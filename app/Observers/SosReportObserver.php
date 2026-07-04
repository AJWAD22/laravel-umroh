<?php

namespace App\Observers;

use App\Models\SosReport;
use App\Services\AdminNotificationService;

class SosReportObserver
{
    public function __construct(private readonly AdminNotificationService $notifications) {}

    public function created(SosReport $report): void
    {
        $this->notifications->sos($report);
    }
}
