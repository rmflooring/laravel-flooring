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
        'status_reason',
        'is_active',
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

    public function rfms()
    {
        return $this->hasMany(Rfm::class)->orderByDesc('scheduled_at');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class)->orderByDesc('created_at');
    }

    /**
     * Returns the storage folder name for this opportunity's uploaded files.
     * Format: "{JobSiteName} - {job_no}"  e.g. "Sandra_Cokinass - 26-0001"
     * Falls back to "{JobSiteName} - {id}" if no job_no, or "opportunity-{id}" if no site name.
     */
    public function storageFolderName(): string
    {
        $siteName = $this->jobSiteCustomer?->name;
        $jobNo    = $this->job_no;

        $sanitize = fn(string $str): string =>
            preg_replace('/[\/\\\\:*?"<>|]+/', '', str_replace(' ', '_', trim($str)));

        if ($siteName && $jobNo) {
            return $sanitize($siteName) . ' - ' . $sanitize($jobNo);
        }

        if ($siteName) {
            return $sanitize($siteName) . ' - ' . $this->id;
        }

        return 'opportunity-' . $this->id;
    }

}
