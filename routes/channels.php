<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('branches.{branchId}', function (User $user, int $branchId): bool {
    return $user->hasRole(UserRole::BranchAdmin->value)
        && $user->branch_id === $branchId;
});
