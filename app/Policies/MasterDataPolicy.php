<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class MasterDataPolicy
{
    protected string $permission;
    protected ?string $viewPermission = null;

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->canView($user) && $this->ownsRecord($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can($this->permission);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permission) && $this->ownsRecord($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permission) && $this->ownsRecord($user, $model);
    }

    private function ownsRecord(User $user, Model $model): bool
    {
        return $user->hasRole(UserRole::SuperAdmin->value)
            || ! isset($model->branch_id)
            || (int) $model->branch_id === (int) $user->branch_id;
    }

    private function canView(User $user): bool
    {
        return $user->can($this->permission)
            || ($this->viewPermission !== null && $user->can($this->viewPermission));
    }
}
