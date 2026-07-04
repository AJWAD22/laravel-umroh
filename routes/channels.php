<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('branches.{branchId}', function (User $user, int $branchId): bool {
    return $user->hasRole(UserRole::SuperAdmin->value)
        || ($user->hasRole(UserRole::BranchAdmin->value) && $user->branch_id === $branchId);
});

Broadcast::channel('admins.national', function (User $user): bool {
    return $user->hasRole(UserRole::SuperAdmin->value);
});
