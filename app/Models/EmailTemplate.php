<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = ['user_id', 'type', 'subject', 'body'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Types available to regular users (customer-facing)
    public const USER_TYPES = [
        'estimate'       => 'Estimate',
        'sale'           => 'Sale',
        'work_order'     => 'Work Order',
        'purchase_order' => 'Purchase Order',
        'invoice'        => 'Invoice',
    ];

    // Types restricted to admin (system notifications)
    public const SYSTEM_TYPES = [
        'rfm_created_estimator' => 'RFM Created — Estimator',
        'rfm_created_pm'        => 'RFM Created — PM',
        'rfm_updated_estimator' => 'RFM Updated — Estimator',
        'rfm_updated_pm'        => 'RFM Updated — PM',
    ];

    // Merge tags available per type
    public const TAGS = [
        'estimate' => [
            '{{customer_name}}', '{{estimate_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_no}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{pm_name}}', '{{pm_first_name}}',
            '{{salesperson_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'sale' => [
            '{{customer_name}}', '{{sale_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_no}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{pm_name}}', '{{pm_first_name}}',
            '{{salesperson_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'work_order' => [
            '{{customer_name}}', '{{wo_number}}', '{{job_name}}', '{{job_no}}', '{{job_address}}',
            '{{job_phone}}', '{{job_mobile}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{sender_name}}', '{{sender_email}}', '{{wo_link}}',
        ],
        'purchase_order' => [
            '{{customer_name}}', '{{po_number}}', '{{job_name}}', '{{job_no}}', '{{job_address}}',
            '{{pm_name}}', '{{pm_first_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'invoice' => [
            '{{customer_name}}', '{{invoice_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_no}}', '{{job_address}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{sender_name}}', '{{sender_email}}',
        ],
        'rfm_created_estimator' => [
            '{{customer_name}}', '{{job_no}}', '{{job_site}}',
            '{{rfm_date}}', '{{rfm_time}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{flooring_type}}', '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{rfm_link}}',
        ],
        'rfm_created_pm' => [
            '{{customer_name}}', '{{job_no}}', '{{job_site}}',
            '{{rfm_date}}', '{{rfm_time}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{rfm_link}}',
        ],
        'rfm_updated_estimator' => [
            '{{customer_name}}', '{{job_no}}', '{{job_site}}',
            '{{rfm_date}}', '{{rfm_time}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{flooring_type}}', '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{rfm_link}}',
        ],
        'rfm_updated_pm' => [
            '{{customer_name}}', '{{job_no}}', '{{job_site}}',
            '{{rfm_date}}', '{{rfm_time}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{special_instructions}}',
            '{{estimator_name}}', '{{estimator_first_name}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{rfm_link}}',
        ],
    ];

    // Built-in defaults — used when a user has no saved template
    public const DEFAULTS = [
        'estimate' => [
            'subject' => 'Your Estimate {{estimate_number}} from RM Flooring',
            'body'    => "Hi {{customer_name}},\n\nPlease find your estimate below.\n\nEstimate #: {{estimate_number}}\nJob: {{job_name}}\nTotal: {{grand_total}}\n\nPlease don't hesitate to reach out if you have any questions.\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'sale' => [
            'subject' => 'Your Order Confirmation {{sale_number}} — RM Flooring',
            'body'    => "Hi {{customer_name}},\n\nThank you for your order. Your sale confirmation is below.\n\nSale #: {{sale_number}}\nJob: {{job_name}}\nTotal: {{grand_total}}\n\nWe'll be in touch with next steps.\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'work_order' => [
            'subject' => 'Work Order {{wo_number}} — {{job_name}}',
            'body'    => "Hi {{customer_name}},\n\nPlease find your work order attached.\n\nWork Order #: {{wo_number}}\nJob: {{job_name}}\nAddress: {{job_address}}\n\nView on mobile: {{wo_link}}\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'purchase_order' => [
            'subject' => 'Purchase Order {{po_number}} — {{job_name}}',
            'body'    => "Hi {{customer_name}},\n\nPlease find your purchase order below.\n\nPurchase Order #: {{po_number}}\nJob: {{job_name}}\nAddress: {{job_address}}\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'invoice' => [
            'subject' => 'Invoice {{invoice_number}} from RM Flooring',
            'body'    => "Hi {{customer_name}},\n\nPlease find your invoice below.\n\nInvoice #: {{invoice_number}}\nJob: {{job_name}}\nTotal: {{grand_total}}\n\nThank you for choosing RM Flooring.\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'rfm_created_estimator' => [
            'subject' => 'RFM Scheduled — {{customer_name}}',
            'body'    => "A new Request for Measure has been scheduled.\n\n----------------------------------------\nJob:          {{job_no}} — {{customer_name}}\nJob Site:     {{job_site}}\nEstimator:    {{estimator_name}}\nDate:         {{rfm_date}}\nTime:         {{rfm_time}}\nAddress:      {{job_address}}\nPhone:        {{job_phone}}\nMobile:       {{job_mobile}}\nFlooring:     {{flooring_type}}\n\nSpecial Instructions:\n{{special_instructions}}\n\n----------------------------------------\nOpen on mobile: {{rfm_link}}",
        ],
        'rfm_created_pm' => [
            'subject' => 'RFM Scheduled — {{customer_name}}',
            'body'    => "Hi {{pm_first_name}},\n\nA flooring measurement has been scheduled for {{customer_name}} at {{job_site}}.\n\n----------------------------------------\nDate:         {{rfm_date}}\nTime:         {{rfm_time}}\nLocation:     {{job_address}}\nPhone:        {{job_phone}}\nEstimator:    {{estimator_name}}\n----------------------------------------\n\nPlease ensure site access is available at the scheduled time.\n\nThank you,\nRM Flooring",
        ],
        'rfm_updated_estimator' => [
            'subject' => 'RFM Updated — {{customer_name}}',
            'body'    => "An RFM has been updated. Details below.\n\n--- Current RFM Details ---\nJob:          {{job_no}} — {{customer_name}}\nJob Site:     {{job_site}}\nEstimator:    {{estimator_name}}\nDate:         {{rfm_date}}\nTime:         {{rfm_time}}\nAddress:      {{job_address}}\nPhone:        {{job_phone}}\nMobile:       {{job_mobile}}\nFlooring:     {{flooring_type}}\n\nSpecial Instructions:\n{{special_instructions}}\n\n----------------------------------------\nOpen on mobile: {{rfm_link}}",
        ],
        'rfm_updated_pm' => [
            'subject' => 'RFM Updated — {{customer_name}}',
            'body'    => "Hi {{pm_first_name}},\n\nThe flooring measurement details for {{customer_name}} at {{job_site}} have been updated.\n\n----------------------------------------\nDate:         {{rfm_date}}\nTime:         {{rfm_time}}\nLocation:     {{job_address}}\nPhone:        {{job_phone}}\nEstimator:    {{estimator_name}}\n----------------------------------------\n\nPlease ensure site access is available at the updated scheduled time.\n\nThank you,\nRM Flooring",
        ],
    ];
}
