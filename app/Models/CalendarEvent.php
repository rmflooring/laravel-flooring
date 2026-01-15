<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use SoftDeletes;

    protected $table = 'calendar_events';

    protected $fillable = [
        'owner_user_id',
        'assigned_to_user_id',
        'title',
        'description',
        'location',
        'starts_at',
        'ends_at',
        'timezone',
        'status',
        'related_type',
        'related_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'deleted_at'=> 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
	
	public function externalLink()
	{
		return $this->hasOne(\App\Models\ExternalEventLink::class, 'calendar_event_id');
	}

}
