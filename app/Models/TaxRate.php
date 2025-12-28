<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'tax_agency_id',
        'collect_on_sales',
        'sales_rate',
        'sales_gl_account_id',
        'pay_on_purchases',
        'purchase_rate',
        'purchase_gl_account_id',
        'show_on_return_line',
    ];

    public function agency()
    {
        return $this->belongsTo(TaxAgency::class, 'tax_agency_id');
    }

    public function salesGlAccount()
    {
        return $this->belongsTo(GLAccount::class, 'sales_gl_account_id');
    }

    public function purchaseGlAccount()
    {
        return $this->belongsTo(GLAccount::class, 'purchase_gl_account_id');
    }
}
