<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PilgrimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'registration_number' => $this->registration_number,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'photo_url' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'status' => $this->status,
            'monitoring_status' => $this->monitoring_status,
            'branch' => $this->whenLoaded('branch', fn () => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
            ]),
            'latest_location' => new LocationResource($this->whenLoaded('latestLocation')),
        ];
    }
}
