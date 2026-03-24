<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarTemplate extends Model
{
    protected $fillable = ['type', 'title_template', 'notes_template'];

    /**
     * Admin-managed calendar entry template types.
     */
    const TYPES = [
        'work_order_calendar' => 'WO Installation',
        'po_pickup_calendar'  => 'PO Pickup / Delivery',
        'rfm_calendar'        => 'RFM / Measure',
    ];

    /**
     * Available merge tags per type.
     */
    const TAGS = [
        'work_order_calendar' => [
            '{{wo_number}}'           => 'Work Order number (e.g. 3-8)',
            '{{installer_name}}'      => 'Installer company name',
            '{{installer_first_name}}' => 'First word of installer name',
            '{{customer_name}}'       => 'Homeowner / customer name',
            '{{sale_number}}'         => 'Linked sale number',
            '{{job_address}}'         => 'Job site address',
            '{{items_summary}}'       => 'Comma-separated list of labour items',
            '{{wo_notes}}'            => 'Work order notes',
            '{{pm_name}}'             => 'Project manager full name',
            '{{pm_first_name}}'       => 'Project manager first name',
        ],
        'po_pickup_calendar' => [
            '{{po_number}}'            => 'Purchase order number',
            '{{vendor_name}}'          => 'Vendor company name',
            '{{sale_number}}'          => 'Linked sale number (if any)',
            '{{customer_name}}'        => 'Sale customer name (if any)',
            '{{special_instructions}}' => 'PO special instructions',
            '{{pm_name}}'              => 'Project manager full name',
            '{{pm_first_name}}'        => 'Project manager first name',
        ],
        'rfm_calendar' => [
            '{{customer_name}}'        => 'Opportunity customer name',
            '{{estimator_name}}'       => 'Assigned estimator full name',
            '{{job_number}}'           => 'Opportunity job number',
            '{{flooring_type}}'        => 'Flooring type(s)',
            '{{site_address}}'         => 'RFM site address',
            '{{special_instructions}}' => 'RFM special instructions',
            '{{pm_name}}'              => 'Project manager full name',
            '{{pm_first_name}}'        => 'Project manager first name',
        ],
    ];

    /**
     * Default templates (mirrors current hardcoded behaviour).
     */
    const DEFAULTS = [
        'work_order_calendar' => [
            'title_template' => '{{installer_first_name}} - {{customer_name}}',
            'notes_template' => "Sale: {{sale_number}}\nInstaller: {{installer_name}}\nWork: {{items_summary}}\nPM: {{pm_name}}\n\nNotes:\n{{wo_notes}}",
        ],
        'po_pickup_calendar' => [
            'title_template' => 'Pickup — PO {{po_number}} / {{vendor_name}}',
            'notes_template' => "Purchase Order: {{po_number}}\nSale: {{sale_number}} — {{customer_name}}\nPM: {{pm_name}}\n\nInstructions: {{special_instructions}}",
        ],
        'rfm_calendar' => [
            'title_template' => 'RFM #{{job_number}}: {{customer_name}} – {{flooring_type}}',
            'notes_template' => "Estimator: {{estimator_name}}\nPM: {{pm_name}}\nAddress: {{site_address}}\n\nNotes:\n{{special_instructions}}",
        ],
    ];
}
