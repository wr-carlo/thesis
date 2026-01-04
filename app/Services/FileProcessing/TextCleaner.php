<?php

namespace App\Services\FileProcessing;

class TextCleaner
{
    /**
     * Clean extracted text for AI processing.
     *
     * @param string $text
     * @return string
     */
    public function clean(string $text): string
    {
        // Remove multiple consecutive newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Remove multiple consecutive spaces
        $text = preg_replace('/[ ]{2,}/', ' ', $text);

        // Remove tabs
        $text = str_replace("\t", ' ', $text);

        // Remove carriage returns
        $text = str_replace("\r", '', $text);

        // Trim whitespace from each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);

        // Remove empty lines at the beginning and end
        $text = trim($text);

        return $text;
    }
}

