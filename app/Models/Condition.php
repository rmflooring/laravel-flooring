<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Condition extends Model
{
    protected $fillable = ['title', 'body', 'sort_order', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->created_by ??= auth()->id());
        static::saving(fn ($m) => $m->updated_by = auth()->id());
    }
}
