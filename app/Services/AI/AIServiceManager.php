<?php

namespace App\Services\AI;

use App\Services\ContentProcessing\ContentChunker;
use App\Services\ContentProcessing\ContextSummarizer;
use App\Services\ContentProcessing\TokenCalculator;

class AIServiceManager
{
    protected array $providers = [];
    protected ContentChunker $chunker;
    protected ContextSummarizer $summarizer;
    protected TokenCalculator $tokenCalculator;
    protected BloomsValidator $bloomsValidator;

    public function __construct(
        ContentChunker $chunker,
        ContextSummarizer $summarizer,
        TokenCalculator $tokenCalculator,
        BloomsValidator $bloomsValidator
    ) {
        $this->chunker = $chunker;
        $this->summarizer = $summarizer;
        $this->tokenCalculator = $tokenCalculator;
        $this->bloomsValidator = $bloomsValidator;

        $this->initializeProviders();
    }

    /**
     * Initialize providers in fallback order.
     */
    protected function initializeProviders(): void
    {
        $fallbackOrder = config('ai_models.fallback_order');

        foreach ($fallbackOrder as $providerName) {
            $this->providers[$providerName] = $this->createProvider($providerName);
        }
    }

    /**
     * Create a provider instance.
     */
    protected function createProvider(string $providerName): AIServiceInterface
    {
        $config = $this->getProviderConfig($providerName);

        return match ($providerName) {
            'openai' => new OpenAIProvider($config),
            'groq' => new GroqProvider($config),
            'gemini' => new GeminiProvider($config),
            default => throw new \Exception("Unknown provider: {$providerName}"),
        };
    }

    /**
     * Get normalized configuration for a provider.
     */
    public function getProviderConfig(string $providerName): array
    {
        $providerConfig = config("ai_models.providers.{$providerName}");

        if (!$providerConfig) {
            throw new \Exception("Provider config not found: {$providerName}");
        }

        return [
            'name' => $providerName,
            'api_key' => $providerConfig['api_key'] ?? '',
            'model' => $providerConfig['model'] ?? '',
            'max_input_tokens' => $providerConfig['limits']['max_input_tokens'] ?? 8000,
            'max_output_tokens' => $providerConfig['limits']['max_output_tokens'] ?? 4096,
            'safe_limit' => $providerConfig['limits']['safe_limit'] ?? 6000,
            'timeout' => config('ai_models.timeout', 120),
        ];
    }

    /**
     * Get the primary provider's safe limit for chunking decisions.
     */
    protected function getPrimarySafeLimit(): int
    {
        $primaryProvider = config('ai_models.primary_provider');
        $config = $this->getProviderConfig($primaryProvider);

        return $config['safe_limit'];
    }

    /**
     * Main entry point for generating assessments.
     */
    public function generateAssessment(string $content, array $config): array
    {
        $safeLimit = $this->getPrimarySafeLimit();
        $contentTokens = $this->tokenCalculator->estimateTokens($content);

        if ($this->tokenCalculator->fitsInModel($contentTokens, $safeLimit)) {
            return $this->processSingleRequest($content, $config);
        }

        return $this->processChunks($content, $config, $safeLimit);
    }

    /**
     * Process content in a single request (no chunking).
     */
    protected function processSingleRequest(string $content, array $config): array
    {
        $lastException = null;
        $fallbackOrder = config('ai_models.fallback_order');
        $usedProvider = null;

        foreach ($fallbackOrder as $providerName) {
            $provider = $this->providers[$providerName];

            try {
                $result = $provider->generateAssessment($content, $config);
                $usedProvider = $providerName;

                // Run Bloom's validation if bloom_levels are specified
                $bloomLevels = $config['bloom_levels'] ?? null;
                if ($bloomLevels) {
                    $result = $this->bloomsValidator->validate($result, $bloomLevels, $provider);
                }

                return [
                    'success' => true,
                    'data' => $result,
                    'provider_used' => $providerName,
                    'chunks_processed' => 1,
                ];

            } catch (\Exception $e) {
                // Retry once before moving to next provider
                try {
                    $result = $provider->generateAssessment($content, $config);
                    $usedProvider = $providerName;

                    // Run Bloom's validation on retry result too
                    $bloomLevels = $config['bloom_levels'] ?? null;
                    if ($bloomLevels) {
                        $result = $this->bloomsValidator->validate($result, $bloomLevels, $provider);
                    }

                    return [
                        'success' => true,
                        'data' => $result,
                        'provider_used' => $providerName,
                        'chunks_processed' => 1,
                        'retry_used' => true,
                    ];

                } catch (\Exception $retryException) {
                    $lastException = $retryException;
                    continue;
                }
            }
        }

        throw new \Exception('All AI providers failed: ' . ($lastException?->getMessage() ?? 'Unknown error'));
    }

