<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class BloomsValidator
{
    /**
     * Validate generated questions' Bloom's level accuracy using a 2nd AI call.
     *
     * @param array $questions The parsed questions (with bloom_level per question)
     * @param array $allowedLevels The Bloom's levels that were requested
     * @param AIServiceInterface $provider The AI provider to use for validation
     * @return array The validated/corrected questions
     */
    public function validate(array $questions, array $allowedLevels, AIServiceInterface $provider): array
    {
        try {
            $prompt = $this->buildValidationPrompt($questions, $allowedLevels);
            $result = $provider->generateAssessment('', $prompt);

            // Validate the structure of the returned result
            if ($this->isValidStructure($result)) {
                Log::info('Bloom\'s Validation: Success', [
                    'original_questions' => $this->countByLevel($questions),
                    'validated_questions' => $this->countByLevel($result),
                ]);
                return $result;
            }

            Log::warning('Bloom\'s Validation: Invalid response structure, using original questions');
            return $questions;
        } catch (\Exception $e) {
            Log::warning('Bloom\'s Validation: Failed, using original questions', [
                'error' => $e->getMessage(),
            ]);
            return $questions;
        }
    }

    /**
     * Build the validation prompt.
     */
    protected function buildValidationPrompt(array $questions, array $allowedLevels): array
    {
        $questionsJson = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $allowedLevelsStr = implode(', ', $allowedLevels);

        $systemPrompt = "You are a Bloom's Taxonomy expert and educational assessment validator. "
            . "Your job is to review assessment questions and verify that each question's bloom_level tag accurately reflects its cognitive level. "
            . "Always respond with valid JSON only.";

        $userPrompt = "Review the following assessment questions and verify if each question's bloom_level tag is accurate.\n\n";
        $userPrompt .= "Bloom's Taxonomy Levels (for reference):\n";
        $userPrompt .= "- remember: Recall facts, terms, definitions, basic concepts\n";
        $userPrompt .= "- understand: Explain, summarize, interpret, paraphrase ideas\n";
        $userPrompt .= "- apply: Use knowledge to solve problems in new situations\n";
        $userPrompt .= "- analyze: Compare, contrast, examine relationships, break down\n";
        $userPrompt .= "- evaluate: Judge, justify, critique, assess, defend decisions\n";
        $userPrompt .= "- create: Design, propose, construct, formulate new ideas\n\n";
        $userPrompt .= "The ALLOWED levels for this assessment are: [{$allowedLevelsStr}]\n\n";
        $userPrompt .= "Questions to validate:\n{$questionsJson}\n\n";
        $userPrompt .= "Instructions:\n";
        $userPrompt .= "1. For each question, check if the bloom_level is accurate\n";
        $userPrompt .= "2. If a question's bloom_level is WRONG, correct the bloom_level to the right one\n";
        $userPrompt .= "3. If a question's bloom_level is OUTSIDE the allowed levels [{$allowedLevelsStr}], REWRITE the question to match one of the allowed levels and update the bloom_level accordingly\n";
        $userPrompt .= "4. Keep the same JSON structure exactly as provided\n";
        $userPrompt .= "5. Do NOT change questions that are already correctly tagged\n\n";
        $userPrompt .= "Return ONLY the corrected JSON in the exact same structure. Do not add explanations.\n";

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    /**
     * Check if the AI response has a valid structure.
     */
    protected function isValidStructure(array $result): bool
    {
        return isset($result['multiple_choice']) && is_array($result['multiple_choice'])
            && isset($result['identification']) && is_array($result['identification'])
            && isset($result['true_or_false']) && is_array($result['true_or_false']);
    }

    /**
     * Count questions by Bloom's level for logging.
     */
    protected function countByLevel(array $questions): array
    {
        $counts = [];

        foreach (['multiple_choice', 'identification', 'true_or_false'] as $type) {
            if (isset($questions[$type]) && is_array($questions[$type])) {
                foreach ($questions[$type] as $q) {
                    $level = $q['bloom_level'] ?? 'untagged';
                    $counts[$level] = ($counts[$level] ?? 0) + 1;
                }
            }
        }

        return $counts;
    }
}
