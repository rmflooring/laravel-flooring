<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlooringSignOff extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'opportunity_id', 'sale_id', 'status', 'date',
        'customer_name', 'job_no', 'job_site_name',
        'job_site_address', 'job_site_phone', 'job_site_email',
        'pm_name', 'condition_id', 'condition_text',
        'created_by', 'updated_by',
    ];

    protected $casts = ['date' => 'date'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->created_by ??= auth()->id());
        static::saving(fn ($m) => $m->updated_by = auth()->id());
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function items()
    {
        return $this->hasMany(FlooringSignOffItem::class, 'sign_off_id')->orderBy('sort_order');
    }

    public function condition()
    {
        return $this->belongsTo(Condition::class, 'condition_id');
    }
}
