<?php

namespace App\Services\Agent;

use App\Models\ProductStyle;
use App\Models\User;

/**
 * Executes the `search_material_catalog` chat tool. Read-only — sell price only, no
 * cost_price/margin (that stays out of scope for a chat answer regardless of role).
 */
class SearchMaterialCatalogService
{
    private const LIMIT = 10;

    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $query): array
    {
        if (! $this->gate->canUseTool($user, 'search_material_catalog')) {
            return $this->gate->unauthorizedResult();
        }

        $styles = ProductStyle::query()
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%")
                    ->orWhereHas('productLine', fn ($l) => $l->where('name', 'like', "%{$query}%")
                        ->orWhere('manufacturer', 'like', "%{$query}%")
                        ->orWhereHas('productType', fn ($t) => $t->where('name', 'like', "%{$query}%")));
            })
            ->with('productLine.productType')
            ->limit(self::LIMIT)
            ->get();

        return [
            'authorized' => true,
            'results' => $styles->map(fn (ProductStyle $style) => [
                'name' => $style->name,
                'sku' => $style->sku,
                'product_type' => $style->productLine?->productType?->name,
                'manufacturer' => $style->productLine?->manufacturer,
                'sell_price' => (float) $style->sell_price,
            ])->values()->all(),
        ];
    }
}
