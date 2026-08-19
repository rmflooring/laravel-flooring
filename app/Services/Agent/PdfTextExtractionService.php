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

        $text = $this->sanitizeUtf8($text);

        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('No extractable text found in this PDF (it may be a scanned image with no text layer).');
        }

        return $text;
    }

    /**
     * pdfparser's getText() isn't guaranteed to return valid UTF-8 — some PDFs embed
     * text with symbol-font encodings or malformed byte sequences that survive
     * extraction as invalid UTF-8 (confirmed live: a real uploaded pricelist PDF
     * produced text that made json_encode() throw "Malformed UTF-8 characters" when
     * the controller returned it, surfacing to the admin as a generic "Something went
     * wrong"). Strip invalid byte sequences here, once, at the source — every caller
     * downstream (the extractPdf JSON response, later chunking/embedding, DB storage)
     * needs valid UTF-8 anyway, so there's no reason to handle this per-caller.
     */
    private function sanitizeUtf8(string $text): string
    {
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        return iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: '';
    }
}
