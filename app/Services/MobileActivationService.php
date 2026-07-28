<?php

namespace App\Services;

use App\Enums\MobileRole;
use App\Http\Resources\Mobile\ProfileResource;
use App\Models\MobileActivationSession;
use App\Models\MobileDevice;
use App\Models\Group;
use App\Models\Pilgrim;
use App\Models\PilgrimLocation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileActivationService
{
    public function __construct(
        private readonly MobileGroupAccessService $groupAccess,
        private readonly AuditLogService $audit,
    ) {}

    /** Membuat PIN aktivasi baru untuk jamaah yang dikelola admin. */
    public function generatePin(User $actor, Pilgrim $pilgrim, ?string $reason = null): string
    {
        if (! $actor->can('pilgrims.manage')
            || (int) $actor->branch_id !== (int) $pilgrim->branch_id) {
            throw new AuthorizationException;
        }
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan pembuatan atau reset PIN wajib diisi.'],
            ]);
        }

        return $this->issuePin($pilgrim, (string) $reason, $actor);
    }

    /**
     * Merotasi PIN yang sudah berumur sesuai batas hari.
     *
     * @return array{rotated: int, skipped: int}
     */
    public function rotateExpiredPins(int $days = 15): array
    {
        $days = max(1, $days);
        $rotated = 0;
        $skipped = 0;

        Pilgrim::query()
            ->whereNotNull('activation_pin_hash')
            ->whereNotNull('activation_pin_generated_at')
            ->where('activation_pin_generated_at', '<=', now()->subDays($days))
            ->whereIn('status', ['registered', 'active'])
            ->whereHas('groupMemberships', fn ($query) => $query
                ->where('status', 'active')
                ->whereHas('group', fn ($groupQuery) => $groupQuery
                    ->where('is_active', true)
                    ->whereHas('departure', fn ($departureQuery) => $departureQuery
                        ->whereIn('status', ['scheduled', 'departed'])
                        ->where(fn ($dateQuery) => $dateQuery
                            ->whereNull('return_date')
                            ->orWhereDate('return_date', '>=', today())))))
            ->orderBy('id')
            ->chunkById(100, function ($pilgrims) use ($days, &$rotated, &$skipped): void {
                foreach ($pilgrims as $pilgrim) {
                    try {
                        $this->issuePin(
                            $pilgrim,
                            "Rotasi PIN otomatis setiap {$days} hari.",
                        );
                        $rotated++;
                    } catch (ValidationException) {
                        $skipped++;
                    }
                }
            });

        $this->audit->record(
            null,
            'activation.pins.auto_rotated',
            metadata: [
                'rotation_days' => $days,
                'rotated_count' => $rotated,
                'skipped_count' => $skipped,
            ],
        );

        return ['rotated' => $rotated, 'skipped' => $skipped];
    }

    private function issuePin(Pilgrim $pilgrim, string $reason, ?User $actor = null): string
    {
        return DB::transaction(function () use ($actor, $pilgrim, $reason): string {
            $pilgrim = Pilgrim::query()
                ->with(['user', 'groupMemberships.group.departure'])
                ->lockForUpdate()
                ->findOrFail($pilgrim->id);
            $this->ensurePinEligible($pilgrim);
            $this->ensurePilgrimUser($pilgrim);
            $before = $pilgrim->only([
                'activation_pin_hash',
                'activation_pin_ciphertext',
                'activation_pin_generated_at',
                'activation_pin_used_at',
            ]);

            MobileActivationSession::query()
                ->where('pilgrim_id', $pilgrim->id)
                ->whereIn('status', ['created', 'awaiting_approval', 'approved'])
                ->update(['status' => 'cancelled']);
            $revokedDevices = $this->revokeActiveDevicesForPilgrim($pilgrim);

            $numericCode = $this->uniqueNumericCode();
            $pilgrim->forceFill([
                'activation_pin_hash' => $this->digest($numericCode),
                'activation_pin_ciphertext' => $numericCode,
                'activation_pin_created_by' => $actor?->id,
                'activation_pin_generated_at' => now(),
                'activation_pin_used_at' => null,
            ])->save();

            $this->audit->record(
                $actor,
                'activation.pin.generated',
                $pilgrim,
                $before,
                $pilgrim->only([
                    'activation_pin_hash',
                    'activation_pin_ciphertext',
                    'activation_pin_generated_at',
                    'activation_pin_used_at',
                ]),
                [
                    'branch_id' => $pilgrim->branch_id,
                    'reason' => $reason,
                    'revoked_existing_devices' => $revokedDevices > 0,
                    'revoked_devices' => $revokedDevices,
                ],
            );

            return $numericCode;
        });
    }

    public function revealPinForTourLeader(User $actor, Pilgrim $pilgrim): string
    {
        if (! $actor->hasRole(MobileRole::TourLeader->value)
            || ! $this->groupAccess->pilgrimsForStaff($actor, MobileRole::TourLeader)
                ->whereKey($pilgrim->id)
                ->exists()) {
            throw new AuthorizationException;
        }

        if (blank($pilgrim->activation_pin_ciphertext)) {
            throw ValidationException::withMessages([
                'activation' => ['PIN belum tersedia atau dibuat sebelum fitur ini aktif. Minta Admin Cabang membuat ulang PIN.'],
            ]);
        }

        $this->audit->record(
            $actor,
            'activation.pin.revealed_by_tour_leader',
            $pilgrim,
            metadata: [
                'branch_id' => $pilgrim->branch_id,
                'registration_number' => $pilgrim->registration_number,
            ],
        );

        return (string) $pilgrim->activation_pin_ciphertext;
    }

    /**
     * @return array{count: int, pins: list<array{pilgrim_id: int, registration_number: string, name: string, pin: string}>}
     */
    public function resetPinsForGroup(User $actor, Group $group, ?string $reason = null): array
    {
        if (! $actor->can('pilgrims.manage')
            || (int) $actor->branch_id !== (int) $group->branch_id) {
            throw new AuthorizationException;
        }
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan reset PIN rombongan wajib diisi.'],
            ]);
        }

        $pins = [];
        $group->pilgrims()
            ->wherePivot('status', 'active')
            ->orderBy('full_name')
            ->get()
            ->each(function (Pilgrim $pilgrim) use ($actor, $reason, &$pins): void {
                $pins[] = [
                    'pilgrim_id' => $pilgrim->id,
                    'registration_number' => $pilgrim->registration_number,
                    'name' => $pilgrim->full_name,
                    'pin' => $this->generatePin($actor, $pilgrim, $reason),
                ];
            });

        $this->audit->record(
            $actor,
            'activation.group_pins.reset',
            $group,
            [],
            ['reset_count' => count($pins)],
            [
                'branch_id' => $group->branch_id,
                'reason' => $reason,
            ],
        );

        return [
            'count' => count($pins),
            'pins' => $pins,
        ];
    }

    /**
     * @return array{count: int, groups: int, pins: list<array{pilgrim_id: int, registration_number: string, name: string, pin: string}>}
     */
    public function resetPinsForDeparture(User $actor, \App\Models\Departure $departure, ?string $reason = null): array
    {
        if (! $actor->can('pilgrims.manage')
            || (int) $actor->branch_id !== (int) $departure->branch_id) {
            throw new AuthorizationException;
        }
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan reset PIN paket wajib diisi.'],
            ]);
        }

        $groups = $departure->groups()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages([
                'activation' => ['Paket belum memiliki rombongan aktif.'],
            ]);
        }

        $pins = [];
        foreach ($groups as $group) {
            $result = $this->resetPinsForGroup($actor, $group, $reason);
            $pins = [...$pins, ...$result['pins']];
        }

        $this->audit->record(
            $actor,
            'activation.departure_pins.reset',
            $departure,
            [],
            ['reset_count' => count($pins), 'group_count' => $groups->count()],
            ['branch_id' => $departure->branch_id, 'reason' => $reason],
        );

        return [
            'count' => count($pins),
            'groups' => $groups->count(),
            'pins' => $pins,
        ];
    }

    /**
     * @return array{count: int, pins: list<array{pilgrim_id: int, registration_number: string, name: string, pin: string}>}
     */
    public function generateMissingPinsForGroup(User $actor, Group $group, string $reason): array
    {
        if (! $actor->can('pilgrims.manage')
            || (int) $actor->branch_id !== (int) $group->branch_id) {
            throw new AuthorizationException;
        }

        $pins = [];
        $group->pilgrims()
            ->wherePivot('status', 'active')
            ->where(fn ($query) => $query
                ->whereNull('activation_pin_hash')
                ->orWhereNull('activation_pin_ciphertext'))
            ->orderBy('full_name')
            ->get()
            ->each(function (Pilgrim $pilgrim) use ($actor, $reason, &$pins): void {
                $pins[] = [
                    'pilgrim_id' => $pilgrim->id,
                    'registration_number' => $pilgrim->registration_number,
                    'name' => $pilgrim->full_name,
                    'pin' => $this->generatePin($actor, $pilgrim, $reason),
                ];
            });

        $this->audit->record(
            $actor,
            'activation.group_missing_pins.generated',
            $group,
            [],
            ['generated_count' => count($pins)],
            [
                'branch_id' => $group->branch_id,
                'reason' => $reason,
            ],
        );

        return ['count' => count($pins), 'pins' => $pins];
    }

    public function revokePilgrimDevices(User $actor, Pilgrim $pilgrim, string $reason): int
    {
        if (! $actor->can('pilgrims.manage')
            || (int) $actor->branch_id !== (int) $pilgrim->branch_id) {
            throw new AuthorizationException;
        }
        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => ['Alasan pencabutan perangkat wajib diisi.'],
            ]);
        }

        return DB::transaction(function () use ($actor, $pilgrim, $reason): int {
            $user = $pilgrim->user;
            if (! $user) {
                return 0;
            }

            $revokedDevices = $this->revokeActiveDevicesForPilgrim($pilgrim);

            $this->audit->record(
                $actor,
                'activation.devices.revoked',
                $pilgrim,
                ['active_devices' => $revokedDevices],
                ['revoked_devices' => $revokedDevices],
                [
                    'branch_id' => $pilgrim->branch_id,
                    'reason' => $reason,
                ],
            );

            return $revokedDevices;
        });
    }

    public function claim(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            // Jamaah memasukkan PIN dari aplikasi.
            // Sistem mencari PIN yang masih aktif, belum dipakai, dan belum kedaluwarsa.
            $pilgrim = Pilgrim::query()
                ->where('activation_pin_hash', $this->digest($data['numeric_code']))
                ->where('registration_number', $data['registration_number'])
                ->lockForUpdate()
                ->first();

            if (! $pilgrim) {
                throw ValidationException::withMessages([
                    'activation' => ['PIN aktivasi tidak valid.'],
                ]);
            }

            $this->ensurePilgrimUser($pilgrim);
            $this->ensurePinEligible($pilgrim);

            // Aktivasi langsung disetujui otomatis. Tour Leader tidak perlu
            // menekan approve, tetapi relasi rombongan tetap dipakai untuk audit.
            $group = $this->groupAccess->activeGroupForPilgrim($pilgrim);
            if (! $group) {
                throw ValidationException::withMessages([
                    'activation' => ['Jamaah belum ditempatkan pada rombongan aktif. Hubungi Admin Cabang.'],
                ]);
            }

            $group->loadMissing('departure');
            if (! $group->departure) {
                throw ValidationException::withMessages([
                    'activation' => ['Rombongan belum memiliki paket perjalanan. Hubungi Admin Cabang.'],
                ]);
            }

            if (! in_array($group->departure->status, ['scheduled', 'departed'], true)
                || $group->departure->return_date?->endOfDay()->isPast()) {
                throw ValidationException::withMessages([
                    'activation' => ['PIN hanya berlaku sampai perjalanan selesai. Hubungi Admin Cabang jika status perjalanan belum diperbarui.'],
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

    private function ensurePinEligible(Pilgrim $pilgrim): void
    {
        if (! in_array($pilgrim->status, ['registered', 'active'], true)) {
            throw ValidationException::withMessages([
                'activation' => ['Jamaah belum aktif untuk aktivasi aplikasi.'],
            ]);
        }

        $group = $this->groupAccess->activeGroupForPilgrim($pilgrim);
        if (! $group) {
            throw ValidationException::withMessages([
                'activation' => ['Jamaah belum ditempatkan pada rombongan aktif.'],
            ]);
        }

        $group->loadMissing('departure');
        if (! $group->departure
            || ! in_array($group->departure->status, ['scheduled', 'departed'], true)
            || $group->departure->return_date?->endOfDay()->isPast()) {
            throw ValidationException::withMessages([
                'activation' => ['PIN hanya berlaku sampai perjalanan selesai.'],
            ]);
        }

        $registration = \App\Models\PilgrimRegistration::query()
            ->where('user_id', $pilgrim->user_id)
            ->where('branch_id', $pilgrim->branch_id)
            ->where('departure_id', $group->departure_id)
            ->where('status', 'in_group')
            ->whereIn('payment_status', ['paid', 'verified'])
            ->first();

        if (! $registration) {
            throw ValidationException::withMessages([
                'activation' => ['PIN hanya dapat dibuat untuk jamaah yang sudah lunas dan masuk rombongan.'],
            ]);
        }
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

    private function revokeActiveDevicesForPilgrim(Pilgrim $pilgrim): int
    {
        $user = $pilgrim->user;
        if (! $user) {
            return 0;
        }

        $devices = MobileDevice::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->get();

        $devices->each(function (MobileDevice $device) use ($user): void {
            $user->tokens()
                ->where('name', 'activation-'.$device->device_uuid)
                ->delete();
            $device->forceFill(['revoked_at' => now()])->save();
        });

        PilgrimLocation::query()
            ->where('pilgrim_id', $pilgrim->id)
            ->delete();

        return $devices->count();
    }
}
