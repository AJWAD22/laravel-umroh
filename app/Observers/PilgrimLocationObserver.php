<?php

namespace App\Observers;

use App\Models\PilgrimLocation;
use App\Services\AdminNotificationService;

class PilgrimLocationObserver
{
    public function __construct(private readonly AdminNotificationService $notifications) {}

    public function saved(PilgrimLocation $location): void
    {
        if ($location->gps_status === 'offline'
            && ($location->wasRecentlyCreated || $location->wasChanged('gps_status'))) {
            $this->notifications->gpsOffline($location);
        }
    }
}
