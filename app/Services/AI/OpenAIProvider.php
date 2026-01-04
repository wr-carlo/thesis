<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OpenAIProvider implements AIServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai_models.providers.openai.api_key');
        $this->model = config('ai_models.primary_model');
        $this->timeout = config('ai_models.timeout');
    }

    public function generateAssessment(string $content, array $options = []): array
    {
        $prompt = $this->buildPrompt($content, '', $options);
        
        return $this->makeRequest($prompt);
    }

    public function generateChunk(string $chunkContent, string $previousContext, array $options = []): array
    {
        $prompt = $this->buildPrompt($chunkContent, $previousContext, $options);
        
        return $this->makeRequest($prompt);
    }

    protected function buildPrompt(string $content, string $previousContext, array $options): string
    {
        $multipleChoiceCount = $options['multiple_choice_count'] ?? 0;
        $identificationCount = $options['identification_count'] ?? 0;
        $trueOrFalseCount = $options['true_or_false_count'] ?? 0;
        $difficulty = $options['difficulty'] ?? 'medium';

        $systemPrompt = "You are an expert assessment generator. Generate educational assessment questions based on the provided lesson content.";

        $userPrompt = "Generate assessment questions based on this lesson content:\n\n";

        if (!empty($previousContext)) {
            $userPrompt .= "IMPORTANT: The following sections have already been covered. Avoid duplicating questions from these topics:\n\n";
            $userPrompt .= $previousContext . "\n\n";
            $userPrompt .= "Current section to generate questions from:\n\n";
        }

        $userPrompt .= $content . "\n\n";
        $userPrompt .= "Question Requirements:\n";
        $userPrompt .= "- Multiple Choice: {$multipleChoiceCount} questions\n";
        $userPrompt .= "- Identification: {$identificationCount} questions\n";
        $userPrompt .= "- True/False: {$trueOrFalseCount} questions\n";
        $userPrompt .= "- Difficulty Level: {$difficulty}\n\n";

        $userPrompt .= "Return ONLY a valid JSON response with this exact structure:\n";
        $userPrompt .= "{\n";
        $userPrompt .= '  "multiple_choice": [' . "\n";
        $userPrompt .= '    {' . "\n";
        $userPrompt .= '      "question": "Question text here",' . "\n";
        $userPrompt .= '      "choices": ["Choice A", "Choice B", "Choice C", "Choice D"],' . "\n";
        $userPrompt .= '      "correct_answer": "Choice A"' . "\n";
        $userPrompt .= '    }' . "\n";
        $userPrompt .= '  ],' . "\n";
        $userPrompt .= '  "identification": [' . "\n";
        $userPrompt .= '    {' . "\n";
        $userPrompt .= '      "question": "Question text here",' . "\n";
        $userPrompt .= '      "correct_answer": "Answer text"' . "\n";
        $userPrompt .= '    }' . "\n";
        $userPrompt .= '  ],' . "\n";
        $userPrompt .= '  "true_or_false": [' . "\n";
        $userPrompt .= '    {' . "\n";
        $userPrompt .= '      "question": "Statement text here",' . "\n";
        $userPrompt .= '      "correct_answer": "True" or "False"' . "\n";
        $userPrompt .= '    }' . "\n";
        $userPrompt .= '  ]' . "\n";
        $userPrompt .= "}\n";

        return json_encode([
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ]);
    }

    protected function makeRequest(string $prompt): array
    {
        $promptData = json_decode($prompt, true);

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $promptData['system'],
                        ],
                        [
                            'role' => 'user',
                            'content' => $promptData['user'],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response structure from OpenAI');
            }

            $content = $data['choices'][0]['message']['content'];
            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from OpenAI: ' . json_last_error_msg());
            }

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('OpenAI Provider Error: ' . $e->getMessage());
        }
    }
}

