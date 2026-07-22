<?php

namespace App\Services;

use App\Enums\MobileRole;
use App\Enums\UserRole;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\MobileActivationSession;
use App\Models\MobileDevice;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileActivationService
{
    public function __construct(private readonly MobileGroupAccessService $groupAccess) {}

    /** Membuat PIN aktivasi baru untuk jamaah yang dikelola admin. */
    public function generatePin(User $actor, Pilgrim $pilgrim): string
    {
        $isSuperAdmin = $actor->hasRole(UserRole::SuperAdmin->value);

        if (! $isSuperAdmin
            && (! $actor->can('pilgrims.manage')
                || (int) $actor->branch_id !== (int) $pilgrim->branch_id)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $pilgrim): string {
            $user = $this->ensurePilgrimUser($pilgrim);

            // Mengganti PIN juga mencabut sesi login perangkat lama.
            $user->tokens()->delete();
            MobileDevice::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            // Hapus hanya snapshot lokasi aktif agar marker lama tidak tetap
            // muncul di Live Map. Riwayat detail tetap tersimpan di tabel
            // location_histories untuk kebutuhan laporan dan audit.
            PilgrimLocation::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->delete();
            MobileActivationSession::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->whereIn('status', ['created', 'awaiting_approval', 'approved'])
                ->update(['status' => 'cancelled']);

            $numericCode = $this->uniqueNumericCode();
            $pilgrim->forceFill([
                'activation_pin_hash' => $this->digest($numericCode),
                'activation_pin_encrypted' => Crypt::encryptString($numericCode),
                'activation_pin_created_by' => $actor->id,
                'activation_pin_generated_at' => now(),
                'activation_pin_used_at' => null,
            ])->save();

            return $numericCode;
        });
    }

    /**
     * @return array{count: int, pins: array<int, string>}
     */
    public function resetPinsForGroup(User $actor, Group $group): array
    {
        $isSuperAdmin = $actor->hasRole(UserRole::SuperAdmin->value);

        if (! $isSuperAdmin
            && (! $actor->can('pilgrims.manage')
                || (int) $actor->branch_id !== (int) $group->branch_id)) {
            throw new AuthorizationException;
        }

        $pins = [];
        $group->pilgrims()
            ->wherePivot('status', 'active')
            ->orderBy('full_name')
            ->get()
            ->each(function (Pilgrim $pilgrim) use ($actor, &$pins): void {
                $pins[$pilgrim->id] = $this->generatePin($actor, $pilgrim);
            });

        return [
            'count' => count($pins),
            'pins' => $pins,
        ];
    }

    public function claim(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            // Jamaah memasukkan PIN dari aplikasi.
            // Sistem mencari PIN yang masih aktif, belum dipakai, dan belum kedaluwarsa.
            $pilgrim = Pilgrim::query()
                ->where('activation_pin_hash', $this->digest($data['numeric_code']))
                ->lockForUpdate()
                ->first();

            if (! $pilgrim) {
                throw ValidationException::withMessages([
                    'activation' => ['PIN aktivasi tidak valid.'],
                ]);
            }

            $this->ensurePilgrimUser($pilgrim);

            // Aktivasi langsung disetujui otomatis. Tour Leader tidak perlu
            // menekan approve, tetapi relasi rombongan tetap dipakai untuk audit.
            $group = $this->groupAccess->activeGroupForPilgrim($pilgrim);
            if ($group?->departure && in_array($group->departure->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages([
                    'activation' => ['PIN aktivasi sudah tidak berlaku karena perjalanan selesai atau dibatalkan.'],
                ]);
            }
            $leaderUser = $group?->tourLeader?->user;
            $createdBy = $pilgrim->activation_pin_created_by
                ?? $leaderUser?->id
                ?? $pilgrim->user_id;

            MobileActivationSession::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->whereIn('status', ['created', 'awaiting_approval', 'approved'])
                ->update(['status' => 'cancelled']);

            // claim_secret adalah kode rahasia sementara yang dipakai APK
            // untuk mengambil token login pada tahap status().
            $claimSecret = Str::random(64);
            $session = MobileActivationSession::create([
                'public_id' => (string) Str::uuid(),
                'pilgrim_id' => $pilgrim->id,
                'created_by' => $createdBy,
                'activation_token_hash' => $this->digest(Str::random(64)),
                'numeric_code_hash' => $this->digest($data['numeric_code']),
                'claim_secret_hash' => $this->digest($claimSecret),
                'device_uuid' => $data['device_uuid'],
                'device_name' => $data['device_name'],
                'platform' => $data['platform'],
                'status' => 'approved',
                'claimed_at' => now(),
                'approved_at' => now(),
                'expires_at' => now()->addMinutes(10),
            ]);

            return [
                'public_id' => $session->public_id,
                'claim_secret' => $claimSecret,
                'status' => $session->status,
                'message' => 'PIN valid. Aktivasi perangkat sedang diproses.',
                'pilgrim_name' => $session->pilgrim->full_name,
            ];
        });
    }

    public function status(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            // APK memanggil endpoint ini setelah claim berhasil.
            // lockForUpdate mencegah dua perangkat menyelesaikan aktivasi bersamaan.
            $session = MobileActivationSession::query()
                ->with('pilgrim.user')
                ->where('public_id', $data['public_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals((string) $session->claim_secret_hash, $this->digest($data['claim_secret']))
                || ! hash_equals((string) $session->device_uuid, $data['device_uuid'])) {
                throw ValidationException::withMessages(['activation' => ['Permintaan aktivasi tidak dikenali.']]);
            }

            if ($session->expires_at->isPast() && $session->status !== 'completed') {
                $session->update(['status' => 'expired']);
            }

            if ($session->status !== 'approved') {
                return [
                    'status' => $session->status,
                    'message' => match ($session->status) {
                        'awaiting_approval' => 'Aktivasi sedang diproses.',
                        'expired' => 'Permintaan aktivasi kedaluwarsa.',
                        'cancelled' => 'Permintaan aktivasi dibatalkan.',
                        'completed' => 'Aktivasi telah selesai.',
                        default => 'Aktivasi sedang diproses.',
                    },
                ];
            }

            $user = $session->pilgrim->user;
            abort_unless($user && $user->is_active, 422, 'Akun Jamaah tidak aktif.');

            // Satu jamaah hanya boleh aktif di satu perangkat.
            // Token dan perangkat lama dicabut agar tracking lama berhenti.
            $user->tokens()->delete();
            MobileDevice::query()
                ->where(fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->orWhere('device_uuid', $session->device_uuid))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            MobileDevice::updateOrCreate(
                ['device_uuid' => $session->device_uuid],
                [
                    'user_id' => $user->id,
                    'device_name' => $session->device_name,
                    'platform' => $session->platform,
                    'activated_at' => now(),
                    'last_used_at' => now(),
                    'revoked_at' => null,
                    'activated_by' => $session->approved_by,
                ],
            );

            $token = $user->createToken(
                'activation-'.$session->device_uuid,
                [MobileRole::Pilgrim->ability()],
            );

            // PIN tetap disimpan agar bisa dipakai lagi selama perjalanan belum
            // selesai, misalnya saat jamaah mengganti perangkat. Perangkat lama
            // tetap dicabut sehingga satu jamaah hanya aktif pada satu perangkat.
            $session->update(['status' => 'completed', 'completed_at' => now()]);
            $session->pilgrim->forceFill([
                'activation_pin_used_at' => now(),
            ])->save();

            return [
                'status' => 'completed',
                'message' => 'Perangkat berhasil diaktifkan.',
                'token_type' => 'Bearer',
                'access_token' => $token->plainTextToken,
                'role' => MobileRole::Pilgrim->value,
                'user' => (new ProfileResource(
                    $user->load(['branch', 'pilgrim', 'tourLeader', 'muthawwif', 'roles']),
                ))->resolve(),
            ];
        });
    }

    private function ensurePilgrimUser(Pilgrim $pilgrim): User
    {
        if ($pilgrim->user) {
            $pilgrim->user->syncRoles(MobileRole::Pilgrim->value);

            return $pilgrim->user;
        }

        $user = User::create([
            'branch_id' => $pilgrim->branch_id,
            'name' => $pilgrim->full_name,
            'email' => "jamaah-{$pilgrim->id}@activation.umrah.local",
            'phone_number' => $pilgrim->phone,
            'password' => Str::password(32),
            'is_active' => true,
        ]);
        $user->syncRoles(MobileRole::Pilgrim->value);
        $pilgrim->update(['user_id' => $user->id]);
        $pilgrim->setRelation('user', $user);

        return $user;
    }

    private function uniqueNumericCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $exists = Pilgrim::query()
                ->where('activation_pin_hash', $this->digest($code))
                ->exists();
        } while ($exists);

        return $code;
    }

    private function digest(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
