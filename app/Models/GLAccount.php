<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GLAccount extends Model
{
    use HasFactory;

    protected $table = 'gl_accounts';
    protected $fillable = [
        'account_number',
        'name',
        'account_type_id',
        'detail_type_id',
        'parent_id',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    // Relationship: belongs to account type
    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type_id');
    }

    // Relationship: belongs to detail type
    public function detailType()
    {
        return $this->belongsTo(DetailType::class, 'detail_type_id');
    }

    // Relationship: belongs to parent account (for sub-accounts)
    public function parent()
    {
        return $this->belongsTo(GLAccount::class, 'parent_id');
    }

    // Relationship: has many sub-accounts
    public function children()
    {
        return $this->hasMany(GLAccount::class, 'parent_id');
    }

    // Relationship to creator user
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to updater user
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Automatically set created_by and updated_by
    protected static function booted()
    {
        static::creating(function ($account) {
            $account->created_by = auth()->id();
            $account->updated_by = auth()->id();
        });

        static::updating(function ($account) {
            $account->updated_by = auth()->id();
        });
    }
}
