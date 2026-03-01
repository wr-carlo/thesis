<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

class AIResponseParser
{
    /**
     * Parse and validate AI response.
     *
     * @param array $response
     * @return array
     * @throws \Exception
     */
    public function parse(array $response): array
    {
        Log::info('AIResponseParser: Received data for parsing', [
            'keys' => array_keys($response),
            'multiple_choice_count' => count($response['multiple_choice'] ?? []),
            'identification_count' => count($response['identification'] ?? []),
            'true_or_false_count' => count($response['true_or_false'] ?? []),
        ]);

        // Validate structure
        if (!isset($response['multiple_choice']) || 
            !isset($response['identification']) || 
            !isset($response['true_or_false'])) {
            Log::error('AIResponseParser: Missing required keys', [
                'has_multiple_choice' => isset($response['multiple_choice']),
                'has_identification' => isset($response['identification']),
                'has_true_or_false' => isset($response['true_or_false']),
                'actual_keys' => array_keys($response),
            ]);
            throw new \Exception('Invalid AI response structure: missing required keys. Keys found: ' . implode(', ', array_keys($response)));
        }

        // Validate arrays
        if (!is_array($response['multiple_choice']) || 
            !is_array($response['identification']) || 
            !is_array($response['true_or_false'])) {
            throw new \Exception('Invalid AI response structure: values must be arrays');
        }

        // Validate each question type
        $this->validateMultipleChoiceQuestions($response['multiple_choice']);
        $this->validateIdentificationQuestions($response['identification']);
        $this->validateTrueOrFalseQuestions($response['true_or_false']);

        return [
            'multiple_choice' => $this->normalizeBloomLevels($response['multiple_choice']),
            'identification' => $this->normalizeBloomLevels($response['identification']),
            'true_or_false' => $this->normalizeBloomLevels($response['true_or_false']),
        ];
    }

    protected function validateMultipleChoiceQuestions(array $questions): void
    {
        foreach ($questions as $index => $question) {
            if (!isset($question['question']) || !is_string($question['question'])) {
                throw new \Exception("Multiple choice question {$index}: missing or invalid 'question' field");
            }

            if (!isset($question['choices']) || !is_array($question['choices'])) {
                throw new \Exception("Multiple choice question {$index}: missing or invalid 'choices' field");
            }

            if (count($question['choices']) < 2) {
                throw new \Exception("Multiple choice question {$index}: must have at least 2 choices");
            }

            if (!isset($question['correct_answer']) || !is_string($question['correct_answer'])) {
                throw new \Exception("Multiple choice question {$index}: missing or invalid 'correct_answer' field");
            }
        }
    }

    protected function validateIdentificationQuestions(array $questions): void
    {
        foreach ($questions as $index => $question) {
            if (!isset($question['question']) || !is_string($question['question'])) {
                throw new \Exception("Identification question {$index}: missing or invalid 'question' field");
            }

            if (!isset($question['correct_answer']) || !is_string($question['correct_answer'])) {
                throw new \Exception("Identification question {$index}: missing or invalid 'correct_answer' field");
            }
        }
    }

    protected function validateTrueOrFalseQuestions(array $questions): void
    {
        foreach ($questions as $index => $question) {
            if (!isset($question['question']) || !is_string($question['question'])) {
                throw new \Exception("True/False question {$index}: missing or invalid 'question' field");
            }

            if (!isset($question['correct_answer']) || !is_string($question['correct_answer'])) {
                throw new \Exception("True/False question {$index}: missing or invalid 'correct_answer' field");
            }

            // Validate that answer is True or False
            $answer = strtolower(trim($question['correct_answer']));
            if (!in_array($answer, ['true', 'false'])) {
                throw new \Exception("True/False question {$index}: correct_answer must be 'True' or 'False'");
            }
        }
    }

    /**
     * Normalize bloom_level fields in questions.
     * If bloom_level is missing, set to null. If present, validate it.
     */
    protected function normalizeBloomLevels(array $questions): array
    {
        $validLevels = BloomsTaxonomyConfig::getValidLevels();

        return array_map(function ($question) use ($validLevels) {
            if (isset($question['bloom_level']) && is_string($question['bloom_level'])) {
                $level = strtolower(trim($question['bloom_level']));
                $question['bloom_level'] = in_array($level, $validLevels) ? $level : null;
            } else {
                $question['bloom_level'] = null;
            }
            return $question;
        }, $questions);
    }
}
