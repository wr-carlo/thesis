<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $userId = $this->route('student')?->id;

        return [
            'id_number' => ['required', 'integer', Rule::unique('users', 'id_number')->ignore($userId)],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'section_id' => ['required', 'exists:sections,id'],
        ];
    }
}

