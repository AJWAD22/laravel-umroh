<?php

namespace App\Notifications;

class GpsOfflineAlert extends AdminAlert
{
    public function databaseType(object $notifiable): string
    {
        return 'gps_offline';
    }
}
