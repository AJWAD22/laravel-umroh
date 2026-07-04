<?php

namespace App\Notifications;

class GeofenceExitAlert extends AdminAlert
{
    public function databaseType(object $notifiable): string
    {
        return 'geofence_exit';
    }
}
