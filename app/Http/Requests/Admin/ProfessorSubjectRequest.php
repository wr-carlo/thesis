<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProfessorSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $assignmentId = $this->route('assignment')?->id;

        return [
            'professor_id' => ['required', 'exists:professors,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
        ];
    }
}

