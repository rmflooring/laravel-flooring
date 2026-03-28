<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTerm extends Model
{
    protected $guarded = ['id'];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
