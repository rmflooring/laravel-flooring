<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = ['type', 'body'];

    public const TYPES = [
        'wo_scheduled'  => 'Work Order Scheduled',
        'wo_reminder'   => 'Work Order Day-Before Reminder',
        'rfm_booked'    => 'RFM Booked',
        'rfm_reminder'  => 'RFM Day-Before Reminder',
    ];

    public const TAGS = [
        'wo_scheduled' => [
            '{{wo_number}}', '{{sale_number}}', '{{customer_name}}',
            '{{job_address}}', '{{scheduled_date}}', '{{scheduled_time}}',
            '{{installer_name}}', '{{installer_first_name}}',
            '{{pm_name}}', '{{pm_first_name}}',
        ],
        'wo_reminder' => [
            '{{wo_number}}', '{{sale_number}}', '{{customer_name}}',
            '{{job_address}}', '{{scheduled_date}}', '{{scheduled_time}}',
            '{{installer_name}}', '{{installer_first_name}}',
            '{{pm_name}}', '{{pm_first_name}}',
        ],
        'rfm_booked' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}',
            '{{pm_name}}', '{{pm_first_name}}',
        ],
        'rfm_reminder' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}',
            '{{pm_name}}', '{{pm_first_name}}',
        ],
    ];

    public const DEFAULTS = [
        'wo_scheduled' => "Hi {{pm_first_name}}, WO {{wo_number}} has been scheduled for {{scheduled_date}} at {{scheduled_time}}. Installer: {{installer_name}}. Job: {{customer_name}}, {{job_address}}.",
        'wo_reminder'  => "Reminder: WO {{wo_number}} is tomorrow ({{scheduled_date}}) at {{scheduled_time}}. Installer: {{installer_name}}. Job: {{customer_name}}, {{job_address}}.",
        'rfm_booked'   => "Hi {{estimator_first_name}}, RFM booked for {{rfm_date}} at {{rfm_time}}. Customer: {{customer_name}}, {{site_address}}. PM: {{pm_name}}.",
        'rfm_reminder' => "Reminder: RFM appointment tomorrow ({{rfm_date}}) at {{rfm_time}}. Customer: {{customer_name}}, {{site_address}}.",
    ];
}
