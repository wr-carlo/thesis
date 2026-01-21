<?php

namespace App\Services\FileProcessing;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpPresentation\IOFactory as PresentationIOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class FileValidator
{
    protected array $allowedMimeTypes = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
        'application/pdf', // PDF
        'application/vnd.openxmlformats-officedocument.presentationml.presentation', // PPTX
        'text/plain', // TXT
    ];

    protected int $maxFileSize = 10485760; // 10MB in bytes

    public function validateFileType(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType();
        
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return [
                'valid' => false,
                'error' => 'Invalid file type. Only DOCX, PDF, PPTX, and TXT files are allowed.',
            ];
        }

        return ['valid' => true];
    }

    public function validateFileSize(UploadedFile $file): array
    {
        if ($file->getSize() > $this->maxFileSize) {
            return [
                'valid' => false,
                'error' => 'File size exceeds the maximum limit of 10MB.',
            ];
        }

        return ['valid' => true];
    }

    public function validatePasswordProtection(string $filePath): array
    {
        $mimeType = mime_content_type($filePath);

        try {
            // Check DOCX for password protection
            if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                try {
                    WordIOFactory::load($filePath);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'password') || str_contains($e->getMessage(), 'encrypted')) {
                        return [
                            'valid' => false,
                            'error' => 'File is password protected. Please remove password protection before uploading.',
                        ];
                    }
                }
            }

            // Check PDF for password protection
            if ($mimeType === 'application/pdf') {
                try {
                    $parser = new PdfParser();
                    $parser->parseFile($filePath);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'password') || str_contains($e->getMessage(), 'encrypted')) {
                        return [
                            'valid' => false,
                            'error' => 'PDF is password protected. Please remove password protection before uploading.',
                        ];
                    }
                }
            }

            // Check PPTX for password protection
            if ($mimeType === 'application/vnd.openxmlformats-officedocument.presentationml.presentation') {
                try {
                    PresentationIOFactory::load($filePath);
                } catch (\Exception $e) {
                    if (str_contains($e->getMessage(), 'password') || str_contains($e->getMessage(), 'encrypted')) {
                        return [
                            'valid' => false,
                            'error' => 'Presentation is password protected. Please remove password protection before uploading.',
                        ];
                    }
                }
            }

            return ['valid' => true];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Could not validate file: ' . $e->getMessage(),
            ];
        }
    }

    public function validateNoMedia(string $filePath): array
    {
        $mimeType = mime_content_type($filePath);

        try {
            // Use extractor methods for validation (DRY principle)
            // This ensures consistent validation logic across the application
            // Use factory to get appropriate extractor based on MIME type
            
            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                            return [
                                'valid' => false,
                    'error' => 'Unsupported file type for media validation.',
                            ];
                        }
                        
            // Get appropriate extractor using factory
            $extractor = FileExtractorFactory::make($mimeType);
            $isValid = $extractor->validateNoMedia($filePath);
            
            if (!$isValid) {
                // Provide specific error messages based on file type
                $errorMessages = [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 
                        'File contains images or shapes. Please remove all images and shapes before uploading.',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 
                        'Presentation contains images, videos, or other media. Please remove all media content before uploading.',
                    'application/pdf' => 
                        'PDF contains images. Please remove all images before uploading.',
                    'text/plain' => 
                        'Text file validation failed.',
                ];
                
                    return [
                        'valid' => false,
                    'error' => $errorMessages[$mimeType] ?? 'File contains media content. Please remove all media before uploading.',
                    ];
            }

            return ['valid' => true];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Could not validate media content: ' . $e->getMessage(),
            ];
        }
    }

    public function validateAll(UploadedFile $file, string $filePath): array
    {
        // Validate file type
        $typeValidation = $this->validateFileType($file);
        if (!$typeValidation['valid']) {
            return $typeValidation;
        }

        // Validate file size
        $sizeValidation = $this->validateFileSize($file);
        if (!$sizeValidation['valid']) {
            return $sizeValidation;
        }

        // Validate password protection
        $passwordValidation = $this->validatePasswordProtection($filePath);
        if (!$passwordValidation['valid']) {
            return $passwordValidation;
        }

        // Validate no media content
        $mediaValidation = $this->validateNoMedia($filePath);
        if (!$mediaValidation['valid']) {
            return $mediaValidation;
        }

        return ['valid' => true];
    }
}

