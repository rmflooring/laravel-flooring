<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCalendarPreference extends Model
{
    protected $fillable = [
        'user_id',
        'show_rfm',
        'show_installations',
        'show_warehouse',
        'show_team',
        'show_availability',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
