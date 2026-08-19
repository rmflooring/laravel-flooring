<?php

namespace App\Services\Agent;

use App\Models\LabourItem;
use App\Models\User;
use App\Services\Agent\Concerns\TokenizesSearchQuery;

/**
 * Executes the `search_labour_catalog` chat tool. Read-only — installation/labour
 * rates staff would otherwise have to look up manually under Labour Items.
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

        return [
            'authorized' => true,
            'results' => $ranked->map(fn (LabourItem $item) => [
                'description' => $item->description,
                'labour_type' => $item->labourType?->name,
                'sell_price' => (float) $item->sell,
                'unit' => $item->unitMeasure?->label,
            ])->values()->all(),
        ];
    }
}
