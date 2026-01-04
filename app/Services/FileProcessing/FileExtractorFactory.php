<?php

namespace App\Services\FileProcessing;

class FileExtractorFactory
{
    /**
     * Create an appropriate file extractor based on MIME type.
     *
     * @param string $mimeType
     * @return FileExtractorInterface
     * @throws \Exception
     */
    public static function make(string $mimeType): FileExtractorInterface
    {
        return match ($mimeType) {
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => new DocxExtractor(),
            'application/pdf' => new PdfExtractor(),
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => new PptxExtractor(),
            'text/plain' => new TxtExtractor(),
            default => throw new \Exception('Unsupported file type: ' . $mimeType),
        };
    }
}

