<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $sectionId = $this->route('section');

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sections', 'name')
                    ->where(fn ($q) => $q->where('department_id', $this->department_id))
                    ->ignore($sectionId),
            ],
        ];
    }
}

