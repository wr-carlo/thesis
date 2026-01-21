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
     * Recursively check element for images or shapes.
     *
     * @param mixed $element
     * @return bool
     * @phpstan-ignore-next-line
     */
    private function checkElementForMedia($element): bool
    {
        $elementClass = get_class($element);

        // Check for images - multiple possible class names
        if (str_contains($elementClass, 'Image')) {
            return false;
        }

        // Check for shapes - multiple possible class names and patterns
        // PhpWord may use: Shape, TextBox, OLEObject, etc.
        if (
            str_contains($elementClass, 'Shape') ||
            str_contains($elementClass, 'TextBox') ||
            str_contains($elementClass, 'OLEObject') ||
            str_contains($elementClass, 'Object') ||
            str_contains($elementClass, 'Chart')
        ) {
            return false;
        }

        // Also check using instanceof for better detection
        // @phpstan-ignore-next-line
        if (
            $element instanceof \PhpOffice\PhpWord\Element\Shape ||
            // @phpstan-ignore-next-line
            $element instanceof \PhpOffice\PhpWord\Element\TextRun
        ) {
            // Check if TextRun contains non-text elements (like images/shapes)
            if (method_exists($element, 'getElements')) {
                // @phpstan-ignore-next-line
                $childElements = $element->getElements();
                foreach ($childElements as $childElement) {
                    if (!$this->checkElementForMedia($childElement)) {
                        return false;
                    }
                }
            }
            // Continue to check nested elements below
        }

        // Recursively check nested elements
        if (method_exists($element, 'getElements')) {
            // @phpstan-ignore-next-line
            $childElements = $element->getElements();
            foreach ($childElements as $childElement) {
                if (!$this->checkElementForMedia($childElement)) {
                    return false;
                }
            }
        }

        // Check Table elements recursively
        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    $cellElements = $cell->getElements();
                    foreach ($cellElements as $cellElement) {
                        if (!$this->checkElementForMedia($cellElement)) {
                            return false;
                        }
                    }
                }
            }
        }

        // Check for embedded objects or other media types
        // Check if element has methods that suggest it's a shape/object
        if (method_exists($element, 'getSource')) {
            // If it has a source, it's likely an embedded object/image/shape
            return false;
        }

        return true;
    }

    /**
     * Check DOCX XML for shape/drawing elements.
     * DOCX files are ZIP archives containing XML files.
     * We check the document.xml for drawing/shape elements.
     *
     * @param string $filePath
     * @return bool
     */
    private function checkXmlForShapes(string $filePath): bool
    {
        try {
            // DOCX is a ZIP archive
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                // Check main document XML
                $documentXml = $zip->getFromName('word/document.xml');
                if ($documentXml !== false) {
                    // Check for drawing elements
                    // Common shape/drawing elements in DOCX:
                    // - <w:drawing> - main drawing container
                    // - <wp:anchor> or <wp:inline> - drawing anchors
                    // - <a:graphic> - graphic elements
                    // - <v:shape> or <v:imagedata> - legacy shapes/images
                    if (preg_match('/<w:drawing|<wp:anchor|<wp:inline|<a:graphic|<v:shape|<v:imagedata|<o:OLEObject|<mc:AlternateContent/i', $documentXml)) {
                        $zip->close();
                        return false;
                    }
                }
                $zip->close();
            }
            return true;
        } catch (\Exception $e) {
            // If ZIP reading fails, fall back to PhpWord method
            return true;
        }
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function validateNoMedia(string $filePath): bool
    {
        try {
            // First check XML directly for shapes (more comprehensive)
            if (!$this->checkXmlForShapes($filePath)) {
                return false;
            }

            // Also check using PhpWord (for images and known shape types)
            $phpWord = IOFactory::load($filePath);

            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    if (!$this->checkElementForMedia($element)) {
                        return false;
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Failed to validate DOCX file: ' . $e->getMessage());
        }
    }
}
