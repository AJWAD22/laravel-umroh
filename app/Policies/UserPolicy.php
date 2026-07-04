<?php
namespace App\Policies;
class UserPolicy extends MasterDataPolicy
{
    protected string $permission = 'branch-admins.manage';
}
