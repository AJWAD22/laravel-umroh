<?php
namespace App\Policies;
class HotelPolicy extends MasterDataPolicy
{
    protected string $permission = 'hotels.manage';
    protected ?string $viewPermission = 'hotels.view';
}
