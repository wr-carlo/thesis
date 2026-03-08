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
        if (isset($options['is_adaptive']) && $options['is_adaptive']) {
            return $this->buildAdaptivePrompt($content, $previousContext, $options);
        }

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

        $prompt = "You are an expert assessment generator aligned with Bloom's Taxonomy. Generate educational assessment questions based on the provided lesson content.\n\n";

        if (!empty($previousContext)) {
            $prompt .= "IMPORTANT: The following sections have already been covered. Avoid duplicating questions from these topics:\n\n";
            $prompt .= $previousContext . "\n\n";
            $prompt .= "Current section to generate questions from:\n\n";
        }

        $prompt .= $content . "\n\n";
        $prompt .= "Question Requirements (Total):\n";
        $prompt .= "- Multiple Choice: {$totalMcq} questions\n";
        $prompt .= "- Identification: {$totalId} questions\n";
        $prompt .= "- True/False: {$totalTf} questions\n";

        // Add Bloom's Taxonomy instructions with specific distribution mapping
        $prompt .= BloomsTaxonomyConfig::getPromptInstructions($distribution);

        $prompt .= "Return ONLY a valid JSON response with this exact structure:\n";
        $prompt .= BloomsTaxonomyConfig::getJsonStructure();

        return $prompt;
    }

    protected function buildAdaptivePrompt(string $content, string $previousContext, array $options): string
    {
        $totalMcq = $options['multiple_choice_count'] ?? 0;
        $totalId = $options['identification_count'] ?? 0;
        $totalTf = $options['true_or_false_count'] ?? 0;

        $prompt = "You are an expert, empathetic educational assessment generator. "
            . "Generate an adaptive practice assessment to help a student overcome specific learning gaps. "
            . "You have been provided with the student's previous mistakes and the corresponding lesson content. "
            . "Analyze the mistakes, determine the core misunderstandings, and generate questions that will test those concepts "
            . "and gently build their understanding, without being overly punitive. "
            . "Assign the most appropriate Bloom's Taxonomy cognitive level to each question based on what the student needs.\n\n";

        $prompt .= "Generate an adaptive assessment based on the following student performance and lesson content:\n\n";

        if (!empty($previousContext)) {
            $prompt .= "IMPORTANT: The following sections have already been generated in a previous chunk. Avoid duplicating topics:\n\n";
            $prompt .= $previousContext . "\n\n";
            $prompt .= "Current section to generate questions from:\n\n";
        }

        $prompt .= $content . "\n\n";
        $prompt .= "Question Requirements (Total to generate):\n";
        $prompt .= "- Multiple Choice: {$totalMcq} questions\n";
        $prompt .= "- Identification: {$totalId} questions\n";
        $prompt .= "- True/False: {$totalTf} questions\n\n";

        $prompt .= "Instructions:\n";
        $prompt .= "1. Read the STUDENT'S WRONG ANSWERS carefully to identify their knowledge gaps.\n";
        $prompt .= "2. Cross-reference these gaps with the LESSON CONTENT.\n";
        $prompt .= "3. Create NEW questions that target these specific gaps. Do not just repeat the old questions.\n";
        $prompt .= "4. For each question, decide the best 'bloom_level' (remember, understand, apply, analyze, evaluate, create) that fits the concept.\n\n";

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
