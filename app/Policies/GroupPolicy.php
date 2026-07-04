<?php
namespace App\Policies;
class GroupPolicy extends MasterDataPolicy
{
    protected string $permission = 'groups.manage';
    protected ?string $viewPermission = 'groups.view';
}
