<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SectionImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'exists:departments,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:2048'], // Max 2MB
        ];
    }

    public function messages(): array
    {
        return [
            'department_id.required' => 'Please select a department.',
            'department_id.exists' => 'The selected department is invalid.',
            'file.required' => 'Please select a file to import.',
            'file.mimes' => 'File must be an Excel file (xlsx, xls, or csv).',
            'file.max' => 'File size must not exceed 2MB.',
        ];
    }
}
