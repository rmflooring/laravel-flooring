<?php

namespace App\Services\Agent;

use App\Models\ProductStyle;
use App\Models\User;
use App\Services\Agent\Concerns\TokenizesSearchQuery;

/**
 * Executes the `search_material_catalog` chat tool. Read-only — sell price only, no
 * cost_price/margin (that stays out of scope for a chat answer regardless of role).
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

        return [
            'authorized' => true,
            'results' => $ranked->map(fn (ProductStyle $style) => [
                'name' => $style->name,
                'sku' => $style->sku,
                'product_type' => $style->productLine?->productType?->name,
                'manufacturer' => $style->productLine?->manufacturer,
                'sell_price' => (float) $style->sell_price,
            ])->values()->all(),
        ];
    }
}
