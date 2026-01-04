<?php

namespace App\Services\FileProcessing;

use Smalot\PdfParser\Parser;

class PdfExtractor implements FileExtractorInterface
{
    public function extract(string $filePath): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();

            return trim($text);
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from PDF file: ' . $e->getMessage());
        }
    }

    public function validateNoMedia(string $filePath): bool
    {
        try {
            // Use PDF parser to check for images more accurately
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            
            // Get the raw PDF content to check for image objects
            $content = file_get_contents($filePath);
            
            // Only flag as having images if we find actual image stream objects
            // Pattern: Look for /Subtype/Image that's part of an object definition (has numbers before it)
            // This avoids false positives from text content containing the word "Image"
            if (preg_match('/\d+\s+\d+\s+obj[\s\S]{0,500}\/Subtype\s*\/Image[^a-zA-Z]/i', $content)) {
                return false;
            }
            
            // Also check for /Type/XObject followed by /Subtype/Image (image XObject)
            if (preg_match('/\/Type\s*\/XObject[\s\S]{0,200}\/Subtype\s*\/Image[^a-zA-Z]/i', $content)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // If parsing fails, be lenient and allow the file
            // The extraction will fail later if there's a real problem
            return true;
        }
    }
}

