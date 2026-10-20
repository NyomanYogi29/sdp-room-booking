<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
            'title' => $this->title,
            'purpose' => $this->purpose,
            'attendees' => $this->attendees,
            'start_at' => $this->start_at?->toISOString(),
            'end_at' => $this->end_at?->toISOString(),
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'user' => new UserResource($this->whenLoaded('user')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'usage_log' => new UsageLogResource($this->whenLoaded('usageLog')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
