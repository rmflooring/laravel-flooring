<?php

namespace App\Services\Agent;

use App\Models\LabourItem;
use App\Models\User;
use App\Services\Agent\Concerns\TokenizesSearchQuery;

/**
 * Executes the `search_labour_catalog` chat tool. Read-only — installation/labour
 * rates staff would otherwise have to look up manually under Labour Items. Always
 * returns the sell rate; cost_price/margin_pct are added only when the user's role is
 * separately granted the `view_catalog_cost` modifier (see KnowledgeAgentSettingsController).
 */
class SearchLabourCatalogService
{
    use TokenizesSearchQuery;

    private const LIMIT = 10;

    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $query): array
    {
        if (! $this->gate->canUseTool($user, 'search_labour_catalog')) {
            return $this->gate->unauthorizedResult();
        }

        $tokens = $this->tokenize($query);
        if (empty($tokens)) {
            $tokens = [$query];
        }

        $candidates = LabourItem::query()
            ->where('status', 'active')
            ->where(function ($q) use ($tokens) {
                foreach ($tokens as $token) {
                    $q->orWhere('description', 'like', "%{$token}%")
                        ->orWhereHas('labourType', fn ($t) => $t->where('name', 'like', "%{$token}%"));
                }
            })
            ->with(['labourType', 'unitMeasure'])
            ->get();

        // Rank by how many of the query's words each row actually matches — an OR
        // across tokens means a generic word (e.g. "install") alone shouldn't outrank
        // a row that matches the whole query.
        $ranked = $candidates
            ->sortByDesc(fn (LabourItem $item) => $this->tokenMatchScore(
                $tokens,
                $item->description . ' ' . ($item->labourType?->name ?? ''),
            ))
            ->take(self::LIMIT);

        $includeCost = $this->gate->canUseTool($user, 'view_catalog_cost');

        return [
            'authorized' => true,
            'results' => $ranked->map(function (LabourItem $item) use ($includeCost) {
                $row = [
                    'description' => $item->description,
                    'labour_type' => $item->labourType?->name,
                    'sell_price' => (float) $item->sell,
                    'unit' => $item->unitMeasure?->label,
                ];

                if ($includeCost) {
                    $row['cost_price'] = (float) $item->cost;
                    $row['margin_pct'] = $this->marginPct((float) $item->sell, (float) $item->cost);
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
