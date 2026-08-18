<?php

namespace App\Services\Agent;

use Smalot\PdfParser\Parser;

/**
 * Extracts plain text from an uploaded PDF for the knowledge base "import from PDF"
 * feature — admin reviews/edits the result before it's ever chunked or embedded.
 * Pure-PHP (smalot/pdfparser), no new system-level dependency to install on the live
 * server. Text extraction from PDFs (especially tabular price lists) is inherently
 * imperfect — this is why the result is a review-and-edit step, not a direct save.
 */
class PdfTextExtractionService
{
    public function extractFromPath(string $path): string
    {
        try {
            $text = (new Parser())->parseFile($path)->getText();
        } catch (\Throwable $e) {
            throw new \RuntimeException('Could not read this PDF: ' . $e->getMessage());
        }

        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('No extractable text found in this PDF (it may be a scanned image with no text layer).');
        }

        return $text;
    }
}
