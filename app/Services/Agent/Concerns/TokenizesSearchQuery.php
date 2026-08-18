<?php

namespace App\Services\Agent\Concerns;

/**
 * Shared tokenizer for the catalog search tools (SearchLabourCatalogService,
 * SearchMaterialCatalogService). A single LIKE '%whole query%' match is too strict —
 * catalog entries are short internal names ("Laminate Install - Click"), so a natural
 * multi-word question ("laminate flooring installation") almost never appears as one
 * contiguous substring even when the row is an obvious match. Splitting into words and
 * matching on any of them (an OR across tokens, per field) is far more forgiving.
 */
trait TokenizesSearchQuery
{
    /**
     * @return array<int, string>
     */
    private function tokenize(string $query): array
    {
        $words = preg_split('/\s+/', trim($query)) ?: [];

        return collect($words)
            ->map(fn (string $w) => trim($w, " \t\n\r\0\x0B-"))
            ->filter(fn (string $w) => mb_strlen($w) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * How many of the given tokens appear in $haystack — used to rank OR-matched
     * candidates so a row matching more of the query's words (e.g. both "laminate"
     * AND "install") outranks one matching only a common word like "install" alone.
     * Without this, a generic word in the query could bury the actually-relevant row
     * once results are capped to a limit.
     *
     * @param  array<int, string>  $tokens
     */
    private function tokenMatchScore(array $tokens, string $haystack): int
    {
        $haystack = mb_strtolower($haystack);

        $score = 0;
        foreach ($tokens as $token) {
            if (str_contains($haystack, mb_strtolower($token))) {
                $score++;
            }
        }

        return $score;
    }
}
