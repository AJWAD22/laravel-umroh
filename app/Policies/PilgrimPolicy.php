<?php
namespace App\Policies;
class PilgrimPolicy extends MasterDataPolicy
{
    protected string $permission = 'pilgrims.manage';
    protected ?string $viewPermission = 'pilgrims.view';
}
