<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopCacheService
{
    private ?string $shopUrl;
    private ?string $bustKey;

    public function __construct()
    {
        $this->shopUrl = rtrim(config('services.shop.url', ''), '/');
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
        if (!$this->shopUrl || !$this->bustKey) {
            return;
        }

        try {
            Http::timeout(3)
                ->withHeader('X-Cache-Bust-Key', $this->bustKey)
                ->post("{$this->shopUrl}/api/cache/bust", ['keys' => $keys]);  // /api prefix from Laravel api routes
        } catch (\Exception $e) {
            Log::warning('Shop cache bust failed', ['error' => $e->getMessage()]);
        }
    }
}
