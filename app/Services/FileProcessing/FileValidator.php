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
            // Check DOCX for images/shapes
            if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                $phpWord = WordIOFactory::load($filePath);
                
                foreach ($phpWord->getSections() as $section) {
                    foreach ($section->getElements() as $element) {
                        $elementClass = get_class($element);
                        
                        if (str_contains($elementClass, 'Image')) {
                            return [
                                'valid' => false,
                                'error' => 'File contains images. Please remove all images before uploading.',
                            ];
                        }
                        
                        if (str_contains($elementClass, 'Shape')) {
                            return [
                                'valid' => false,
                                'error' => 'File contains shapes. Please remove all shapes before uploading.',
                            ];
                        }
                    }
                }
            }

            // Check PPTX for images/shapes
            if ($mimeType === 'application/vnd.openxmlformats-officedocument.presentationml.presentation') {
                $phpPresentation = PresentationIOFactory::load($filePath);
                
                foreach ($phpPresentation->getAllSlides() as $slide) {
                    foreach ($slide->getShapeCollection() as $shape) {
                        $shapeClass = get_class($shape);
                        
                        if (str_contains($shapeClass, 'Drawing')) {
                            return [
                                'valid' => false,
                                'error' => 'Presentation contains images. Please remove all images before uploading.',
                            ];
                        }
                    }
                }
            }

            // PDF media detection - check for actual image objects
            if ($mimeType === 'application/pdf') {
                $content = file_get_contents($filePath);
                
                // Only flag as having images if we find actual image stream objects
                // Pattern 1: Look for /Subtype/Image that's part of an object definition (has numbers before it)
                // This avoids false positives from text content containing the word "Image"
                if (preg_match('/\d+\s+\d+\s+obj[\s\S]{0,500}\/Subtype\s*\/Image[^a-zA-Z]/i', $content)) {
                    return [
                        'valid' => false,
                        'error' => 'PDF contains images. Please remove all images before uploading.',
                    ];
                }
                
                // Pattern 2: /Type/XObject followed by /Subtype/Image (image XObject)
                if (preg_match('/\/Type\s*\/XObject[\s\S]{0,200}\/Subtype\s*\/Image[^a-zA-Z]/i', $content)) {
                    return [
                        'valid' => false,
                        'error' => 'PDF contains images. Please remove all images before uploading.',
                    ];
                }
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

