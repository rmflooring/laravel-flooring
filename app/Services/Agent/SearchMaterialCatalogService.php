<?php

namespace App\Services\Agent;

use App\Models\ProductStyle;
use App\Models\User;
use App\Services\Agent\Concerns\TokenizesSearchQuery;

/**
 * Executes the `search_material_catalog` chat tool. Read-only — always returns sell
 * price; cost_price/margin_pct are added only when the user's role is separately
 * granted the `view_catalog_cost` modifier (see KnowledgeAgentSettingsController).
 */
class SearchMaterialCatalogService
{
    use TokenizesSearchQuery;

    private const LIMIT = 10;

    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $query): array
    {
        if (! $this->gate->canUseTool($user, 'search_material_catalog')) {
            return $this->gate->unauthorizedResult();
        }

        $tokens = $this->tokenize($query);
        if (empty($tokens)) {
            $tokens = [$query];
        }

        $candidates = ProductStyle::query()
            ->where('status', 'active')
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $q->orWhere('name', 'like', "%{$token}%")
                        ->orWhere('description', 'like', "%{$token}%")
                        ->orWhere('sku', 'like', "%{$token}%")
                        ->orWhereHas('productLine', fn ($l) => $l->where('name', 'like', "%{$token}%")
                            ->orWhere('manufacturer', 'like', "%{$token}%")
                            ->orWhereHas('productType', fn ($t) => $t->where('name', 'like', "%{$token}%")));
                }
            })
            ->with('productLine.productType')
            // Safety cap before PHP-side ranking — a single common token could otherwise
            // match a large slice of the catalog.
            ->limit(300)
            ->get();

        // Rank by how many of the query's words each row actually matches — an OR
        // across tokens means a generic word alone shouldn't outrank a row that
        // matches the whole query.
        $ranked = $candidates
            ->sortByDesc(fn (ProductStyle $style) => $this->tokenMatchScore($tokens, implode(' ', [
                $style->name,
                $style->description,
                $style->sku,
                $style->productLine?->name,
                $style->productLine?->manufacturer,
                $style->productLine?->productType?->name,
            ])))
            ->take(self::LIMIT);

        $includeCost = $this->gate->canUseTool($user, 'view_catalog_cost');

        return [
            'authorized' => true,
            'results' => $ranked->map(function (ProductStyle $style) use ($includeCost) {
                $row = [
                    'name' => $style->name,
                    'sku' => $style->sku,
                    'product_type' => $style->productLine?->productType?->name,
                    'manufacturer' => $style->productLine?->manufacturer,
                    'sell_price' => (float) $style->sell_price,
                ];

                if ($includeCost) {
                    $row['cost_price'] = (float) $style->cost_price;
                    $row['margin_pct'] = $this->marginPct((float) $style->sell_price, (float) $style->cost_price);
                }

                return $row;
            })->values()->all(),
        ];
    }

    private function marginPct(float $sellPrice, float $costPrice): ?float
    {
        if ($sellPrice <= 0) {
            return null;
        }

        return round((($sellPrice - $costPrice) / $sellPrice) * 100, 1);
    }
}
