<?php

namespace App\Services\AI;

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
        // Validate structure
        if (!isset($response['multiple_choice']) || 
            !isset($response['identification']) || 
            !isset($response['true_or_false'])) {
            throw new \Exception('Invalid AI response structure: missing required keys');
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
            'multiple_choice' => $response['multiple_choice'],
            'identification' => $response['identification'],
            'true_or_false' => $response['true_or_false'],
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
}

