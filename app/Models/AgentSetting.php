<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentSetting extends Model
{
    protected $fillable = [
        'admin_notification_email',
        'allowed_sender_domains',
        'allowed_sender_addresses',
        'rate_limit_per_sender_per_hour',
    ];

    protected $casts = [
        'allowed_sender_domains' => 'array',
        'allowed_sender_addresses' => 'array',
    ];

    /**
     * agent_settings is a single-row config table — fetch (or lazily create) that row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    public function isSenderAllowed(string $email): bool
    {
        $domains = $this->allowed_sender_domains ?? [];
        $addresses = $this->allowed_sender_addresses ?? [];

        if (in_array(strtolower($email), array_map('strtolower', $addresses), true)) {
            return true;
        }

        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));

        return $domain !== '' && in_array($domain, array_map('strtolower', $domains), true);
    }
}
