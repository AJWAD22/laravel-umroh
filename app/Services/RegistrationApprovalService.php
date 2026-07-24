<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Pilgrim;
use App\Models\PilgrimRegistration;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationApprovalService
{
    public function __construct(
        private readonly MasterDataService $masterData,
        private readonly MobileActivationService $activations,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array{status: string, payment_status: string, group_id?: int|null, revision_notes?: string|null}  $data
     * @return array{registration: PilgrimRegistration, pilgrim: Pilgrim|null, pin: string|null}
     */
    public function update(User $actor, PilgrimRegistration $registration, array $data): array
    {
        if (! $actor->can('registrations.manage')
            || (int) $actor->branch_id !== (int) $registration->branch_id) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $registration, $data): array {
            $registration = PilgrimRegistration::query()
                ->with(['user.pilgrim', 'departure'])
                ->lockForUpdate()
                ->findOrFail($registration->id);

            $pilgrim = $registration->user?->pilgrim;
            $before = $registration->getOriginal();
            $pin = null;
            $isOperationallyApproved = $data['status'] === 'in_group'
                && in_array($data['payment_status'], ['paid', 'verified'], true);

            $this->ensureStatusCanChange($registration, $data);

            if (in_array($data['status'], ['approved', 'in_group'], true)) {
                $this->ensureRegistrationIsComplete($registration);
                $this->ensureDepartureQuotaIsAvailable($registration);
            }

            if ($isOperationallyApproved) {
                $group = $this->resolveGroup($registration, $pilgrim, $data['group_id'] ?? null);
                $this->ensureCapacity($group, $pilgrim);
                $this->ensureIdentityIsAvailable($registration, $pilgrim);

                $pilgrim = $this->masterData->save('pilgrims', [
                    'branch_id' => $registration->branch_id,
                    'user_id' => $registration->user_id,
                    'full_name' => $registration->full_name,
                    'nik' => $registration->nik,
                    'passport_number' => $registration->passport_number,
                    'passport_expired_at' => $registration->passport_expired_at,
                    'gender' => $registration->gender,
                    'phone' => $registration->phone,
                    'birth_date' => $registration->birth_date,
                    'address' => $registration->address,
                    'status' => 'registered',
                    'monitoring_status' => 'normal',
                    'group_id' => $group->id,
                ], $actor, $pilgrim);

                $registration->user?->forceFill([
                    'branch_id' => $registration->branch_id,
                    'name' => $registration->full_name,
                ])->save();

            }

            $registration->forceFill([
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
                'revision_notes' => $data['status'] === 'revision_requested'
                    ? ($data['revision_notes'] ?? null)
                    : null,
            ])->save();

            $this->audit->record(
                $actor,
                'registrations.status.updated',
                $registration,
                $before,
                $registration->fresh()->getAttributes(),
                [
                    'branch_id' => $registration->branch_id,
                    'approved_to_pilgrim_id' => $pilgrim?->id,
                    'group_id' => $data['group_id'] ?? null,
                ],
            );

            return [
                'registration' => $registration,
                'pilgrim' => $pilgrim,
                'pin' => $pin,
            ];
        });
    }

    /**
     * @param  array{status: string, payment_status: string, group_id?: int|null, revision_notes?: string|null}  $data
     */
    private function ensureStatusCanChange(PilgrimRegistration $registration, array $data): void
    {
        if ($registration->status === 'in_group' && $data['status'] !== 'in_group') {
            throw ValidationException::withMessages([
                'status' => ['Jamaah yang sudah masuk rombongan tidak bisa dikembalikan melalui perubahan status pendaftaran.'],
            ]);
        }

        if ($data['status'] === 'in_group' && $registration->status !== 'approved') {
            throw ValidationException::withMessages([
                'status' => ['Setujui biodata terlebih dahulu sebelum memasukkan jamaah ke rombongan.'],
            ]);
        }

        if ($data['status'] === 'revision_requested' && blank($data['revision_notes'] ?? null)) {
            throw ValidationException::withMessages([
                'revision_notes' => ['Catatan perbaikan wajib diisi saat meminta perbaikan data.'],
            ]);
        }
    }

    private function ensureRegistrationIsComplete(PilgrimRegistration $registration): void
    {
        foreach ([
            'full_name' => 'Nama lengkap',
            'nik' => 'NIK',
            'gender' => 'Jenis kelamin',
            'birth_date' => 'Tanggal lahir',
            'address' => 'Alamat',
            'emergency_contact_name' => 'Nama kontak darurat',
            'emergency_contact_phone' => 'Nomor kontak darurat',
        ] as $field => $label) {
            if (blank($registration->{$field})) {
                throw ValidationException::withMessages([
                    'status' => ["{$label} harus dilengkapi sebelum pendaftaran disetujui."],
                ]);
            }
        }
    }

    private function ensureDepartureQuotaIsAvailable(PilgrimRegistration $registration): void
    {
        $departure = $registration->departure;
        if (! $departure?->quota) {
            return;
        }

        $used = PilgrimRegistration::query()
            ->where('departure_id', $departure->id)
            ->whereKeyNot($registration->id)
            ->whereIn('status', ['submitted', 'revision_requested', 'approved', 'in_group'])
            ->count();

        if ($used >= $departure->quota) {
            throw ValidationException::withMessages([
                'status' => ['Kuota paket sudah penuh.'],
            ]);
        }
    }

    private function resolveGroup(
        PilgrimRegistration $registration,
        ?Pilgrim $pilgrim,
        int|string|null $groupId,
    ): Group {
        $query = Group::query()
            ->where('branch_id', $registration->branch_id)
            ->where('departure_id', $registration->departure_id)
            ->where('is_active', true);

        if ($groupId) {
            $group = (clone $query)->lockForUpdate()->find($groupId);
        } elseif ($pilgrim) {
            $group = (clone $query)
                ->whereHas('members', fn (Builder $query) => $query
                    ->where('pilgrim_id', $pilgrim->id)
                    ->where('status', 'active'))
                ->lockForUpdate()
                ->first();
        }

        if (! isset($group)) {
            throw ValidationException::withMessages([
                'group_id' => ['Pilih rombongan aktif yang menggunakan paket perjalanan ini.'],
            ]);
        }

        return $group;
    }

    private function ensureCapacity(Group $group, ?Pilgrim $pilgrim): void
    {
        if (! $group->capacity) {
            return;
        }

        $alreadyMember = $pilgrim && GroupMember::query()
            ->where('group_id', $group->id)
            ->where('pilgrim_id', $pilgrim->id)
            ->where('status', 'active')
            ->exists();

        if (! $alreadyMember
            && $group->members()->where('status', 'active')->count() >= $group->capacity) {
            throw ValidationException::withMessages([
                'group_id' => ['Kapasitas rombongan sudah penuh.'],
            ]);
        }
    }

    private function ensureIdentityIsAvailable(
        PilgrimRegistration $registration,
        ?Pilgrim $pilgrim,
    ): void {
        $conflict = Pilgrim::query()
            ->when($pilgrim, fn (Builder $query) => $query->whereKeyNot($pilgrim->id))
            ->where(function (Builder $query) use ($registration): void {
                if ($registration->nik) {
                    $query->orWhere('nik_hash', Pilgrim::identityDigest($registration->nik));
                }
                if ($registration->passport_number) {
                    $query->orWhere('passport_number_hash', Pilgrim::identityDigest($registration->passport_number));
                }
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'status' => ['NIK atau nomor paspor sudah digunakan oleh data jamaah lain.'],
            ]);
        }
    }
}
