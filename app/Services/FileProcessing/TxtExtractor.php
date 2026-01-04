<?php

namespace App\Services\FileProcessing;

class TxtExtractor implements FileExtractorInterface
{
    public function extract(string $filePath): string
    {
        try {
            $text = file_get_contents($filePath);

            if ($text === false) {
                throw new \Exception('Could not read text file');
            }

            return trim($text);
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from TXT file: ' . $e->getMessage());
        }
    }

    public function validateNoMedia(string $filePath): bool
    {
        // Plain text files cannot contain media
        return true;
    }
}

