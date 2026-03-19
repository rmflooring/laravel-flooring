<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReturnItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
        'unit_cost'         => 'decimal:2',
        'line_total'        => 'decimal:2',
    ];

    public function inventoryReturn(): BelongsTo
    {
        return $this->belongsTo(InventoryReturn::class);
    }

    public function inventoryReceipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
