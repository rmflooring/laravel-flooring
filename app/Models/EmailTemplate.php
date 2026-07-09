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
        'estimate'             => 'Estimate',
        'sale'                 => 'Sale',
        'work_order'           => 'Work Order',
        'purchase_order'       => 'Purchase Order',
        'invoice'              => 'Invoice',
        'estimate_follow_up_1' => 'Estimate Follow-up — Stage 1 (7 days)',
        'estimate_follow_up_2' => 'Estimate Follow-up — Stage 2 (14 days)',
        'estimate_follow_up_3' => 'Estimate Follow-up — Stage 3 (30 days)',
    ];

    // Types restricted to admin (system notifications)
    public const SYSTEM_TYPES = [
        'rfm_created_estimator'        => 'RFM Created — Estimator',
        'rfm_created_pm'               => 'RFM Created — PM',
        'rfm_updated_estimator'        => 'RFM Updated — Estimator',
        'rfm_updated_pm'               => 'RFM Updated — PM',
        'shop_quote_confirmation'      => 'Shop Quote — Customer Confirmation',
        'signature_request_flooring'   => 'Signature Request — Flooring Selection',
        'signature_request_work_auth'  => 'Signature Request — Work Authorization',
        'signature_request_document'   => 'Signature Request — Generated Document',
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
        'shop_quote_confirmation' => [
            '{{first_name}}', '{{last_name}}', '{{phone}}',
            '{{product_reference}}', '{{color_reference}}', '{{sq_footage}}',
        ],
        'signature_request_flooring' => [
            '{{client_name}}', '{{document_label}}', '{{signing_link}}', '{{signing_link_button}}', '{{expires_date}}',
        ],
        'signature_request_work_auth' => [
            '{{client_name}}', '{{document_label}}', '{{signing_link}}', '{{signing_link_button}}', '{{expires_date}}',
        ],
        'signature_request_document' => [
            '{{client_name}}', '{{document_label}}', '{{signing_link}}', '{{signing_link_button}}', '{{expires_date}}',
        ],
        'estimate_follow_up_1' => [
            '{{customer_name}}', '{{estimate_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_no}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{days_since_sent}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'estimate_follow_up_2' => [
            '{{customer_name}}', '{{estimate_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_no}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{days_since_sent}}', '{{sender_name}}', '{{sender_email}}',
        ],
        'estimate_follow_up_3' => [
            '{{customer_name}}', '{{estimate_number}}', '{{grand_total}}',
            '{{job_name}}', '{{job_no}}', '{{job_address}}', '{{job_phone}}', '{{job_mobile}}',
            '{{days_since_sent}}', '{{sender_name}}', '{{sender_email}}',
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
        'shop_quote_confirmation' => [
            'subject' => 'We received your quote request — RM Flooring',
            'body'    => "Hi {{first_name}},\n\nThank you for reaching out! We've received your quote request and one of our team members will be in touch with you shortly.\n\nIf you have any questions in the meantime, feel free to contact us at reception@rmflooring.ca.\n\nWarm regards,\nRM Flooring",
        ],
        'signature_request_flooring' => [
            'subject' => 'Action Required: Please Sign Your Flooring Selection',
            'body'    => "Hello {{client_name}},\n\nRM Flooring & Cabinetry has prepared a {{document_label}} document for your review and signature.\n\n{{signing_link_button}}\n\nIf the button above doesn't work, copy and paste this link into your browser:\n{{signing_link}}\n\nThis link will expire on {{expires_date}}. If you need a new link after that date, please contact us.\n\nIf you have any questions or did not expect this email, please reach out to us at reception@rmflooring.ca.\n\nThank you,\nRM Flooring & Cabinetry",
        ],
        'signature_request_work_auth' => [
            'subject' => 'Action Required: Please Sign Your Work Authorization',
            'body'    => "Hello {{client_name}},\n\nRM Flooring & Cabinetry has prepared a {{document_label}} document for your review and signature.\n\n{{signing_link_button}}\n\nIf the button above doesn't work, copy and paste this link into your browser:\n{{signing_link}}\n\nThis link will expire on {{expires_date}}. If you need a new link after that date, please contact us.\n\nIf you have any questions or did not expect this email, please reach out to us at reception@rmflooring.ca.\n\nThank you,\nRM Flooring & Cabinetry",
        ],
        'signature_request_document' => [
            'subject' => 'Action Required: Please Sign — {{document_label}}',
            'body'    => "Hello {{client_name}},\n\nRM Flooring & Cabinetry has prepared a {{document_label}} document for your review and signature.\n\n{{signing_link_button}}\n\nIf the button above doesn't work, copy and paste this link into your browser:\n{{signing_link}}\n\nThis link will expire on {{expires_date}}. If you need a new link after that date, please contact us.\n\nIf you have any questions or did not expect this email, please reach out to us at reception@rmflooring.ca.\n\nThank you,\nRM Flooring & Cabinetry",
        ],
        'estimate_follow_up_1' => [
            'subject' => 'Following up on your estimate {{estimate_number}} — RM Flooring',
            'body'    => "Hi {{customer_name}},\n\nI just wanted to follow up on the estimate we sent over for {{job_name}}.\n\nEstimate #: {{estimate_number}}\nTotal: {{grand_total}}\n\nPlease don't hesitate to reach out if you have any questions or would like to go over any of the details — we're happy to help.\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'estimate_follow_up_2' => [
            'subject' => 'Checking in — Estimate {{estimate_number}}',
            'body'    => "Hi {{customer_name}},\n\nI wanted to check in again regarding the estimate we provided for {{job_name}}.\n\nEstimate #: {{estimate_number}}\nTotal: {{grand_total}}\n\nIf you have any questions, want to adjust anything, or would like to schedule a time to chat, we're here to help.\n\n{{sender_name}}\n{{sender_email}}",
        ],
        'estimate_follow_up_3' => [
            'subject' => 'Last check-in — Estimate {{estimate_number}}',
            'body'    => "Hi {{customer_name}},\n\nI wanted to reach out one more time regarding the estimate for {{job_name}}.\n\nEstimate #: {{estimate_number}}\nTotal: {{grand_total}}\n\nIf the timing isn't right or you've decided to go in a different direction, no worries at all — just let us know and we'll update our records. Otherwise, we'd love the opportunity to work with you.\n\n{{sender_name}}\n{{sender_email}}",
        ],
    ];
}
