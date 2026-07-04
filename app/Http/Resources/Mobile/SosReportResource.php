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
            'pilgrim' => new PilgrimResource($this->whenLoaded('pilgrim')),
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'message' => $this->message,
            'status' => $this->status,
            'reported_at' => $this->reported_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
