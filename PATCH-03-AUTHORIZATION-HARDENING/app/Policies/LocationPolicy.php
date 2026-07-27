<?php
namespace App\Policies;

use App\Models\PilgrimLocation;
use App\Models\User;

class LocationPolicy
{
    public function view(User $user, PilgrimLocation $location): bool
    {
        return (int) $user->branch_id === (int) optional($location->pilgrim)->branch_id;
    }
}
