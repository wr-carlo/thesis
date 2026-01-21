<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'student';
    }

    public function rules(): array
    {
        return [
            'answers' => 'required|array',
            'answers.*.answer' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Please provide answers for the assessment.',
            'answers.array' => 'Answers must be in the correct format.',
        ];
    }
}
