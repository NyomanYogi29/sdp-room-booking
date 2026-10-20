<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'floor' => $this->floor,
            'capacity' => $this->capacity,
            'type' => $this->type,
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'photo' => $this->photo,
            'photo_url' => $this->photo_url,
            'building' => new BuildingResource($this->whenLoaded('building')),
            'facilities' => FacilityResource::collection($this->whenLoaded('facilities')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
