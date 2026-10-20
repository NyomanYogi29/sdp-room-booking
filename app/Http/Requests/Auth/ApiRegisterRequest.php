<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in(['dosen', 'mahasiswa'])],
            'nim_nip' => ['required', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'unit' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Role pendaftaran hanya boleh "dosen" atau "mahasiswa". Akun admin hanya dapat dibuat oleh sistem.',
        ];
    }
}
