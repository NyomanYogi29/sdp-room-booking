<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $roomId = $this->route('room')?->id ?? $this->route('room');

        return [
            'building_id' => ['sometimes', 'required', 'exists:buildings,id'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('rooms', 'code')->ignore($roomId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'floor' => ['sometimes', 'required', 'integer', 'min:1'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
            'type' => ['sometimes', 'required', Rule::in(['kelas', 'lab', 'rapat'])],
            'status' => ['sometimes', 'required', Rule::in(['active', 'maintenance'])],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['exists:facilities,id'],
        ];
    }
}
