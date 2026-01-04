<?php

namespace App\Services\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentItem;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;

class AssessmentGenerator
{
    /**
     * Generate and save assessment from AI response.
     *
     * @param Lesson $lesson
     * @param array $aiResponse
     * @param array $config
     * @return Assessment
     * @throws \Exception
     */
    public function generate(Lesson $lesson, array $aiResponse, array $config = []): Assessment
    {
        DB::beginTransaction();

        try {
            // Create assessment record
            $assessment = Assessment::create([
                'lesson_id' => $lesson->id,
                'title' => $config['title'] ?? "Assessment for {$lesson->title}",
                'type' => $config['type'] ?? 'quiz',
                'status' => 'draft',
            ]);

            // Save multiple choice questions
            if (isset($aiResponse['multiple_choice']) && is_array($aiResponse['multiple_choice'])) {
                foreach ($aiResponse['multiple_choice'] as $question) {
                    AssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'question' => $question['question'],
                        'type' => 'multiple_choice',
                        'choices' => json_encode($question['choices']),
                        'correct_answer' => $question['correct_answer'],
                    ]);
                }
            }

            // Save identification questions
            if (isset($aiResponse['identification']) && is_array($aiResponse['identification'])) {
                foreach ($aiResponse['identification'] as $question) {
                    AssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'question' => $question['question'],
                        'type' => 'identification',
                        'choices' => null,
                        'correct_answer' => $question['correct_answer'],
                    ]);
                }
            }

            // Save true/false questions
            if (isset($aiResponse['true_or_false']) && is_array($aiResponse['true_or_false'])) {
                foreach ($aiResponse['true_or_false'] as $question) {
                    AssessmentItem::create([
                        'assessment_id' => $assessment->id,
                        'question' => $question['question'],
                        'type' => 'true_or_false',
                        'choices' => null,
                        'correct_answer' => $question['correct_answer'],
                    ]);
                }
            }

            DB::commit();

            // Reload with items
            $assessment->load('items');

            return $assessment;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Failed to generate assessment: ' . $e->getMessage());
        }
    }
}

