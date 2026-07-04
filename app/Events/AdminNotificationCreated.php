<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $branchId,
        public readonly string $type,
        public readonly array $data,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("branches.{$this->branchId}"),
            new PrivateChannel('admins.national'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'admin.notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'branch_id' => $this->branchId,
            'type' => $this->type,
            'data' => $this->data,
        ];
    }
}
