<?php

namespace App\Notifications;

use App\Notifications\Channels\BranchDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class AdminAlert extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $branchId,
        protected readonly array $payload,
    ) {}

    public function via(object $notifiable): array
    {
        return [BranchDatabaseChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}
