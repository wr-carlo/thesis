<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GroqProvider implements AIServiceInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->model = $config['model'];
        $this->timeout = $config['timeout'];
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

    protected function buildPrompt(string $content, string $previousContext, array $options): array
    {
        $distribution = $options['question_distribution'] ?? [];
        $bloomLevels = $options['bloom_levels'] ?? ['remember', 'understand'];

        $totalMcq = 0;
        $totalId = 0;
        $totalTf = 0;

        foreach ($distribution as $counts) {
            $totalMcq += $counts['mcq'] ?? 0;
            $totalId += $counts['identification'] ?? 0;
            $totalTf += $counts['tf'] ?? 0;
        }

        $systemPrompt = "You are an expert assessment generator aligned with Bloom's Taxonomy. "
            . "Generate educational assessment questions based on the provided lesson content. "
            . "Each question must be tagged with its appropriate Bloom's Taxonomy cognitive level. "
            . "Always respond with valid JSON only.";

        $userPrompt = "Generate assessment questions based on this lesson content:\n\n";

        if (!empty($previousContext)) {
            $userPrompt .= "IMPORTANT: The following sections have already been covered. Avoid duplicating questions from these topics:\n\n";
            $userPrompt .= $previousContext . "\n\n";
            $userPrompt .= "Current section to generate questions from:\n\n";
        }

        $userPrompt .= $content . "\n\n";
        $userPrompt .= "Question Requirements (Total):\n";
        $userPrompt .= "- Multiple Choice: {$totalMcq} questions\n";
        $userPrompt .= "- Identification: {$totalId} questions\n";
        $userPrompt .= "- True/False: {$totalTf} questions\n";

        // Add Bloom's Taxonomy instructions with specific distribution mapping
        $userPrompt .= BloomsTaxonomyConfig::getPromptInstructions($distribution);

        $userPrompt .= "Return ONLY a valid JSON response with this exact structure:\n";
        $userPrompt .= BloomsTaxonomyConfig::getJsonStructure();

        return [
            'system' => $systemPrompt,
            'user' => $userPrompt,
        ];
    }

    protected function makeRequest(array $prompt): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $prompt['system'],
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt['user'],
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                ]);

            if (!$response->successful()) {
                throw new \Exception('Groq API request failed: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid response structure from Groq');
            }

            $content = $data['choices'][0]['message']['content'];
            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from Groq: ' . json_last_error_msg());
            }

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Groq Provider Error: ' . $e->getMessage());
        }
    }
}
