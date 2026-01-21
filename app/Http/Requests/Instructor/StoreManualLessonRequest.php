<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualLessonRequest extends FormRequest
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
            'status' => 'nullable|in:draft,published',
            'section_ids' => 'nullable|array',
            'section_ids.*' => 'exists:sections,id',
            'questions' => 'required|array|min:1',
            'questions.*.type' => 'required|in:multiple_choice,identification,true_or_false',
            'questions.*.question' => 'required|string',
            'questions.*.choices' => 'required_if:questions.*.type,multiple_choice|nullable|array|min:4',
            'questions.*.correct_answer' => 'required|string',
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
            'section_ids.array' => 'Sections must be an array.',
            'section_ids.*.exists' => 'One or more selected sections do not exist.',
            'questions.required' => 'Please add at least one question.',
            'questions.min' => 'Please add at least one question.',
            'questions.*.type.required' => 'Question type is required.',
            'questions.*.type.in' => 'Question type must be multiple choice, identification, or true/false.',
            'questions.*.question.required' => 'Question text is required.',
            'questions.*.choices.required_if' => 'Multiple choice questions must have at least 4 choices.',
            'questions.*.choices.min' => 'Multiple choice questions must have at least 4 choices.',
            'questions.*.correct_answer.required' => 'Correct answer is required.',
        ];
    }
}
