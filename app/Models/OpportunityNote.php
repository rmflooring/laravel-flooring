<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['opportunity_id', 'user_id', 'body'];

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
