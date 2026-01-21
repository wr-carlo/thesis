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

    /**
     * Check PPTX XML for shape/drawing elements.
     * PPTX files are ZIP archives containing XML files.
     * We check slide XML files for geometric shapes and drawings.
     *
     * @param string $filePath
     * @return bool
     */
    private function checkXmlForShapes(string $filePath): bool
    {
        try {
            // PPTX is a ZIP archive
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                // Check all slide XML files
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = $zip->getNameIndex($i);
                    // Check slide XML files (ppt/slides/slide*.xml)
                    if (preg_match('#ppt/slides/slide\d+\.xml$#', $filename)) {
                        $slideXml = $zip->getFromIndex($i);
                        if ($slideXml !== false) {
                            // Check for geometric shapes and drawings in PPTX XML:
                            // - <a:blip> - images
                            // - <a:custGeom> or <a:prstGeom> - geometric shapes (rectangles, circles, etc.)
                            // - <p:cNvPr> with specific types for shapes
                            // - <p:sp> with geometry (geometric shapes)
                            // - <p:pic> - pictures
                            // - <p:mediaFile> or <p:audioFile> - media
                            // - <p:cxnSp> - connector lines
                            // - <p:grpSp> - group shapes (may contain media)

                            // Check for geometric shapes: <a:prstGeom> (preset geometry) or <a:custGeom> (custom geometry)
                            if (preg_match('/<a:prstGeom|<a:custGeom|<a:blip|<p:pic|<p:mediaFile|<p:audioFile|<p:cxnSp/i', $slideXml)) {
                                $zip->close();
                                return false;
                            }

                            // Check for shape elements with geometry that aren't text boxes
                            // Text boxes are <p:sp> with <p:txBody>, geometric shapes are <p:sp> without <p:txBody>
                            // Pattern: <p:sp> without <p:txBody> indicates a geometric shape
                            if (preg_match('/<p:sp[^>]*>.*?<\/p:sp>/is', $slideXml, $matches)) {
                                foreach ($matches as $match) {
                                    // If it's a shape (<p:sp>) without text body (<p:txBody>), it's a geometric shape
                                    if (
                                        strpos($match, '<p:txBody') === false &&
                                        strpos($match, '<p:nvSpPr') !== false
                                    ) {
                                        // This is a geometric shape, not a text box
                                        $zip->close();
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }
                $zip->close();
            }
            return true;
        } catch (\Exception $e) {
            // If ZIP reading fails, fall back to PhpPresentation method
            return true;
        }
    }

    public function validateNoMedia(string $filePath): bool
    {
        try {
            // First check XML directly for shapes (more comprehensive)
            if (!$this->checkXmlForShapes($filePath)) {
                return false;
            }

            // Also check using PhpPresentation (for known shape types)
            $phpPresentation = IOFactory::load($filePath);

            foreach ($phpPresentation->getAllSlides() as $slide) {
                foreach ($slide->getShapeCollection() as $shape) {
                    $shapeClass = get_class($shape);

                    // Check for drawing/image shapes
                    if (str_contains($shapeClass, 'Drawing')) {
                        return false;
                    }

                    // Check for media shapes (video, audio)
                    if (str_contains($shapeClass, 'Media')) {
                        return false;
                    }

                    // Check for group shapes - they might contain media
                    if (str_contains($shapeClass, 'Group')) {
                        return false;
                    }

                    // Check for charts - visual elements
                    if (str_contains($shapeClass, 'Chart')) {
                        return false;
                    }

                    // Check for OLE objects (embedded objects)
                    if (str_contains($shapeClass, 'OLE') || str_contains($shapeClass, 'Object')) {
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
