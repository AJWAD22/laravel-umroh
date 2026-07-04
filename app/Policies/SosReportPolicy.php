<?php

namespace App\Policies;

class SosReportPolicy extends MasterDataPolicy
{
    protected string $permission = 'sos.manage';
    protected ?string $viewPermission = 'sos.view';
}
