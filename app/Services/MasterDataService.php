<?php

namespace App\Services;

use App\Enums\MobileRole;
use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\Checkpoint;
use App\Models\Departure;
use App\Models\Group;
use App\Models\Hotel;
use App\Models\Muthawwif;
use App\Models\Pilgrim;
use App\Models\TourLeader;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataService
{
    public function __construct(
        private readonly ProfilePhotoService $photos,
        private readonly OperationalCodeGenerator $codes,
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function resources(): array
    {
        return [
            'branches' => $this->definition(Branch::class, 'Cabang', 'branches.manage',
                ['code' => 'Kode', 'name' => 'Nama Cabang', 'city' => 'Kota', 'province' => 'Provinsi', 'is_active' => 'Aktif'],
                ['code', 'name', 'city', 'province'], ['code', 'name', 'city', 'province', 'is_active']),
            'branch-admins' => $this->definition(User::class, 'Admin Cabang', 'branch-admins.manage',
                ['photo_path' => 'Foto', 'name' => 'Nama', 'email' => 'Email', 'branch.name' => 'Cabang', 'is_active' => 'Aktif'],
                ['name', 'email', 'phone_number'], ['name', 'email', 'is_active'], ['branch']),
            'pilgrims' => $this->definition(Pilgrim::class, 'Jamaah', 'pilgrims.manage',
                ['photo_path' => 'Foto', 'registration_number' => 'No. Registrasi', 'full_name' => 'Nama', 'branch.name' => 'Cabang', 'phone' => 'Telepon', 'activation_pin' => 'PIN Aktivasi', 'status' => 'Status'],
                ['registration_number', 'full_name', 'nik', 'passport_number', 'phone'],
                ['registration_number', 'full_name', 'phone', 'status'], ['branch']),
            'tour-leaders' => $this->definition(TourLeader::class, 'Tour Leader', 'tour-leaders.manage',
                ['photo_path' => 'Foto', 'employee_number' => 'No. Pegawai', 'full_name' => 'Nama', 'user.email' => 'Email Login', 'branch.name' => 'Cabang', 'phone' => 'Telepon', 'is_active' => 'Aktif'],
                ['employee_number', 'full_name', 'phone'], ['employee_number', 'full_name', 'phone', 'is_active'], ['branch', 'user']),
            'muthawwifs' => $this->definition(Muthawwif::class, 'Muthawwif', 'muthawwifs.manage',
                ['photo_path' => 'Foto', 'employee_number' => 'No. Pegawai', 'full_name' => 'Nama', 'user.email' => 'Email Login', 'branch.name' => 'Cabang', 'phone' => 'Telepon', 'is_active' => 'Aktif'],
                ['employee_number', 'full_name', 'phone', 'languages'], ['employee_number', 'full_name', 'phone', 'is_active'], ['branch', 'user']),
            'hotels' => $this->definition(Hotel::class, 'Hotel', 'hotels.manage',
                ['name' => 'Nama Hotel', 'branch.name' => 'Cabang', 'city' => 'Kota', 'geofence_radius_meters' => 'Radius (m)'],
                ['name', 'address'], ['name', 'city', 'geofence_radius_meters'], ['branch']),
            'checkpoints' => $this->definition(Checkpoint::class, 'Tujuan & Titik Penting', 'hotels.manage',
                ['name' => 'Nama Tujuan', 'category' => 'Kategori', 'city' => 'Kota', 'branch.name' => 'Cabang', 'departure.program_name' => 'Keberangkatan', 'group.name' => 'Rombongan', 'is_active' => 'Aktif'],
                ['name', 'address', 'description'], ['name', 'category', 'city', 'is_active'], ['branch', 'departure', 'group']),
            'departures' => $this->definition(Departure::class, 'Keberangkatan', 'departures.manage',
                ['code' => 'Kode', 'program_name' => 'Program', 'branch.name' => 'Cabang', 'departure_date' => 'Berangkat', 'status' => 'Status'],
                ['code', 'program_name', 'departure_airport'], ['code', 'program_name', 'departure_date', 'status'], ['branch']),
            'groups' => $this->definition(Group::class, 'Rombongan', 'groups.manage',
                ['code' => 'Kode', 'name' => 'Nama Rombongan', 'branch.name' => 'Cabang', 'departure.program_name' => 'Keberangkatan', 'is_active' => 'Aktif'],
                ['code', 'name'], ['code', 'name', 'is_active'], ['branch', 'departure']),
        ];
    }

    public function definitionFor(string $resource): array
    {
        abort_unless(isset($this->resources()[$resource]), 404);

        return $this->resources()[$resource];
    }

    public function query(string $resource, User $user): Builder
    {
        $definition = $this->definitionFor($resource);
        $query = $definition['model']::query()->with($definition['with']);

        if ($resource === 'branch-admins') {
            $query->role(UserRole::BranchAdmin->value);
        }

        if (! $user->hasRole(UserRole::SuperAdmin->value) && $resource !== 'branches') {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    public function find(string $resource, int $id, User $user): Model
    {
        return $this->query($resource, $user)->findOrFail($id);
    }

    public function save(string $resource, array $data, User $actor, ?Model $record = null): Model
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo']);

        if (in_array($resource, ['tour-leaders', 'muthawwifs'], true)) {
            return DB::transaction(function () use ($resource, $data, $record, $photo): Model {
                if (! $record) {
                    $generated = $this->codes->generate($resource, $data);
                    $data[$generated['column']] = $generated['value'];
                }

                $email = $data['email'];
                $password = $data['password'] ?? null;
                unset($data['email'], $data['password']);

                /** @var TourLeader|Muthawwif $staff */
                $staff = $record ?? new ($this->definitionFor($resource)['model']);
                /** @var User $user */
                $user = $staff->user ?? new User;
                $user->fill([
                    'branch_id' => $data['branch_id'],
                    'name' => $data['full_name'],
                    'email' => $email,
                    'phone_number' => $data['phone'] ?? null,
                    'is_active' => $data['is_active'],
                ]);
                if (filled($password)) {
                    $user->password = $password;
                }
                $user->save();
                $user->syncRoles(
                    $resource === 'tour-leaders'
                        ? MobileRole::TourLeader->value
                        : MobileRole::Muthawwif->value
                );

                $data['user_id'] = $user->id;
                $staff->fill($data)->save();

                if ($photo) {
                    $photoPath = $this->photos->store(
                        $photo,
                        $staff->photo_path,
                        $resource,
                    );
                    $staff->forceFill(['photo_path' => $photoPath])->save();
                    $user->forceFill(['photo_path' => $photoPath])->save();
                } elseif ($staff->photo_path !== $user->photo_path) {
                    $user->forceFill(['photo_path' => $staff->photo_path])->save();
                }

                return $staff->load(['branch', 'user']);
            });
        }

        if ($resource === 'branch-admins') {
            if (filled($data['password'] ?? null)) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }

            /** @var User $admin */
            $admin = $record ?? new User;
            $admin->fill($data)->save();
            if ($photo) {
                $admin->forceFill([
                    'photo_path' => $this->photos->store($photo, $admin->photo_path, 'admins'),
                ])->save();
            }
            $admin->syncRoles(UserRole::BranchAdmin->value);

            return $admin;
        }

        return DB::transaction(function () use ($resource, $data, $record, $photo): Model {
            if (! $record && in_array($resource, ['pilgrims', 'departures', 'groups'], true)) {
                $generated = $this->codes->generate($resource, $data);
                $data[$generated['column']] = $generated['value'];
            }

            $modelClass = $this->definitionFor($resource)['model'];
            $model = $record ?? new $modelClass;
            $model->fill($data)->save();
            if ($photo) {
                $directory = match ($resource) {
                    'pilgrims' => 'pilgrims',
                    default => 'master-data',
                };
                $model->forceFill([
                    'photo_path' => $this->photos->store($photo, $model->photo_path, $directory),
                ])->save();
            }

            return $model;
        });
    }

    /**
     * @return array<string, array<int|string, string>>
     */
    public function options(string $resource, User $user): array
    {
        $branchQuery = Branch::query()->orderBy('name');
        if (! $user->hasRole(UserRole::SuperAdmin->value)) {
            $branchQuery->whereKey($user->branch_id);
        }
        $branchId = $user->hasRole(UserRole::SuperAdmin->value) ? null : $user->branch_id;

        return [
            'branches' => $branchQuery->pluck('name', 'id')->all(),
            'departures' => Departure::query()->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->orderByDesc('departure_date')->pluck('program_name', 'id')->all(),
            'tourLeaders' => TourLeader::query()->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id')->all(),
            'muthawwifs' => Muthawwif::query()->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id')->all(),
            'groups' => Group::query()->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId))
                ->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    private function definition(
        string $model,
        string $label,
        string $permission,
        array $columns,
        array $search,
        array $sort,
        array $with = [],
    ): array {
        return compact('model', 'label', 'permission', 'columns', 'search', 'sort', 'with');
    }
}
