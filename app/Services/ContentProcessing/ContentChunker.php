<?php

namespace App\Services\ContentProcessing;

class ContentChunker
{
    protected TokenCalculator $tokenCalculator;

    public function __construct(TokenCalculator $tokenCalculator)
    {
        $this->tokenCalculator = $tokenCalculator;
    }

    /**
     * Determine if chunking is needed based on content size and model limit.
     *
     * @param string $content
     * @param int $modelSafeLimit
     * @return array
     */
    public function needsChunking(string $content, int $modelSafeLimit): array
    {
        $contentTokens = $this->tokenCalculator->estimateTokens($content);
        $needsChunking = $contentTokens > $modelSafeLimit;

        return [
            'needs_chunking' => $needsChunking,
            'content_tokens' => $contentTokens,
            'model_safe_limit' => $modelSafeLimit,
        ];
    }

    /**
     * Split content into chunks based on model token limit.
     *
     * @param string $content
     * @param int $modelSafeLimit
     * @param int $bufferTokens
     * @param float $overlapPercentage
     * @return array
     */
    public function chunk(
        string $content,
        int $modelSafeLimit,
        int $bufferTokens = 10000,
        float $overlapPercentage = 0.05
    ): array {
        // Calculate chunk size in tokens
        $chunkTokenSize = $modelSafeLimit - $bufferTokens;
        
        // Calculate overlap in tokens
        $overlapTokens = (int) ($chunkTokenSize * $overlapPercentage);
        
        // Convert to words (1 token ≈ 1.3 words, so 1 word ≈ 0.77 tokens)
        $chunkWordSize = (int) ($chunkTokenSize / 1.3);
        $overlapWords = (int) ($overlapTokens / 1.3);

        // Split content into words
        $words = preg_split('/\s+/', $content);
        $totalWords = count($words);

        // Calculate number of chunks needed
        $numberOfChunks = (int) ceil($totalWords / $chunkWordSize);

        $chunks = [];
        $currentPosition = 0;

        for ($i = 0; $i < $numberOfChunks; $i++) {
            // Calculate start position (with overlap for chunks after the first)
            $start = $i === 0 ? 0 : max(0, $currentPosition - $overlapWords);
            
            // Calculate end position
            $end = min($totalWords, $start + $chunkWordSize);
            
            // Extract chunk words
            $chunkWords = array_slice($words, $start, $end - $start);
            $chunkContent = implode(' ', $chunkWords);

            $chunks[] = [
                'content' => $chunkContent,
                'chunk_number' => $i + 1,
                'start_word' => $start,
                'end_word' => $end,
                'word_count' => count($chunkWords),
                'estimated_tokens' => $this->tokenCalculator->estimateTokens($chunkContent),
            ];

            $currentPosition = $end;

            // Break if we've reached the end
            if ($end >= $totalWords) {
                break;
            }
        }

        return [
            'chunks' => $chunks,
            'total_chunks' => count($chunks),
            'total_words' => $totalWords,
            'chunk_word_size' => $chunkWordSize,
            'overlap_words' => $overlapWords,
            'chunk_token_size' => $chunkTokenSize,
            'overlap_tokens' => $overlapTokens,
        ];
    }

    /**
     * Calculate questions per chunk (equal distribution).
     *
     * @param int $totalQuestions
     * @param int $numberOfChunks
     * @return array
     */
    public function distributeQuestions(int $totalQuestions, int $numberOfChunks): array
    {
        $questionsPerChunk = (int) floor($totalQuestions / $numberOfChunks);
        $remainder = $totalQuestions % $numberOfChunks;

        $distribution = [];
        for ($i = 0; $i < $numberOfChunks; $i++) {
            // Distribute remainder to first chunks
            $questions = $questionsPerChunk + ($i < $remainder ? 1 : 0);
            $distribution[] = $questions;
        }

        return $distribution;
    }
}

