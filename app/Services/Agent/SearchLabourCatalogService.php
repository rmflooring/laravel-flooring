<?php

namespace App\Services\Agent;

use App\Models\LabourItem;
use App\Models\User;

/**
 * Executes the `search_labour_catalog` chat tool. Read-only — installation/labour
 * rates staff would otherwise have to look up manually under Labour Items.
 */
class SearchLabourCatalogService
{
    private const LIMIT = 10;

    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $query): array
    {
        if (! $this->gate->canUseTool($user, 'search_labour_catalog')) {
            return $this->gate->unauthorizedResult();
        }

        $items = LabourItem::query()
            ->where('status', 'active')
            ->where(function ($q) use ($query) {
                $q->where('description', 'like', "%{$query}%")
                    ->orWhereHas('labourType', fn ($t) => $t->where('name', 'like', "%{$query}%"));
            })
            ->with(['labourType', 'unitMeasure'])
            ->limit(self::LIMIT)
            ->get();

        return [
            'authorized' => true,
            'results' => $items->map(fn (LabourItem $item) => [
                'description' => $item->description,
                'labour_type' => $item->labourType?->name,
                'sell_price' => (float) $item->sell,
                'unit' => $item->unitMeasure?->label,
            ])->values()->all(),
        ];
    }
}
