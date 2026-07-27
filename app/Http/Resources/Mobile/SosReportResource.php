<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SosReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'status' => $this->status,
            'location_status' => $this->location_status ?? 'available',
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'accuracy' => $this->accuracy !== null ? (float) $this->accuracy : null,
            'message' => $this->message,
            'reported_at' => $this->reported_at?->toIso8601String(),
            'acknowledged_at' => $this->acknowledged_at?->toIso8601String(),
            'assigned_at' => $this->assigned_at?->toIso8601String(),
            'arrived_at' => $this->arrived_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'resolution_notes' => $this->resolution_notes ?? $this->resolution_note,
            'pilgrim' => new PilgrimResource($this->whenLoaded('pilgrim')),
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group?->id,
                'name' => $this->group?->name,
                'code' => $this->group?->code,
            ]),
            'handler' => $this->whenLoaded('handler', fn () => [
                'id' => $this->handler?->id,
                'name' => $this->handler?->name,
            ]),
        ];
    }
}
