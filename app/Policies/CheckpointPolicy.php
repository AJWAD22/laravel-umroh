<?php

namespace App\Policies;

class CheckpointPolicy extends MasterDataPolicy
{
    protected string $permission = 'checkpoints.manage';

    protected ?string $viewPermission = 'checkpoints.view';
}
