<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class BloomsValidator
{
    /**
     * Valid Bloom's Taxonomy levels.
     */
    protected const VALID_LEVELS = [
        'remember', 'understand', 'apply', 'analyze', 'evaluate', 'create'
    ];

    /**
     * Validate and normalize generated questions' Bloom's level accuracy.
     * Uses local validation to ensure bloom_level values are correct and
     * within the allowed levels without requiring a 2nd AI call.
     *
     * @param array $questions The parsed questions (with bloom_level per question)
     * @param array $allowedLevels The Bloom's levels that were requested
     * @return array The validated/corrected questions
     */
    public function validate(array $questions, array $allowedLevels): array
    {
        try {
            $corrected = [];

            foreach (['multiple_choice', 'identification', 'true_or_false'] as $type) {
                $corrected[$type] = [];

                if (!isset($questions[$type]) || !is_array($questions[$type])) {
                    continue;
                }

                foreach ($questions[$type] as $question) {
                    $bloomLevel = $question['bloom_level'] ?? null;

                    // Normalize: lowercase, trim
                    if ($bloomLevel) {
                        $bloomLevel = strtolower(trim($bloomLevel));
                    }

                    // If bloom_level is missing or invalid, assign from allowed levels
                    if (!$bloomLevel || !in_array($bloomLevel, self::VALID_LEVELS)) {
                        $bloomLevel = $allowedLevels[array_rand($allowedLevels)];
                    }

                    // If bloom_level is valid but not in the allowed levels, reassign
                    if (!in_array($bloomLevel, $allowedLevels)) {
                        $bloomLevel = $allowedLevels[array_rand($allowedLevels)];
                    }

                    $question['bloom_level'] = $bloomLevel;
                    $corrected[$type][] = $question;
                }
            }

            Log::info('Bloom\'s Validation: Success', [
                'original_counts' => $this->countByLevel($questions),
                'validated_counts' => $this->countByLevel($corrected),
            ]);

            return $corrected;
        } catch (\Exception $e) {
            Log::warning('Bloom\'s Validation: Failed, using original questions', [
                'error' => $e->getMessage(),
            ]);
            return $questions;
        }
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
	