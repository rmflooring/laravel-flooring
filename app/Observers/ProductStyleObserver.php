<?php

namespace App\Observers;

use App\Models\ProductStyle;
use App\Models\Sample;

class ProductStyleObserver
{
    /**
     * When a product style is marked discontinued, sync all linked samples.
     */
    public function updated(ProductStyle $productStyle): void
    {
        if ($productStyle->wasChanged('status') && $productStyle->status === 'discontinued') {
            Sample::where('product_style_id', $productStyle->id)
                ->whereNotIn('status', ['retired', 'lost', 'discontinued'])
                ->update([
                    'status'          => 'discontinued',
                    'discontinued_at' => now(),
                ]);
        }
    }
}
