<?php
namespace App\Policies;
class MuthawwifPolicy extends MasterDataPolicy
{
    protected string $permission = 'muthawwifs.manage';
    protected ?string $viewPermission = 'muthawwifs.view';
}
