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
        'rfm_created' => 'RFM Created',
        'rfm_updated' => 'RFM Updated',
    ];

    // Merge tags available per type
    public const TAGS = [
        'estimate' => [
            '{{customer_name}}', '{{estimate_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_address}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{salesperson_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'sale' => [
            '{{customer_name}}', '{{sale_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_address}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{salesperson_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'work_order' => [
            '{{customer_name}}', '{{wo_number}}', '{{job_name}}', '{{job_address}}',
            '{{pm_name}}', '{{pm_first_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'purchase_order' => [
            '{{customer_name}}', '{{po_number}}', '{{job_name}}', '{{job_address}}',
            '{{pm_name}}', '{{pm_first_name}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'invoice' => [
            '{{customer_name}}', '{{invoice_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_address}}', '{{pm_name}}', '{{pm_first_name}}',
            '{{sender_name}}', '{{sender_email}}',
        ],
        'rfm_created' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{job_address}}', '{{estimator_name}}', '{{pm_name}}', '{{special_instructions}}',
        ],
        'rfm_updated' => [
            '{{customer_name}}', '{{rfm_date}}', '{{rfm_time}}',
            '{{job_address}}', '{{estimator_name}}', '{{pm_name}}', '{{special_instructions}}',
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
            'body'    => "Hi {{customer_name}},\n\nPlease find your work order below.\n\nWork Order #: {{wo_number}}\nJob: {{job_name}}\nAddress: {{job_address}}\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'purchase_order' => [
            'subject' => 'Purchase Order {{po_number}} — {{job_name}}',
            'body'    => "Hi {{customer_name}},\n\nPlease find your purchase order below.\n\nPurchase Order #: {{po_number}}\nJob: {{job_name}}\nAddress: {{job_address}}\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'invoice' => [
            'subject' => 'Invoice {{invoice_number}} from RM Flooring',
            'body'    => "Hi {{customer_name}},\n\nPlease find your invoice below.\n\nInvoice #: {{invoice_number}}\nJob: {{job_name}}\nTotal: {{grand_total}}\n\nThank you for choosing RM Flooring.\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'rfm_created' => [
            'subject' => 'RFM Scheduled — {{customer_name}}',
            'body'    => "A new RFM has been scheduled.\n\nCustomer: {{customer_name}}\nDate: {{rfm_date}}\nTime: {{rfm_time}}\nAddress: {{job_address}}\nEstimator: {{estimator_name}}\nPM: {{pm_name}}\n\nSpecial Instructions:\n{{special_instructions}}",
        ],
        'rfm_updated' => [
            'subject' => 'RFM Updated — {{customer_name}}',
            'body'    => "An RFM has been updated.\n\nCustomer: {{customer_name}}\nDate: {{rfm_date}}\nTime: {{rfm_time}}\nAddress: {{job_address}}\nEstimator: {{estimator_name}}\nPM: {{pm_name}}\n\nSpecial Instructions:\n{{special_instructions}}",
        ],
    ];
}
