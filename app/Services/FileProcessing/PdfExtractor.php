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
            // Get the raw PDF content to check for image objects
            $content = file_get_contents($filePath);
            
            // Pattern 1: Look for /Subtype/Image that's part of an object definition
            // Format: "obj ... /Subtype /Image" - ensures it's an actual image object
            if (preg_match('/\d+\s+\d+\s+obj[\s\S]{0,1000}\/Subtype\s*\/Image[^a-zA-Z]/i', $content)) {
                return false;
            }
            
            // Pattern 2: /Type/XObject followed by /Subtype/Image (image XObject)
            // This catches inline image objects
            if (preg_match('/\/Type\s*\/XObject[\s\S]{0,300}\/Subtype\s*\/Image[^a-zA-Z]/i', $content)) {
                return false;
            }
            
            // Pattern 3: Direct /Subtype/Image in object stream (more comprehensive)
            // Matches: "obj ... << ... /Subtype /Image ... >> stream"
            if (preg_match('/<<[\s\S]{0,500}\/Subtype\s*\/Image[\s\S]{0,500}>>[\s\S]{0,100}stream/i', $content)) {
                return false;
            }
            
            // Pattern 4: Check for common image filters (JPXDecode, DCTDecode, etc.)
            // These filters indicate image compression, which means images are present
            $imageFilters = ['/JPXDecode', '/DCTDecode', '/CCITTFaxDecode', '/JBIG2Decode', '/RunLengthDecode'];
            foreach ($imageFilters as $filter) {
                if (preg_match('/\/Filter\s*' . preg_quote($filter, '/') . '/i', $content)) {
                    // Only flag if it's in an object that also has /Subtype/Image
                    // This avoids false positives from other uses of these filters
                    if (preg_match('/\d+\s+\d+\s+obj[\s\S]{0,1000}\/Subtype\s*\/Image[\s\S]{0,500}\/Filter[\s\S]{0,100}' . preg_quote($filter, '/') . '/i', $content)) {
                        return false;
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            // If parsing fails, be lenient and allow the file
            // The extraction will fail later if there's a real problem
            return true;
        }
    }
}

