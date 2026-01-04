<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StudentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'id_number' => ['required', 'integer', 'unique:users,id_number'],
            'name' => ['required', 'string', 'max:255'],
            'section_id' => ['required', 'exists:sections,id'],
        ];
    }
}

