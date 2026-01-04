<?php

namespace App\Services\FileProcessing;

interface FileExtractorInterface
{
    /**
     * Extract text content from a file.
     *
     * @param string $filePath
     * @return string
     */
    public function extract(string $filePath): string;

    /**
     * Validate that the file contains no media (images, shapes, videos).
     *
     * @param string $filePath
     * @return bool
     */
    public function validateNoMedia(string $filePath): bool;
}

