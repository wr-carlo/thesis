<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements AIServiceInterface
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

    protected function buildPrompt(string $content, string $previousContext, array $options): string
    {
        $multipleChoiceCount = $options['multiple_choice_count'] ?? 0;
        $identificationCount = $options['identification_count'] ?? 0;
        $trueOrFalseCount = $options['true_or_false_count'] ?? 0;
        $bloomLevels = $options['bloom_levels'] ?? ['remember', 'understand'];

        $prompt = "You are an expert assessment generator aligned with Bloom's Taxonomy. Generate educational assessment questions based on the provided lesson content.\n\n";

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

        // Add Bloom's Taxonomy instructions
        $prompt .= BloomsTaxonomyConfig::getPromptInstructions($bloomLevels);

        $prompt .= "Return ONLY a valid JSON response with this exact structure:\n";
        $prompt .= BloomsTaxonomyConfig::getJsonStructure();

        return $prompt;
    }

    protected function makeRequest(string $prompt): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 4096,
                    ],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Gemini API request failed: ' . $response->body());
            }

            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Invalid response structure from Gemini');
            }

            $content = $data['candidates'][0]['content']['parts'][0]['text'];

            // Extract JSON from response (Gemini sometimes wraps it in markdown)
            if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
                $content = $matches[1];
            }

            $result = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from Gemini: ' . json_last_error_msg());
            }

            return $result;

        } catch (\Exception $e) {
            throw new \Exception('Gemini Provider Error: ' . $e->getMessage());
        }
    }
}
