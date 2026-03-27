<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use App\Models\Opportunity;
use App\Models\Sale;

class DocumentTemplateService
{
    /**
     * Resolve all merge tags and return the rendered HTML body.
     */
    public function render(DocumentTemplate $template, Opportunity $opportunity, ?Sale $sale = null): string
    {
        $opportunity->loadMissing(['parentCustomer', 'jobSiteCustomer', 'projectManager']);

        $customer = $opportunity->parentCustomer;
        $jobSite  = $opportunity->jobSiteCustomer;
        $pm       = $opportunity->projectManager;

        $customerName = $customer?->company_name ?: ($customer?->name ?? '');

        $jobSiteName = $jobSite?->company_name ?: ($jobSite?->name ?? '');

        $jobSiteAddress = implode(', ', array_filter([
            $jobSite?->address,
            $jobSite?->city,
            $jobSite?->province,
            $jobSite?->postal_code,
        ]));

        $pmName = $pm?->name ?? '';

        $vars = [
            'customer_name'    => $customerName,
            'job_name'         => $opportunity->job_name ?? '',
            'job_no'           => $opportunity->job_no ?? '',
            'job_site_name'    => $jobSiteName,
            'job_site_address' => $jobSiteAddress,
            'job_site_phone'   => $jobSite?->phone ?? '',
            'job_site_email'   => $jobSite?->email ?? '',
            'pm_name'          => $pmName,
            'pm_first_name'    => explode(' ', trim($pmName))[0] ?? '',
            'pm_phone'         => $pm?->phone ?? '',
            'pm_email'         => $pm?->email ?? '',
            'date'             => now()->format('F j, Y'),
            'generated_by'     => auth()->user()?->name ?? '',
        ];

        if ($template->needs_sale && $sale) {
            $vars['sale_number']          = $sale->sale_number;
            $vars['flooring_items_table'] = $this->buildFlooringTable($sale);
        }

        $body = $template->body;
        foreach ($vars as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value ?? '', $body);
        }

        return $body;
    }

    /**
     * Build an HTML table of material items grouped by room for the given sale.
     */
    private function buildFlooringTable(Sale $sale): string
    {
        $sale->loadMissing('rooms.items');

        $rows = '';
        $hasRows = false;

        foreach ($sale->rooms as $room) {
            $materialItems = $room->items->where('type', 'material')->values();

            if ($materialItems->isEmpty()) {
                continue;
            }

            $hasRows = true;
            $count   = $materialItems->count();

            foreach ($materialItems as $i => $item) {
                $productName = implode(' — ', array_filter([
                    $item->product_type,
                    $item->manufacturer,
                    $item->style,
                    $item->color_item_number,
                ])) ?: ($item->item_name ?? '');

                $rows .= '<tr>';

                if ($i === 0) {
                    $rows .= '<td rowspan="' . $count . '" style="vertical-align:top; font-weight:bold; padding:6px 8px; border:1px solid #ccc; background:#f5f5f5;">'
                           . e($room->room_name)
                           . '</td>';
                }

                $rows .= '<td style="padding:6px 8px; border:1px solid #ccc;">' . e($productName) . '</td>';
                $rows .= '<td style="padding:6px 8px; border:1px solid #ccc; text-align:center;">' . number_format((float) $item->quantity, 2) . '</td>';
                $rows .= '<td style="padding:6px 8px; border:1px solid #ccc; text-align:center;">' . e($item->unit ?? '') . '</td>';
                $rows .= '</tr>';
            }
        }

        if (! $hasRows) {
            return '<p style="color:#888; font-style:italic;">No material items found on this sale.</p>';
        }

        return '<table style="width:100%; border-collapse:collapse; font-size:11px;">'
             . '<thead>'
             . '<tr style="background:#1d4ed8; color:#fff;">'
             . '<th style="padding:7px 8px; text-align:left; border:1px solid #1d4ed8;">Room</th>'
             . '<th style="padding:7px 8px; text-align:left; border:1px solid #1d4ed8;">Product</th>'
             . '<th style="padding:7px 8px; text-align:center; border:1px solid #1d4ed8;">Qty</th>'
             . '<th style="padding:7px 8px; text-align:center; border:1px solid #1d4ed8;">Unit</th>'
             . '</tr>'
             . '</thead>'
             . '<tbody>' . $rows . '</tbody>'
             . '</table>';
    }
}
