<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class AnthropicProvider implements AIServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai_models.providers.anthropic.api_key');
        $this->model = 'claude-3-5-sonnet-20241022';
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

        $prompt = "Generate assessment questions based on this lesson content:\n\n";

        if (!empty($previousContext)) {
            $prompt .= "IMPORTANT: The following sections have already been covered. Avoid duplicating questions from these topics:\n\n";
            $prompt .= $previousContext . "\n\n";
            $prompt .= "Current section to generate questions from:\n\n";
        }

        $prompt .= $content . "\n\n";
        $prompt .= "Question Requirements:\n";
        $prompt .= "- Multiple Choice: {$multipleChoiceCount} questions\n";
        $prompt .= "- Identification: {$identificationCount} questions\n";
        $prompt .= "- True/False: {$trueOrFalseCount} questions\n";
        $prompt .= "- Difficulty Level: {$difficulty}\n\n";

        $prompt .= "Return ONLY a valid JSON response with this exact structure:\n";
        $prompt .= "{\n";
        $prompt .= '  "multiple_choice": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "question": "Question text here",' . "\n";
        $prompt .= '      "choices": ["Choice A", "Choice B", "Choice C", "Choice D"],' . "\n";
        $prompt .= '      "correct_answer": "Choice A"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "identification": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "question": "Question text here",' . "\n";
        $prompt .= '      "correct_answer": "Answer text"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ],' . "\n";
        $prompt .= '  "true_or_false": [' . "\n";
        $prompt .= '    {' . "\n";
        $prompt .= '      "question": "Statement text here",' . "\n";
        $prompt .= '      "correct_answer": "True" or "False"' . "\n";
        $prompt .= '    }' . "\n";
        $prompt .= '  ]' . "\n";
        $prompt .= "}\n";

        return $prompt;
    }

    protected function makeRequest(string $prompt): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => $this->model,
                    'max_tokens' => 4096,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Anthropic API request failed: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['content'][0]['text'])) {
                throw new \Exception('Invalid response structure from Anthropic');
            }

            $content = $data['content'][0]['text'];
            
            // Extract JSON from response (Claude sometimes wraps it in markdown)
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            }

            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from Anthropic: ' . json_last_error_msg());
            }

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Anthropic Provider Error: ' . $e->getMessage());
        }
    }
}

