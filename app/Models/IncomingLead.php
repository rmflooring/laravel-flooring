<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingLead extends Model
{
    protected $fillable = [
        'source', 'name', 'phone', 'email', 'sms_consent',
        'service_type', 'project_type', 'area', 'city', 'timeline',
        'message', 'referral_source', 'status',
        'opportunity_id', 'reviewed_by', 'reviewed_at', 'denial_reason',
    ];

    protected $casts = [
        'sms_consent' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
