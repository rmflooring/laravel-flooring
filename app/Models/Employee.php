<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Employee extends Model
{
    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'address_line1',
        'address_line2',
        'city',
        'province',
        'postal_code',
        'sin_encrypted',
        'sin_last4',
        'sin_on_file',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'hire_date',
        'job_title',
        'department_id',
        'employee_role_id',
        'role_other',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'sin_on_file' => 'boolean',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRole::class, 'employee_role_id');
    }

    /**
     * Virtual attribute for saving SIN securely:
     * $employee->sin_plain = '123456789';
     */
    public function setSinPlainAttribute(?string $value): void
    {
        $value = $value ? preg_replace('/\D+/', '', $value) : null;

        if (!$value) {
            $this->attributes['sin_encrypted'] = null;
            $this->attributes['sin_last4'] = null;
            $this->attributes['sin_on_file'] = false;
            return;
        }

        $this->attributes['sin_encrypted'] = Crypt::encryptString($value);
        $this->attributes['sin_last4'] = substr($value, -4);
        $this->attributes['sin_on_file'] = true;
    }

    /**
     * Virtual attribute for reading decrypted SIN:
     * $employee->sin_plain
     * Only show in UI if authorized.
     */
    public function getSinPlainAttribute(): ?string
    {
        if (empty($this->sin_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->sin_encrypted);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Safe masked display:
     * $employee->sin_masked => ***-***-1234
     */
    public function getSinMaskedAttribute(): ?string
    {
        if (!$this->sin_on_file || empty($this->sin_last4)) {
            return null;
        }

        return '***-***-' . $this->sin_last4;
    }
}
