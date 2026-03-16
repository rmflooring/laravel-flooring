<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickTicketItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'delivered_qty' => 'decimal:2',
        'returned_qty'  => 'decimal:2',
    ];

    public function pickTicket(): BelongsTo
    {
        return $this->belongsTo(PickTicket::class);
    }

    public function inventoryAllocation(): BelongsTo
    {
        return $this->belongsTo(InventoryAllocation::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }
}
