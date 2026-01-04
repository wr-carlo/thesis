<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InstructorImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:2048', 'mimes:xlsx,xls,csv'], // Max 2MB
            'department_id' => ['required', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload an Excel file.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The Excel file must not exceed 2MB.',
            'file.mimes' => 'The file must be an Excel spreadsheet (.xlsx, .xls) or a CSV file.',
            'department_id.required' => 'Please select a department for the instructors.',
            'department_id.exists' => 'The selected department is invalid.',
        ];
    }
}

