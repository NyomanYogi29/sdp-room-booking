<?php

namespace App\Http\Requests\Building;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuildingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $buildingId = $this->route('building')?->id ?? $this->route('building');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('buildings', 'code')->ignore($buildingId)],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
