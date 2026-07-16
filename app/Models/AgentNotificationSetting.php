<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentNotificationSetting extends Model
{
    protected $fillable = [
        'task_type',
        'admin_bcc_enabled',
    ];

    protected $casts = [
        'admin_bcc_enabled' => 'boolean',
    ];

    public static function bccEnabledFor(?string $taskType): bool
    {
        if (! $taskType) {
            return false;
        }

        return static::query()
            ->where('task_type', $taskType)
            ->value('admin_bcc_enabled') ?? false;
    }
}
