<?php

namespace App\Services\FileProcessing;

use PhpOffice\PhpPresentation\IOFactory;

/**
 * @phpstan-ignore-next-line
 */
class PptxExtractor implements FileExtractorInterface
{
    public function extract(string $filePath): string
    {
        try {
            $phpPresentation = IOFactory::load($filePath);
            $text = '';

            foreach ($phpPresentation->getAllSlides() as $slide) {
                $shapes = $slide->getShapeCollection();
                foreach ($shapes as $shape) {
                    $text .= $this->extractTextFromShape($shape);
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from PPTX file: ' . $e->getMessage());
        }
    }

    /**
     * Extract text from a presentation shape.
     *
     * @param mixed $shape
     * @return string
     */
    private function extractTextFromShape($shape): string
    {
        $text = '';

        // Extract text from text shapes
        if (method_exists($shape, 'getText')) {
            /** @phpstan-ignore-next-line */
            $text .= $shape->getText() . "\n";
        }

        // Extract text from rich text shapes
        if (method_exists($shape, 'getActiveParagraph')) {
            try {
                /** @phpstan-ignore-next-line */
                $paragraph = $shape->getActiveParagraph();
                if (method_exists($paragraph, 'getText')) {
                    /** @phpstan-ignore-next-line */
                    $text .= $paragraph->getText() . "\n";
                }
            } catch (\Exception $e) {
                // Paragraph might not exist, continue
            }
        }

        // Extract from text elements
        if (method_exists($shape, 'getParagraphs')) {
            /** @phpstan-ignore-next-line */
            $paragraphs = $shape->getParagraphs();
            foreach ($paragraphs as $paragraph) {
                $richTextElements = $paragraph->getRichTextElements();
                foreach ($richTextElements as $element) {
                    if (method_exists($element, 'getText')) {
                        /** @phpstan-ignore-next-line */
                        $text .= $element->getText();
                    }
                }
                $text .= "\n";
            }
        }

        return $text;
    }

    public function validateNoMedia(string $filePath): bool
    {
        try {
            $phpPresentation = IOFactory::load($filePath);

            foreach ($phpPresentation->getAllSlides() as $slide) {
                foreach ($slide->getShapeCollection() as $shape) {
                    $shapeClass = get_class($shape);

                    // Check for drawing/image shapes
                    if (str_contains($shapeClass, 'Drawing')) {
                        return false;
                    }

                    // Check for media shapes
                    if (str_contains($shapeClass, 'Media')) {
                        return false;
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to validate PPTX file: ' . $e->getMessage());
        }
    }
}

