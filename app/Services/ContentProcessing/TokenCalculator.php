<?php

namespace App\Services\ContentProcessing;

class TokenCalculator
{
    /**
     * Estimate tokens from text (rough estimate: 1 token ≈ 4 characters).
     *
     * @param string $text
     * @return int
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Estimate tokens from word count.
     *
     * @param int $wordCount
     * @param float $tokensPerWord
     * @return int
     */
    public function estimateTokensFromWords(int $wordCount, float $tokensPerWord = 1.3): int
    {
        return (int) ceil($wordCount * $tokensPerWord);
    }

    /**
     * Check if content fits within model token limit.
     *
     * @param int $contentTokens
     * @param int $modelSafeLimit
     * @return bool
     */
    public function fitsInModel(int $contentTokens, int $modelSafeLimit): bool
    {
        return $contentTokens <= $modelSafeLimit;
    }

    /**
     * Calculate word count from text.
     *
     * @param string $text
     * @return int
     */
    public function countWords(string $text): int
    {
        return str_word_count($text);
    }
}

