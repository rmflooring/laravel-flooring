<?php

if (!function_exists('fmt_price')) {
    /**
     * Format a sell price showing 2 decimal places unless the stored value
     * has meaningful sub-cent precision, in which case show up to 4 decimals.
     * e.g. 62.7200 → "62.72",  62.7202 → "62.7202",  10.00 → "10.00"
     */
    function fmt_price(float|string|null $value): string
    {
        $n   = (float) ($value ?? 0);
        $dec = rtrim(number_format($n, 4, '.', ''), '0');
        // Ensure at least 2 decimal places
        $parts = explode('.', $dec);
        $decimals = $parts[1] ?? '';
        if (strlen($decimals) < 2) {
            $decimals = str_pad($decimals, 2, '0');
        }
        return $parts[0] . '.' . $decimals;
    }
}

if (!function_exists('linkify')) {
    /**
     * Escape plain text and turn any bare http(s) URLs into clickable links.
     * Safe to echo raw ({!! !!}) — the input is HTML-escaped first, same
     * pattern used in App\Mail\SignatureRequestMail. Does not add <br> —
     * pair with a whitespace-pre-wrap wrapper to preserve line breaks.
     */
    function linkify(?string $text): string
    {
        $escaped = htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');

        return preg_replace(
            '/(https?:\/\/[^\s<]+)/',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400" style="word-break:break-all;">$1</a>',
            $escaped
        );
    }
}
