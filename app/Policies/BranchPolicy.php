<?php
namespace App\Policies;
class BranchPolicy extends MasterDataPolicy
{
    protected string $permission = 'branches.manage';
}
