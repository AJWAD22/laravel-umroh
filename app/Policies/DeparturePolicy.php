<?php
namespace App\Policies;
class DeparturePolicy extends MasterDataPolicy
{
    protected string $permission = 'departures.manage';
    protected ?string $viewPermission = 'departures.view';
}
