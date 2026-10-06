<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopCacheService
{
    /** @var string[] Shop sites to notify — SHOP_URL may list several, comma-separated. */
    private array $shopUrls;
    private ?string $bustKey;

    public function __construct()
    {
        $this->shopUrls = array_values(array_filter(array_map(
            fn ($url) => rtrim(trim($url), '/'),
            explode(',', (string) config('services.shop.url', '')),
        )));
        $this->bustKey = config('services.shop.cache_bust_key');
    }

    public function bustProductLine(int $lineId, int $productTypeId): void
    {
        $this->bust([
            'shop.product_types',
            'shop.product_lines.all',
            "shop.product_lines.type_{$productTypeId}",
            "shop.product_line.{$lineId}",
        ]);
    }

    public function bustProductStyle(int $lineId, int $productTypeId): void
    {
        $this->bust([
            'shop.product_types',
            'shop.product_lines.all',
            "shop.product_lines.type_{$productTypeId}",
            "shop.product_line.{$lineId}",
        ]);
    }

    private function bust(array $keys): void
    {
        if (!$this->shopUrls || !$this->bustKey) {
            return;
        }

        foreach ($this->shopUrls as $shopUrl) {
            try {
                Http::timeout(3)
                    ->withHeader('X-Cache-Bust-Key', $this->bustKey)
                    ->post("{$shopUrl}/api/cache/bust", ['keys' => $keys]);  // /api prefix from Laravel api routes
            } catch (\Exception $e) {
                Log::warning('Shop cache bust failed', ['url' => $shopUrl, 'error' => $e->getMessage()]);
            }
        }
    }
}
