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

    public function __construct(
        ContentChunker $chunker,
        ContextSummarizer $summarizer,
        TokenCalculator $tokenCalculator
    ) {
        $this->chunker = $chunker;
        $this->summarizer = $summarizer;
        $this->tokenCalculator = $tokenCalculator;

        // Initialize providers in fallback order
        $fallbackOrder = config('ai_models.fallback_order');
        foreach ($fallbackOrder as $providerName) {
            $this->providers[] = $this->createProvider($providerName);
        }
    }

    protected function createProvider(string $providerName): AIServiceInterface
    {
        return match ($providerName) {
            'openai' => new OpenAIProvider(),
            'anthropic' => new AnthropicProvider(),
            'gemini' => new GeminiProvider(),
            default => throw new \Exception("Unknown provider: {$providerName}"),
        };
    }

    /**
     * Main entry point for generating assessments.
     *
     * @param string $content
     * @param array $config
     * @return array
     */
    public function generateAssessment(string $content, array $config): array
    {
        // Get primary model configuration
        $primaryProvider = config('ai_models.primary_provider');
        $primaryModel = config('ai_models.primary_model');
        $modelConfig = config("ai_models.providers.{$primaryProvider}.models.{$primaryModel}");

        // Check if chunking is needed
        $contentTokens = $this->tokenCalculator->estimateTokens($content);
        $modelSafeLimit = $modelConfig['safe_limit'];

        if ($this->tokenCalculator->fitsInModel($contentTokens, $modelSafeLimit)) {
            // No chunking needed - single request
            return $this->processSingleRequest($content, $config);
        } else {
            // Chunking needed
            return $this->processChunks($content, $config, $modelSafeLimit);
        }
    }

    /**
     * Process content in a single request (no chunking).
     *
     * @param string $content
     * @param array $config
     * @return array
     */
    protected function processSingleRequest(string $content, array $config): array
    {
        $lastException = null;

        // Try each provider
        foreach ($this->providers as $index => $provider) {
            $providerName = config('ai_models.fallback_order')[$index];

            try {
                // Try generating
                $result = $provider->generateAssessment($content, $config);
                
                // Success! Return with provider info
                return [
                    'success' => true,
                    'data' => $result,
                    'provider_used' => $providerName,
                    'chunks_processed' => 1,
                ];

            } catch (\Exception $e) {
                // First attempt failed, try retry
                try {
                    $result = $provider->generateAssessment($content, $config);
                    
                    // Retry success!
                    return [
                        'success' => true,
                        'data' => $result,
                        'provider_used' => $providerName,
                        'chunks_processed' => 1,
                        'retry_used' => true,
                    ];

                } catch (\Exception $e2) {
                    // Retry also failed, try next provider
                    $lastException = $e2;
                    continue;
                }
            }
        }

        // All providers failed
        throw new \Exception('All AI providers failed: ' . $lastException->getMessage());
    }

    /**
     * Process content with chunking.
     *
     * @param string $content
     * @param array $config
     * @param int $modelSafeLimit
     * @return array
     */
    protected function processChunks(string $content, array $config, int $modelSafeLimit): array
    {
        // Get chunking configuration
        $bufferTokens = config('ai_models.chunking.buffer_tokens');
        $overlapPercentage = config('ai_models.chunking.overlap_percentage');

        // Split content into chunks
        $chunkingResult = $this->chunker->chunk($content, $modelSafeLimit, $bufferTokens, $overlapPercentage);
        $chunks = $chunkingResult['chunks'];
        $totalChunks = $chunkingResult['total_chunks'];

        // Calculate questions per chunk
        $totalQuestions = ($config['multiple_choice_count'] ?? 0) + 
                         ($config['identification_count'] ?? 0) + 
                         ($config['true_or_false_count'] ?? 0);
        
        $questionsPerChunk = $this->chunker->distributeQuestions($totalQuestions, $totalChunks);

        $lastException = null;

        // Try each provider
        foreach ($this->providers as $index => $provider) {
            $providerName = config('ai_models.fallback_order')[$index];

            try {
                // Process all chunks with this provider
                $allResults = $this->processAllChunksWithProvider(
                    $provider,
                    $chunks,
                    $config,
                    $questionsPerChunk
                );

                // All chunks succeeded!
                return [
                    'success' => true,
                    'data' => $this->combineChunkResults($allResults),
                    'provider_used' => $providerName,
                    'chunks_processed' => $totalChunks,
                ];

            } catch (\Exception $e) {
                // This provider failed, try next one
                $lastException = $e;
                continue;
            }
        }

        // All providers failed
        throw new \Exception('All AI providers failed to process chunks: ' . $lastException->getMessage());
    }

    /**
     * Process all chunks with a single provider.
     * If any chunk fails (after retry), throw exception to try next provider.
     *
     * @param AIServiceInterface $provider
     * @param array $chunks
     * @param array $config
     * @param array $questionsPerChunk
     * @return array
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

            // Build previous context
            $previousContext = $this->summarizer->combineSummaries($previousSummaries);

            // Calculate questions for this chunk
            $chunkConfig = $this->distributeQuestionsForChunk(
                $config,
                $questionsPerChunk[$index]
            );

            try {
                // Try generating for this chunk
                $result = $provider->generateChunk($chunkContent, $previousContext, $chunkConfig);
                
                // Success! Store result and create summary
                $allResults[] = $result;
                $previousSummaries[] = $this->summarizer->summarizeChunk($chunkContent);

            } catch (\Exception $e) {
                // First attempt failed, try retry
                try {
                    $result = $provider->generateChunk($chunkContent, $previousContext, $chunkConfig);
                    
                    // Retry success! Store result and create summary
                    $allResults[] = $result;
                    $previousSummaries[] = $this->summarizer->summarizeChunk($chunkContent);

                } catch (\Exception $e2) {
                    // Retry failed - throw to switch provider
                    throw new \Exception("Chunk {$chunkNumber} failed after retry: " . $e2->getMessage());
                }
            }
        }

        return $allResults;
    }

    /**
     * Distribute questions for a single chunk.
     *
     * @param array $config
     * @param int $totalQuestionsForChunk
     * @return array
     */
    protected function distributeQuestionsForChunk(array $config, int $totalQuestionsForChunk): array
    {
        $mcCount = $config['multiple_choice_count'] ?? 0;
        $idCount = $config['identification_count'] ?? 0;
        $tfCount = $config['true_or_false_count'] ?? 0;
        
        $totalRequested = $mcCount + $idCount + $tfCount;
        
        if ($totalRequested == 0) {
            return $config;
        }

        // Proportional distribution
        $mcForChunk = (int) round(($mcCount / $totalRequested) * $totalQuestionsForChunk);
        $idForChunk = (int) round(($idCount / $totalRequested) * $totalQuestionsForChunk);
        $tfForChunk = $totalQuestionsForChunk - $mcForChunk - $idForChunk;

        return [
            'multiple_choice_count' => max(0, $mcForChunk),
            'identification_count' => max(0, $idForChunk),
            'true_or_false_count' => max(0, $tfForChunk),
            'difficulty' => $config['difficulty'] ?? 'medium',
        ];
    }

    /**
     * Combine results from all chunks into a single result.
     *
     * @param array $allResults
     * @return array
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

