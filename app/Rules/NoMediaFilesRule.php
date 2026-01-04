<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoMediaFilesRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value || !$value->isValid()) {
            return;
        }

        // First, reject if file type itself is an image/video
        $rejectedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp',
            'image/svg+xml',
            'video/mp4',
            'video/avi',
            'video/mpeg',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-ms-wmv',
        ];

        $mimeType = $value->getMimeType();

        if (in_array($mimeType, $rejectedMimes)) {
            $fail('Pictures, videos, and shapes are not allowed.');
            return;
        }

        // For document files, check content for embedded media
        $allowedMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ];

        if (in_array($mimeType, $allowedMimes)) {
            try {
                // Use FileValidator to check for media content
                $validator = app(\App\Services\FileProcessing\FileValidator::class);
                $filePath = $value->getRealPath();
                
                $result = $validator->validateNoMedia($filePath);
                
                if (!$result['valid']) {
                    $fail($result['error']);
                }
            } catch (\Exception $e) {
                // If validation fails, allow it to proceed (don't block upload on validation error)
                // The FileValidator in the controller will handle it properly
            }
        }
    }
}
