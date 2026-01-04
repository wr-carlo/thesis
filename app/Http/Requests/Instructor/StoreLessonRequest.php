<?php

namespace App\Http\Requests\Instructor;

use App\Rules\NoMediaFilesRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is an instructor
        if ($this->user()?->role !== 'instructor') {
            return false;
        }

        // Check if subject is assigned to instructor
        $subjectId = $this->input('subject_id');
        if ($subjectId) {
            $professor = $this->user()->professor;
            return $professor && $professor->subjects()->where('subjects.id', $subjectId)->exists();
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'file' => [
                'required',
                'file',
                'mimes:docx,pdf,pptx,txt',
                'max:10240', // 10MB in KB
                new NoMediaFilesRule(),
            ],
            'multiple_choice_count' => 'required|integer|min:0',
            'identification_count' => 'required|integer|min:0',
            'true_or_false_count' => 'required|integer|min:0',
            'difficulty' => 'required|in:easy,medium,hard',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'subject_id.required' => 'Please select a subject.',
            'subject_id.exists' => 'The selected subject does not exist.',
            'title.required' => 'Please enter a lesson title.',
            'title.max' => 'Lesson title must not exceed 255 characters.',
            'file.required' => 'Please upload a lesson file.',
            'file.mimes' => 'File must be in DOCX, PDF, PPTX, or TXT format.',
            'file.max' => 'File size must not exceed 10MB.',
            'multiple_choice_count.required' => 'Please specify number of multiple choice questions.',
            'multiple_choice_count.integer' => 'Multiple choice count must be a number.',
            'multiple_choice_count.min' => 'Multiple choice count must be at least 0.',
            'identification_count.required' => 'Please specify number of identification questions.',
            'identification_count.integer' => 'Identification count must be a number.',
            'identification_count.min' => 'Identification count must be at least 0.',
            'true_or_false_count.required' => 'Please specify number of true/false questions.',
            'true_or_false_count.integer' => 'True/false count must be a number.',
            'true_or_false_count.min' => 'True/false count must be at least 0.',
            'difficulty.required' => 'Please select a difficulty level.',
            'difficulty.in' => 'Difficulty must be easy, medium, or hard.',
        ];
    }
}
