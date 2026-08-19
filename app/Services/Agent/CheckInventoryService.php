<?php

namespace App\Services\Agent;

use App\Models\InventoryReceipt;
use App\Models\ProductStyle;
use App\Models\User;

/**
 * Executes the `check_inventory` chat tool. Read-only — sums InventoryReceipt's
 * available_qty accessor (received minus allocated minus outbound plus adjustments,
 * the same calculation the Inventory area of the app already uses) across every
 * receipt for the matched SKU.
 */
class CheckInventoryService
{
    public function __construct(private KnowledgeAccessGate $gate) {}

    public function execute(User $user, string $sku): array
    {
        if (! $this->gate->canUseTool($user, 'check_inventory')) {
            return $this->gate->unauthorizedResult();
        }

        $style = ProductStyle::where('sku', $sku)->first();
        if (! $style) {
            return ['authorized' => true, 'found' => false, 'message' => "No product found with SKU \"{$sku}\"."];
        }

        $receipts = InventoryReceipt::where('product_style_id', $style->id)->get();
        $available = $receipts->sum(fn (InventoryReceipt $r) => $r->available_qty);

        return [
            'authorized' => true,
            'found' => true,
            'sku' => $style->sku,
            'product_name' => $style->name,
            'available_quantity' => round($available, 2),
        ];
    }
}