    /**
     * Process content with chunking.
     */
    protected function processChunks(string $content, array $config, int $safeLimit): array
    {
        $bufferTokens = config('ai_models.chunking.buffer_tokens');
        $overlapPercentage = config('ai_models.chunking.overlap_percentage');

        $chunkingResult = $this->chunker->chunk($content, $safeLimit, $bufferTokens, $overlapPercentage);
        $chunks = $chunkingResult['chunks'];
        $totalChunks = $chunkingResult['total_chunks'];

        $totalQuestions = ($config['multiple_choice_count'] ?? 0)
            + ($config['identification_count'] ?? 0)
            + ($config['true_or_false_count'] ?? 0);

        $questionsPerChunk = $this->chunker->distributeQuestions($totalQuestions, $totalChunks);

        $lastException = null;
        $fallbackOrder = config('ai_models.fallback_order');

        foreach ($fallbackOrder as $providerName) {
            $provider = $this->providers[$providerName];

            try {
                $allResults = $this->processAllChunksWithProvider(
                    $provider,
                    $chunks,
                    $config,
                    $questionsPerChunk
                );

                $combinedResult = $this->combineChunkResults($allResults);

                // Run Bloom's validation on combined results
                $bloomLevels = $config['bloom_levels'] ?? null;
                if ($bloomLevels) {
                    $combinedResult = $this->bloomsValidator->validate($combinedResult, $bloomLevels, $provider);
                }

                return [
                    'success' => true,
                    'data' => $combinedResult,
                    'provider_used' => $providerName,
                    'chunks_processed' => $totalChunks,
                ];

            } catch (\Exception $e) {
                $lastException = $e;
                continue;
            }
        }

        throw new \Exception('All AI providers failed to process chunks: ' . ($lastException?->getMessage() ?? 'Unknown error'));
    }

    /**
     * Process all chunks with a single provider.
     */
    protected function processAllChunksWithProvider(
        AIServiceInterface $provider,
        array $chunks,
        array $config,
        array $questionsPerChunk
    ): array {
        $allResults = [];
        $previousSummaries = [];

        foreach ($chunks as $index => $chunk) {
            $chunkNumber = $index + 1;
            $chunkContent = $chunk['content'];

            $previousContext = $this->summarizer->combineSummaries($previousSummaries);

            $chunkConfig = $this->distributeQuestionsForChunk(
                $config,
                $questionsPerChunk[$index]
            );

            try {
                $result = $provider->generateChunk($chunkContent, $previousContext, $chunkConfig);

                $allResults[] = $result;
                $previousSummaries[] = $this->summarizer->summarizeChunk($chunkContent);

            } catch (\Exception $e) {
                // Retry once
                try {
                    $result = $provider->generateChunk($chunkContent, $previousContext, $chunkConfig);

                    $allResults[] = $result;
                    $previousSummaries[] = $this->summarizer->summarizeChunk($chunkContent);

                } catch (\Exception $retryException) {
                    throw new \Exception("Chunk {$chunkNumber} failed after retry: " . $retryException->getMessage());
                }
            }
        }

        return $allResults;
    }

    /**
     * Distribute questions for a single chunk proportionally.
     */
    protected function distributeQuestionsForChunk(array $config, int $totalQuestionsForChunk): array
    {
        $mcCount = $config['multiple_choice_count'] ?? 0;
        $idCount = $config['identification_count'] ?? 0;
        $tfCount = $config['true_or_false_count'] ?? 0;

        $totalRequested = $mcCount + $idCount + $tfCount;

        if ($totalRequested === 0) {
            return $config;
        }

        $mcForChunk = (int) round(($mcCount / $totalRequested) * $totalQuestionsForChunk);
        $idForChunk = (int) round(($idCount / $totalRequested) * $totalQuestionsForChunk);
        $tfForChunk = $totalQuestionsForChunk - $mcForChunk - $idForChunk;

        return [
            'multiple_choice_count' => max(0, $mcForChunk),
            'identification_count' => max(0, $idForChunk),
            'true_or_false_count' => max(0, $tfForChunk),
            'bloom_levels' => $config['bloom_levels'] ?? ['remember', 'understand'],
        ];
    }

    /**
     * Combine results from all chunks into a single result.
     */
    protected function combineChunkResults(array $allResults): array
    {
        $combined = [
            'multiple_choice' => [],
            'identification' => [],
            'true_or_false' => [],
        ];

        foreach ($allResults as $result) {
            if (isset($result['multiple_choice'])) {
                $combined['multiple_choice'] = array_merge(
                    $combined['multiple_choice'],
                    $result['multiple_choice']
                );
            }

            if (isset($result['identification'])) {
                $combined['identification'] = array_merge(
                    $combined['identification'],
                    $result['identification']
                );
            }

            if (isset($result['true_or_false'])) {
                $combined['true_or_false'] = array_merge(
                    $combined['true_or_false'],
                    $result['true_or_false']
                );
            }
        }

        return $combined;
    }
}
