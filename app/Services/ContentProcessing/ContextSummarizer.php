<?php

namespace App\Services\ContentProcessing;

class ContextSummarizer
{
    /**
     * Create a brief summary of chunk content to use as context.
     * This is a simple extraction of key information.
     *
     * @param string $chunkContent
     * @return string
     */
    public function summarizeChunk(string $chunkContent): string
    {
        // Extract first few sentences as summary
        $sentences = preg_split('/(?<=[.!?])\s+/', $chunkContent, -1, PREG_SPLIT_NO_EMPTY);
        
        // Take first 3-5 sentences as summary
        $summaryLength = min(5, count($sentences));
        $summarySentences = array_slice($sentences, 0, $summaryLength);
        
        $summary = implode(' ', $summarySentences);
        
        // Limit summary length (max 500 characters)
        if (strlen($summary) > 500) {
            $summary = substr($summary, 0, 497) . '...';
        }

        return $summary;
    }

    /**
     * Combine summaries of multiple chunks.
     *
     * @param array $summaries
     * @return string
     */
    public function combineSummaries(array $summaries): string
    {
        if (empty($summaries)) {
            return '';
        }

        $combined = '';
        foreach ($summaries as $index => $summary) {
            $chunkNumber = $index + 1;
            $combined .= "Section {$chunkNumber}: {$summary}\n";
        }

        return trim($combined);
    }

    /**
     * Extract key topics from chunk (simple keyword extraction).
     *
     * @param string $chunkContent
     * @return array
     */
    public function extractKeyTopics(string $chunkContent): array
    {
        // Remove common words
        $commonWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'it', 'its', 'from', 'by', 'as'];

        // Convert to lowercase and extract words
        $words = str_word_count(strtolower($chunkContent), 1);
        
        // Filter out common words and short words
        $keywords = array_filter($words, function($word) use ($commonWords) {
            return strlen($word) > 3 && !in_array($word, $commonWords);
        });

        // Count word frequencies
        $wordCounts = array_count_values($keywords);
        
        // Sort by frequency
        arsort($wordCounts);
        
        // Return top 10 keywords
        return array_slice(array_keys($wordCounts), 0, 10);
    }
}

