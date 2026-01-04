<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StudentImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'], // Max 2MB
            'section_id' => ['required', 'exists:sections,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to import.',
            'file.mimes' => 'File must be an Excel file (xlsx, xls, or csv).',
            'file.max' => 'File size must not exceed 2MB.',
            'section_id.required' => 'Please select a section.',
            'section_id.exists' => 'Selected section does not exist.',
        ];
    }
}

