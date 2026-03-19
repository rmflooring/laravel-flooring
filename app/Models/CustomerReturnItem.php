<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReturnItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity_returned' => 'decimal:2',
    ];

    public function customerReturn(): BelongsTo
    {
        return $this->belongsTo(CustomerReturn::class);
    }

    public function pickTicketItem(): BelongsTo
    {
        return $this->belongsTo(PickTicketItem::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function inventoryReceipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class);
    }
}
