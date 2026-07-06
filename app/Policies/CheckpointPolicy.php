<?php

namespace App\Policies;

class CheckpointPolicy extends MasterDataPolicy
{
    protected string $permission = 'hotels.manage';

    protected ?string $viewPermission = 'hotels.view';
}
