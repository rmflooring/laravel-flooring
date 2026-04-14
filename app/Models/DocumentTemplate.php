<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'body',
        'needs_sale',
        'special_flow',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'needs_sale' => 'boolean',
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Merge tags available in templates ────────────────────────────────────

    /** Tags always available (opportunity context) */
    public const OPPORTUNITY_TAGS = [
        '{{customer_name}}',
        '{{job_name}}',
        '{{job_no}}',
        '{{job_site_name}}',
        '{{job_site_address}}',
        '{{job_site_phone}}',
        '{{job_site_email}}',
        '{{pm_name}}',
        '{{pm_first_name}}',
        '{{pm_phone}}',
        '{{pm_email}}',
        '{{insurance_company}}',
        '{{adjuster}}',
        '{{policy_number}}',
        '{{claim_number}}',
        '{{dol}}',
        '{{date}}',
        '{{generated_by}}',
        '{{special_instructions}}',
        '{{notes}}',
        '{{opportunity_photos_qr}}',
        '{{opportunity_qr}}',
    ];

    /** Tags only available when needs_sale = true */
    public const SALE_TAGS = [
        '{{sale_number}}',
        '{{flooring_items_table}}',
    ];

    // ── Lifecycle hooks ───────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (DocumentTemplate $t) {
            $t->created_by = $t->created_by ?? auth()->id();
            $t->updated_by = auth()->id();
        });

        static::updating(function (DocumentTemplate $t) {
            $t->updated_by = auth()->id();
        });
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(OpportunityDocument::class, 'template_id');
    }
}
