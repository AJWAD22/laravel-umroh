<?php
namespace App\Policies;

use App\Models\SosReport;
use App\Models\User;

class SosReportPolicy
{
    public function view(User $user, SosReport $report): bool
    {
        return (int) $user->branch_id === (int) optional($report->pilgrim)->branch_id;
    }
}
