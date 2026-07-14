<?php

namespace App\Services;

use App\Enums\MobileRole;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\MobileActivationSession;
use App\Models\MobileDevice;
use App\Models\Pilgrim;
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
        if (! $actor->can('pilgrims.manage')
            || (int) $actor->branch_id !== (int) $pilgrim->branch_id) {
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

    public function claim(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $pilgrim = Pilgrim::query()
                ->where('activation_pin_hash', $this->digest($data['numeric_code']))
                ->whereNull('activation_pin_used_at')
                ->where('activation_pin_generated_at', '>=', now()->subDays(30))
                ->lockForUpdate()
                ->first();

            if (! $pilgrim) {
                throw ValidationException::withMessages([
                    'activation' => ['PIN aktivasi tidak valid, sudah digunakan, atau kedaluwarsa.'],
                ]);
            }

            $this->ensurePilgrimUser($pilgrim);
            $group = $this->groupAccess->activeGroupForPilgrim($pilgrim);
            $leaderUser = $group?->tourLeader?->user;
            $createdBy = $pilgrim->activation_pin_created_by
                ?? $leaderUser?->id
                ?? $pilgrim->user_id;

            MobileActivationSession::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->whereIn('status', ['created', 'awaiting_approval', 'approved'])
                ->update(['status' => 'cancelled']);

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
            $session = MobileActivationSession::query()
                ->with('pilgrim.user')
                ->where('public_id', $data['public_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! hash_equals((string) $session->claim_secret_hash, $this->digest($data['claim_secret']))
                || ! hash_equals((string) $session->device_uuid, $data['device_uuid'])) {
                throw ValidationException::withMessages(['activation' => ['Permintaan aktivasi tidak dikenali.']]);
            }

            if ($session->expires_at->isPast() && ! in_array($session->status, ['approved', 'completed'], true)) {
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
            $session->update(['status' => 'completed', 'completed_at' => now()]);
            $session->pilgrim->forceFill([
                'activation_pin_hash' => null,
                'activation_pin_encrypted' => null,
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

    public function approve(User $leader, MobileActivationSession $session): MobileActivationSession
    {
        $session->loadMissing('pilgrim');
        $this->authorizeLeader($leader, $session->pilgrim);

        if ($session->status !== 'awaiting_approval' || $session->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'activation' => ['Permintaan tidak dapat disetujui karena belum dipindai atau sudah kedaluwarsa.'],
            ]);
        }

        $session->update([
            'status' => 'approved',
            'approved_by' => $leader->id,
            'approved_at' => now(),
        ]);

        return $session->fresh('pilgrim');
    }

    private function authorizeLeader(User $leader, Pilgrim $pilgrim): void
    {
        $allowed = $this->groupAccess
            ->pilgrimsForStaff($leader, MobileRole::TourLeader)
            ->whereKey($pilgrim->id)
            ->exists();

        throw_unless($allowed, AuthorizationException::class);
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
                ->whereNull('activation_pin_used_at')
                ->where('activation_pin_generated_at', '>=', now()->subDays(30))
                ->exists();
        } while ($exists);

        return $code;
    }

    private function digest(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
