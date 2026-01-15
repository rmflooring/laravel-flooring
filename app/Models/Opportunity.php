<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = [
        'parent_customer_id',
        'job_site_customer_id',
        'project_manager_id',
        'job_no',
        'status',
        'sales_person_1',
        'sales_person_2',
        'initiated_by',
        'created_by',
        'updated_by',
    ];

    // Match your other models that auto-set created_by/updated_by via closures
    protected static function booted(): void
    {
        static::creating(function ($model) {
            $userId = auth()->id();

            if ($userId) {
                if (empty($model->created_by)) {
                    $model->created_by = $userId;
                }
                if (empty($model->initiated_by)) {
                    $model->initiated_by = $userId;
                }
            }
        });

        static::updating(function ($model) {
            $userId = auth()->id();
            if ($userId) {
                $model->updated_by = $userId;
            }
        });
    }

    /** Relationships **/

    public function parentCustomer()
    {
        return $this->belongsTo(Customer::class, 'parent_customer_id');
    }

    public function jobSiteCustomer()
    {
        return $this->belongsTo(Customer::class, 'job_site_customer_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(ProjectManager::class, 'project_manager_id');
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // You already have Estimate model; we’ll link it in Step 2 once we confirm the column name in estimates table
    public function estimates()
    {
        return $this->hasMany(Estimate::class);
    }
	
	public function documents()
	{
    return $this->hasMany(OpportunityDocument::class);
	}

}
