<?php

namespace App\Services\AI;

interface AIServiceInterface
{
    /**
     * Generate assessment questions from content.
     *
     * @param string $content
     * @param array $options
     * @return array
     */
    public function generateAssessment(string $content, array $options = []): array;

    /**
     * Generate assessment questions for a single chunk with context.
     *
     * @param string $chunkContent
     * @param string $previousContext
     * @param array $options
     * @return array
     */
    public function generateChunk(string $chunkContent, string $previousContext, array $options = []): array;
}

