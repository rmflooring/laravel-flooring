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
