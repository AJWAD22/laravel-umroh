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
    ) {}

    /**
     * @param  array{status: string, payment_status: string, group_id?: int|null}  $data
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
            $pin = null;
            $isOperationallyApproved = $data['status'] === 'approved'
                && $data['payment_status'] === 'verified';

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

                if ($pilgrim->activation_pin_generated_at === null) {
                    $pin = $this->activations->generatePin($actor, $pilgrim);
                }
            }

            $registration->forceFill([
                'status' => $data['status'],
                'payment_status' => $data['payment_status'],
            ])->save();

            return [
                'registration' => $registration,
                'pilgrim' => $pilgrim,
                'pin' => $pin,
            ];
        });
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
                    $query->orWhere('nik', $registration->nik);
                }
                if ($registration->passport_number) {
                    $query->orWhere('passport_number', $registration->passport_number);
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
