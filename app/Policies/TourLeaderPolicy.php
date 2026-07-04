<?php
namespace App\Policies;
class TourLeaderPolicy extends MasterDataPolicy
{
    protected string $permission = 'tour-leaders.manage';
    protected ?string $viewPermission = 'tour-leaders.view';
}
