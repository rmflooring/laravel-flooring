<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = ['type', 'body'];

    public const TYPES = [
        'wo_scheduled'           => 'Work Order Scheduled',
        'wo_reminder'            => 'Work Order Day-Before Reminder',
        'wo_scheduled_customer'  => 'WO Scheduled — Customer',
        'wo_reminder_customer'   => 'WO Reminder — Customer',
        'rfm_booked'             => 'RFM Booked',
        'rfm_updated'            => 'RFM Updated',
        'rfm_reminder'           => 'RFM Day-Before Reminder',
        'rfm_booked_customer'    => 'RFM Booked — Customer',
        'rfm_updated_customer'   => 'RFM Updated — Customer',
        'rfm_reminder_customer'  => 'RFM Reminder — Customer',
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
            '{{rfm_link}}',
        ],
        'rfm_updated' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}',
            '{{pm_name}}', '{{pm_first_name}}',
            '{{rfm_link}}',
        ],
        'rfm_reminder' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}',
            '{{pm_name}}', '{{pm_first_name}}',
            '{{rfm_link}}',
        ],
        'wo_scheduled_customer' => [
            '{{customer_name}}', '{{scheduled_date}}', '{{scheduled_time}}',
            '{{job_address}}', '{{installer_name}}', '{{wo_number}}',
        ],
        'wo_reminder_customer' => [
            '{{customer_name}}', '{{scheduled_date}}', '{{scheduled_time}}',
            '{{job_address}}', '{{installer_name}}', '{{wo_number}}',
        ],
        'rfm_booked_customer' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{estimator_name}}',
        ],
        'rfm_updated_customer' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{estimator_name}}',
        ],
        'rfm_reminder_customer' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{site_address}}', '{{estimator_name}}',
        ],
    ];

    public const DEFAULTS = [
        'wo_scheduled' => "Hi {{pm_first_name}}, WO {{wo_number}} has been scheduled for {{scheduled_date}} at {{scheduled_time}}. Installer: {{installer_name}}. Job: {{customer_name}}, {{job_address}}.",
        'wo_reminder'  => "Reminder: WO {{wo_number}} is tomorrow ({{scheduled_date}}) at {{scheduled_time}}. Installer: {{installer_name}}. Job: {{customer_name}}, {{job_address}}.",
        'rfm_booked'   => "Hi {{estimator_first_name}}, RFM booked for {{rfm_date}} at {{rfm_time}}. Customer: {{customer_name}}, {{site_address}}. PM: {{pm_name}}. View: {{rfm_link}}",
        'rfm_updated'  => "Hi {{estimator_first_name}}, RFM updated — now {{rfm_date}} at {{rfm_time}}. Customer: {{customer_name}}, {{site_address}}. PM: {{pm_name}}. View: {{rfm_link}}",
        'rfm_reminder'           => "Reminder: RFM appointment tomorrow ({{rfm_date}}) at {{rfm_time}}. Customer: {{customer_name}}, {{site_address}}. View: {{rfm_link}}",
        'wo_scheduled_customer'  => "Hi {{customer_name}}, your flooring installation has been scheduled for {{scheduled_date}} at {{scheduled_time}} at {{job_address}}. Reply STOP to unsubscribe.",
        'wo_reminder_customer'   => "Reminder: Your flooring installation is tomorrow ({{scheduled_date}}) at {{scheduled_time}} at {{job_address}}. Reply STOP to unsubscribe.",
        'rfm_booked_customer'    => "Hi {{customer_name}}, your flooring measure has been booked for {{rfm_date}} at {{rfm_time}} at {{site_address}}. Reply STOP to unsubscribe.",
        'rfm_updated_customer'   => "Hi {{customer_name}}, your flooring measure appointment has been updated to {{rfm_date}} at {{rfm_time}} at {{site_address}}. Reply STOP to unsubscribe.",
        'rfm_reminder_customer'  => "Reminder: Your flooring measure is tomorrow ({{rfm_date}}) at {{rfm_time}} at {{site_address}}. Reply STOP to unsubscribe.",
    ];
}
