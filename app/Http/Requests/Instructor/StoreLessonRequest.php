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
            'bloom_levels' => 'required|array|min:1',
            'bloom_levels.*' => 'in:remember,understand,apply,analyze,evaluate,create',
            'question_distribution' => 'required|array',
            'question_distribution.*.mcq' => 'required|integer|min:0',
            'question_distribution.*.identification' => 'required|integer|min:0',
            'question_distribution.*.tf' => 'required|integer|min:0',
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
            'bloom_levels.required' => 'Please select at least one Bloom\'s Taxonomy level.',
            'bloom_levels.array' => 'Bloom\'s levels must be an array.',
            'bloom_levels.min' => 'Please select at least one Bloom\'s Taxonomy level.',
            'bloom_levels.*.in' => 'Invalid Bloom\'s Taxonomy level selected.',
            'question_distribution.required' => 'Question distribution data is required.',
            'question_distribution.array' => 'Question distribution must be an array.',
        ];
    }
}
