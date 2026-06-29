<?php

namespace App\Console\Commands;

use App\Models\ProductStyle;
use App\Services\ShopCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ApplyScheduledPriceChanges extends Command
{
    protected $signature   = 'styles:apply-scheduled-prices';
    protected $description = 'Apply any pending scheduled price changes on product styles whose effective date has arrived';

    public function handle(ShopCacheService $shopCache): int
    {
        $styles = ProductStyle::query()
            ->whereNotNull('price_change_date')
            ->where('price_change_date', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNotNull('pending_cost_price')
                  ->orWhereNotNull('pending_sell_price');
            })
            ->get();

        if ($styles->isEmpty()) {
            $this->info('No scheduled price changes to apply.');
            return self::SUCCESS;
        }

        $affectedLineIds = [];

        foreach ($styles as $style) {
            $updates = ['price_change_date' => null, 'pending_cost_price' => null, 'pending_sell_price' => null];

            if ($style->pending_cost_price !== null) {
                $updates['cost_price'] = $style->pending_cost_price;
            }
            if ($style->pending_sell_price !== null) {
                $updates['sell_price'] = $style->pending_sell_price;
            }

            $style->update($updates);

            $affectedLineIds[$style->product_line_id] = true;

            Log::info('Scheduled price change applied', [
                'product_style_id' => $style->id,
                'name'             => $style->name,
                'cost_price'       => $updates['cost_price'] ?? '(unchanged)',
                'sell_price'       => $updates['sell_price'] ?? '(unchanged)',
            ]);
        }

        foreach (array_keys($affectedLineIds) as $lineId) {
            $line = \App\Models\ProductLine::find($lineId);
            if ($line) {
                $shopCache->bustProductStyle($line->id, $line->product_type_id);
            }
        }

        $this->info("Applied scheduled price changes to {$styles->count()} style(s).");
        return self::SUCCESS;
    }
}
