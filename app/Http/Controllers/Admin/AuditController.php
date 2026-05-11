<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    private const MODEL_LABELS = [
        'App\\Models\\Estimate'       => 'Estimate',
        'App\\Models\\EstimateItem'   => 'Estimate Item',
        'App\\Models\\Sale'           => 'Sale',
        'App\\Models\\SaleItem'       => 'Sale Item',
        'App\\Models\\Invoice'        => 'Invoice',
        'App\\Models\\InvoicePayment' => 'Invoice Payment',
    ];

    private const FIELD_LABELS = [
        'status'               => 'Status',
        'sell_price'           => 'Sell Price',
        'cost_price'           => 'Cost Price',
        'cost_total'           => 'Cost Total',
        'line_total'           => 'Line Total',
        'quantity'             => 'Quantity',
        'order_qty'            => 'Order Qty',
        'grand_total'          => 'Grand Total',
        'pretax_total'         => 'Pretax Total',
        'subtotal_materials'   => 'Materials Subtotal',
        'subtotal_labour'      => 'Labour Subtotal',
        'subtotal_freight'     => 'Freight Subtotal',
        'tax_amount'           => 'Tax Amount',
        'tax_rate_percent'     => 'Tax Rate %',
        'amount'               => 'Amount',
        'payment_method'       => 'Payment Method',
        'payment_date'         => 'Payment Date',
        'reference_number'     => 'Reference #',
        'style'                => 'Style',
        'color_item_number'    => 'Color / Item #',
        'description'          => 'Description',
        'notes'                => 'Notes',
        'estimate_number'      => 'Estimate #',
        'invoice_number'       => 'Invoice #',
        'customer_name'        => 'Customer',
        'job_name'             => 'Job Name',
        'homeowner_name'       => 'Homeowner',
    ];

    public function index(Request $request)
    {
        $query = Audit::with('user')
            ->whereIn('auditable_type', array_keys(self::MODEL_LABELS))
            ->latest();

        if ($request->filled('model_type')) {
            $query->where('auditable_type', $request->model_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $audits    = $query->paginate(50)->withQueryString();
        $users     = User::orderBy('name')->get(['id', 'name']);
        $modelLabels = self::MODEL_LABELS;

        return view('admin.audits.index', compact('audits', 'users', 'modelLabels'));
    }

    public function show(Audit $audit)
    {
        $audit->load('user');

        $modelLabel = self::MODEL_LABELS[$audit->auditable_type] ?? class_basename($audit->auditable_type);
        $fieldLabels = self::FIELD_LABELS;

        // Try to load the auditable record for a friendly identifier
        $record = null;
        $recordLabel = null;
        try {
            $record = $audit->auditable_type::find($audit->auditable_id);
            $recordLabel = $this->recordLabel($audit->auditable_type, $record);
        } catch (\Throwable) {}

        return view('admin.audits.show', compact('audit', 'modelLabel', 'fieldLabels', 'recordLabel'));
    }

    private function recordLabel(string $type, $record): ?string
    {
        if (!$record) return null;

        return match ($type) {
            'App\\Models\\Estimate'       => $record->estimate_number,
            'App\\Models\\EstimateItem'   => $record->style ?? $record->description ?? "Item #{$record->id}",
            'App\\Models\\Sale'           => "Sale #{$record->sale_number}",
            'App\\Models\\SaleItem'       => $record->style ?? $record->description ?? "Item #{$record->id}",
            'App\\Models\\Invoice'        => $record->invoice_number,
            'App\\Models\\InvoicePayment' => "Payment #{$record->id}",
            default                       => "#{$record->id}",
        };
    }
}
