<?php

namespace App\Notifications;

class SosAlert extends AdminAlert
{
    public function databaseType(object $notifiable): string
    {
        return 'sos';
    }
}
