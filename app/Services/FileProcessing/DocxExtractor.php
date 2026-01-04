<?php

namespace App\Services\FileProcessing;

use PhpOffice\PhpWord\IOFactory;

/**
 * @phpstan-ignore-next-line
 */
class DocxExtractor implements FileExtractorInterface
{
    public function extract(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    // Extract text from various element types
                    $text .= $this->extractTextFromElement($element);
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            throw new \Exception('Failed to extract text from DOCX file: ' . $e->getMessage());
        }
    }

    /**
     * Extract text from a PhpWord element.
     *
     * @param mixed $element
     * @return string
     * @phpstan-ignore-next-line
     */
    private function extractTextFromElement($element): string
    {
        $text = '';

        // Handle Text element
        if ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text .= $element->getText();
        }
        // Handle TextRun element - extract from child elements
        elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            // @phpstan-ignore-next-line
            $textRuns = $element->getElements();
            foreach ($textRuns as $textRun) {
                if ($textRun instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $textRun->getText();
                }
            }
        }
        // Handle elements with getElements method (Paragraph, TextRun, etc.)
        elseif (method_exists($element, 'getElements')) {
            // @phpstan-ignore-next-line
            $paragraphElements = $element->getElements();
            foreach ($paragraphElements as $paraElement) {
                if ($paraElement instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $paraElement->getText();
                } elseif ($paraElement instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    // @phpstan-ignore-next-line
                    $textRunElements = $paraElement->getElements();
                    foreach ($textRunElements as $textRunElement) {
                        if ($textRunElement instanceof \PhpOffice\PhpWord\Element\Text) {
                            $text .= $textRunElement->getText();
                        }
                    }
                } else {
                    // Recursively extract from nested elements
                    $text .= $this->extractTextFromElement($paraElement);
                }
            }
            $text .= "\n";
        }
        // Handle Table element
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    $cellElements = $cell->getElements();
                    foreach ($cellElements as $cellElement) {
                        $text .= $this->extractTextFromElement($cellElement);
                    }
                    $text .= ' ';
                }
                $text .= "\n";
            }
        }
        // Fallback: Try generic getText method
        elseif (method_exists($element, 'getText')) {
            try {
                $elementText = $element->getText();
                if (is_string($elementText)) {
                    $text .= $elementText . "\n";
                }
            } catch (\Exception $e) {
                // Ignore if getText fails
            }
        }
        // Fallback: Try getElements method for nested elements
        elseif (method_exists($element, 'getElements')) {
            // @phpstan-ignore-next-line
            $childElements = $element->getElements();
            foreach ($childElements as $childElement) {
                $text .= $this->extractTextFromElement($childElement);
            }
        }

        return $text;
    }


    /**
     * @phpstan-ignore-next-line
     */
    public function validateNoMedia(string $filePath): bool
    {
        try {
            $phpWord = IOFactory::load($filePath);

            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    $elementClass = get_class($element);

                    // Check for images
                    if (str_contains($elementClass, 'Image')) {
                        return false;
                    }

                    // Check for shapes
                    if (str_contains($elementClass, 'Shape')) {
                        return false;
                    }

                    // Check nested elements (TextRun, Paragraph, etc.)
                    if (method_exists($element, 'getElements')) {
                        $childElements = $element->getElements(); // @phpstan-ignore-line
                        foreach ($childElements as $childElement) {
                            $childClass = get_class($childElement);
                            if (str_contains($childClass, 'Image') || str_contains($childClass, 'Shape')) {
                                return false;
                            }
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to validate DOCX file: ' . $e->getMessage());
        }
    }
}



