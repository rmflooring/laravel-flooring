<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Estimate;
use App\Models\EstimateRoom;
use App\Models\EstimateItem;
use App\Models\Sale;
use App\Models\SaleRoom;
use App\Models\SaleItem;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstimateController extends Controller
{
	public function index(Request $request)
{
    $q        = trim((string) $request->query('q', ''));
    $status   = $request->query('status', '');
    $dateFrom = $request->query('date_from', '');
    $dateTo   = $request->query('date_to', '');

    $estimates = Estimate::query()
        ->with(['sale:id,source_estimate_id'])
        ->when($q !== '', function ($query) use ($q) {
            $query->where(function ($sub) use ($q) {
                $sub->where('estimate_number', 'like', "%{$q}%")
                    ->orWhere('customer_name', 'like', "%{$q}%")
                    ->orWhere('job_name', 'like', "%{$q}%")
                    ->orWhere('job_no', 'like', "%{$q}%")
                    ->orWhere('pm_name', 'like', "%{$q}%");
            });
        })
        ->when($status !== '', fn($query) => $query->where('status', $status))
        ->when($dateFrom !== '', fn($query) => $query->whereDate('created_at', '>=', $dateFrom))
        ->when($dateTo !== '', fn($query) => $query->whereDate('created_at', '<=', $dateTo))
        ->orderByDesc('id')
        ->paginate(15)
        ->withQueryString();

    // NOTE: your statuses are lowercase in validation: draft,sent,approved,rejected
    $statusOptions = ['draft', 'sent', 'approved', 'rejected'];

    return view('admin.estimates.index', compact(
        'estimates',
        'statusOptions',
        'q',
        'status',
        'dateFrom',
        'dateTo' , 
    ));
}
	
    public function store(Request $request)
    {
        $data = $request->validate([
			'opportunity_id' => ['nullable', 'integer'],
            'parent_customer_name' => ['nullable', 'string', 'max:255'],
            'homeowner_name'       => ['nullable', 'string', 'max:255'],
            'homeowner_phone'      => ['nullable', 'string', 'max:50'],
            'homeowner_email'      => ['nullable', 'email', 'max:255'],
			'status' => ['required', 'in:draft,sent,revised,approved,rejected'],
            'job_name'             => ['nullable', 'string', 'max:255'],
            'job_number'           => ['nullable', 'string', 'max:255'],
            'job_address'          => ['nullable', 'string', 'max:255'],
            'job_street'           => ['nullable', 'string', 'max:255'],
            'job_city'             => ['nullable', 'string', 'max:100'],
            'job_province'         => ['nullable', 'string', 'max:100'],
            'job_postal'           => ['nullable', 'string', 'max:20'],
            'pm_name'              => ['nullable', 'string', 'max:255'],
			'salesperson_1_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
			'salesperson_2_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes'                => ['nullable', 'string'],
            'estimate_number'      => ['nullable', 'string', 'max:255'],
            'subtotal_materials'   => ['nullable', 'numeric'],
            'subtotal_labour'      => ['nullable', 'numeric'],
            'subtotal_freight'     => ['nullable', 'numeric'],
            'pretax_total'         => ['nullable', 'numeric'],
            'tax_group_id'         => ['nullable', 'integer'],
            'tax_rate_percent'     => ['nullable', 'numeric'],
            'tax_amount'           => ['nullable', 'numeric'],
            'grand_total'          => ['nullable', 'numeric'],
            'rooms'                => ['nullable', 'array'],
            'rooms.*.room_name'    => ['nullable', 'string', 'max:255'],
            'rooms.*.materials'    => ['nullable', 'array'],
            'rooms.*.materials.*.product_type'      => ['nullable', 'string'],
            'rooms.*.materials.*.manufacturer'      => ['nullable', 'string'],
            'rooms.*.materials.*.style'             => ['nullable', 'string'],
            'rooms.*.materials.*.color_item_number' => ['nullable', 'string'],
            'rooms.*.materials.*.po_notes'          => ['nullable', 'string'],
            'rooms.*.materials.*.quantity'          => ['nullable', 'numeric'],
            'rooms.*.materials.*.unit'              => ['nullable', 'string', 'max:50'],
			'rooms.*.materials.*.cost_price' => ['nullable', 'numeric'],
			'rooms.*.materials.*.cost_total' => ['nullable', 'numeric'],
            'rooms.*.materials.*.sell_price'        => ['nullable', 'numeric'],
            'rooms.*.materials.*.notes'             => ['nullable', 'string'],
			'rooms.*.materials.*.order_qty'  => ['nullable', 'numeric'],
			'rooms.*.materials.*.line_total' => ['nullable', 'numeric'],
			'rooms.*.freight.*.line_total'   => ['nullable', 'numeric'],
			'rooms.*.labour.*.line_total'    => ['nullable', 'numeric'],
            'rooms.*.freight'                       => ['nullable', 'array'],
            'rooms.*.freight.*.freight_description' => ['nullable', 'string'],
            'rooms.*.freight.*.quantity'            => ['nullable', 'numeric'],
            'rooms.*.freight.*.unit'                => ['nullable', 'string', 'max:50'],
			'rooms.*.freight.*.cost_price' => ['nullable', 'numeric'],
			'rooms.*.freight.*.cost_total' => ['nullable', 'numeric'],
            'rooms.*.freight.*.sell_price'          => ['nullable', 'numeric'],
            'rooms.*.freight.*.notes'               => ['nullable', 'string'],
            'rooms.*.labour'                        => ['nullable', 'array'],
            'rooms.*.labour.*.labour_type'          => ['nullable', 'string'],
            'rooms.*.labour.*.description'          => ['nullable', 'string'],
            'rooms.*.labour.*.quantity'             => ['nullable', 'numeric'],
            'rooms.*.labour.*.unit'                 => ['nullable', 'string', 'max:50'],
			'rooms.*.labour.*.cost_price' => ['nullable', 'numeric'],
			'rooms.*.labour.*.cost_total' => ['nullable', 'numeric'],
            'rooms.*.labour.*.sell_price'           => ['nullable', 'numeric'],
            'rooms.*.labour.*.notes'                => ['nullable', 'string'],
            'rooms.*.labour.*.order_qty'            => ['nullable', 'numeric'],
        ]);


		// Force tax_rate_percent to be the TAX GROUP TOTAL (not the effective %)
$taxGroupId = $data['tax_group_id'] ?? null;

$rateCol = 'sales_rate';
foreach (['tax_rate_sales', 'sales_rate'] as $candidate) {
    if (\Schema::hasColumn('tax_rates', $candidate)) {
        $rateCol = $candidate;
        break;
    }
}

$groupPercent = $taxGroupId
    ? (float) \DB::table('tax_rate_group_items as tgi')
        ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
        ->where('tgi.tax_rate_group_id', $taxGroupId)
        ->sum("tr.$rateCol")
    : 0.0;

$data['tax_rate_percent'] = $groupPercent;

        $rooms = $data['rooms'] ?? [];

        $estimate = DB::transaction(function () use ($data, $rooms) {
            $incomingEstimateNo = trim((string)($data['estimate_number'] ?? ''));
            if ($incomingEstimateNo === '') {
                $year = now()->format('Y');
                $last = Estimate::where('estimate_number', 'like', $year . '-%')
                    ->whereNull('parent_estimate_id')
                    ->lockForUpdate()
                    ->orderBy('estimate_number', 'desc')
                    ->value('estimate_number');
                $nextSeq = 1;
                if ($last && preg_match('/^' . $year . '-(\d{3})$/', $last, $m)) {
                    $nextSeq = ((int) $m[1]) + 1;
                }
                $incomingEstimateNo = $year . '-' . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
            }

            $estimate = Estimate::create([
                'estimate_number'    => $incomingEstimateNo,
                'revision_no'        => 0,
				'opportunity_id' => $data['opportunity_id'] ?? null,
                'status'             => 'draft',
                'customer_name'      => $data['parent_customer_name'] ?? null,
                'homeowner_name'     => $data['homeowner_name'] ?? null,
                'homeowner_phone'    => $data['homeowner_phone'] ?? null,
                'homeowner_email'    => $data['homeowner_email'] ?? null,
                'job_name'           => $data['job_name'] ?? null,
                'job_no'             => $data['job_number'] ?? null,
                'job_address'        => $this->buildJobAddress($data),
                'pm_name'            => $data['pm_name'] ?? null,
				'salesperson_1_employee_id' => $data['salesperson_1_employee_id'] ?? null,
				'salesperson_2_employee_id' => $data['salesperson_2_employee_id'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'subtotal_materials' => (float)($data['subtotal_materials'] ?? 0),
                'subtotal_labour'    => (float)($data['subtotal_labour'] ?? 0),
                'subtotal_freight'   => (float)($data['subtotal_freight'] ?? 0),
                'pretax_total'       => (float)($data['pretax_total'] ?? 0),
                'tax_group_id'       => $data['tax_group_id'] ?? null,
                'tax_rate_percent' => (function () use ($data) {

    $taxGroupId = $data['tax_group_id'] ?? null;
    if (!$taxGroupId) return 0;

    $rateCol = 'sales_rate';
    foreach (['tax_rate_sales', 'sales_rate'] as $candidate) {
        if (\Schema::hasColumn('tax_rates', $candidate)) {
            $rateCol = $candidate;
            break;
        }
    }

    return (float) \DB::table('tax_rate_group_items as tgi')
        ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
        ->where('tgi.tax_rate_group_id', $taxGroupId)
        ->sum("tr.$rateCol");

})(),
                'tax_amount'         => (float)($data['tax_amount'] ?? 0),
                'grand_total'        => (float)($data['grand_total'] ?? 0),
                'created_by'         => auth()->id(),
                'updated_by'         => auth()->id(),
            ]);

            foreach ($rooms as $roomIndex => $room) {
                $roomModel = EstimateRoom::create([
                    'estimate_id'        => $estimate->id,
                    'room_name'          => $room['room_name'] ?? null,
                    'sort_order'         => (int) $roomIndex,
                    'subtotal_materials' => (float)($room['subtotal_materials'] ?? 0),
                    'subtotal_labour'    => (float)($room['subtotal_labour'] ?? 0),
                    'subtotal_freight'   => (float)($room['subtotal_freight'] ?? 0),
                    'room_total'         => (float)($room['room_total'] ?? 0),
                ]);

                foreach (($room['materials'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['product_type', 'quantity', 'sell_price'])) continue;

					\Log::info('[Estimate update] labour item about to create', [
						'room_id' => $roomModel->id,
						'index' => $i,
						'item' => $item,
					]);
					
                    EstimateItem::create([
                        'estimate_id'        => $estimate->id,
                        'estimate_room_id'   => $roomModel->id,
                        'item_type'          => 'material',
                        'sort_order'         => (int) $i,
                        'product_type'       => $item['product_type'] ?? null,
                        'product_line_id'    => ($item['product_line_id'] ?? '') !== '' ? (int)$item['product_line_id'] : null,
                        'product_style_id'   => ($item['product_style_id'] ?? '') !== '' ? (int)$item['product_style_id'] : null,
                        'manufacturer'       => $item['manufacturer'] ?? null,
                        'style'              => $item['style'] ?? null,
                        'color_item_number'  => $item['color_item_number'] ?? null,
                        'po_notes'           => $item['po_notes'] ?? null,
                        'quantity'           => (float)($item['quantity'] ?? 0),
                        'order_qty'          => ($item['order_qty'] ?? '') !== '' ? (float)$item['order_qty'] : null,
                        'unit'               => $item['unit'] ?? null,
						'cost_price' => (float)($item['cost_price'] ?? 0),
						'cost_total' => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                        'sell_price'         => (float)($item['sell_price'] ?? 0),
                        'line_total'         => (float)($item['line_total'] ?? 0),
                        'notes'              => $item['notes'] ?? null,
                    ]);

					Log::info('[Estimate store] material item saved', [
    'estimate_id' => $estimate->id,
    'room_id'     => $roomModel->id,
    'qty'         => (float)($item['quantity'] ?? 0),
    'sell_price'  => (float)($item['sell_price'] ?? 0),
    'cost_price'  => (float)($item['cost_price'] ?? 0),
]);
                }

                foreach (($room['freight'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['freight_description', 'quantity', 'sell_price'])) continue;
//for logging freight
					Log::info('[Estimate store] freight incoming', [
    'keys' => array_keys($item),
    'qty'  => $item['quantity'] ?? null,
    'sell' => $item['sell_price'] ?? null,
    'cost' => $item['cost_price'] ?? null,
    'raw'  => $item,
]);
					
                    EstimateItem::create([
                        'estimate_id'        => $estimate->id,
                        'estimate_room_id'   => $roomModel->id,
                        'item_type'          => 'freight',
                        'sort_order'         => (int) $i,
                        'freight_description' => $item['freight_description'] ?? null,
                        'quantity'           => (float)($item['quantity'] ?? 0),
                        'unit'               => $item['unit'] ?? null,
						'cost_price'          => (float)($item['cost_price'] ?? 0),
						'cost_total'          => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                        'sell_price'         => (float)($item['sell_price'] ?? 0),
                        'line_total'         => (float)($item['line_total'] ?? 0),
                        'notes'              => $item['notes'] ?? null,
                    ]);
                }

                foreach (($room['labour'] ?? []) as $i => $item) {
                    if ($this->isRowEmpty($item, ['labour_type', 'quantity', 'sell_price', 'description'])) continue;

					//for logginf labour
					Log::info('[Estimate store] labour incoming', [
    'keys' => array_keys($item),
    'qty'  => $item['quantity'] ?? null,
    'sell' => $item['sell_price'] ?? null,
    'cost' => $item['cost_price'] ?? null,
    'raw'  => $item,
]);
					
                    EstimateItem::create([
                        'estimate_id'        => $estimate->id,
                        'estimate_room_id'   => $roomModel->id,
                        'item_type'          => 'labour',
                        'sort_order'         => (int) $i,
                        'labour_type'        => $item['labour_type'] ?? null,
                        'description'        => $item['description'] ?? null,
                        'quantity'           => (float)($item['quantity'] ?? 0),
                        'order_qty'          => ($item['order_qty'] ?? '') !== '' ? (float)$item['order_qty'] : null,
                        'unit'               => $item['unit'] ?? null,
						'cost_price'       => (float)($item['cost_price'] ?? 0),
						'cost_total'       => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                        'sell_price'         => (float)($item['sell_price'] ?? 0),
                        'line_total'         => (float)($item['line_total'] ?? 0),
                        'notes'              => $item['notes'] ?? null,
                    ]);
                }
            }

            return $estimate;
        });

        return redirect()->route('pages.estimates.edit', $estimate->id)
            ->with('success', 'Estimate saved (Draft).')
            ->with('estimate_id', $estimate->id);
    }

	public function show(Estimate $estimate)
	{
		$estimate->load([
			'rooms' => fn($q) => $q->orderBy('sort_order'),
			'rooms.items' => fn($q) => $q->orderBy('sort_order'),
			'salesperson1Employee',
			'salesperson2Employee',
			'sale',
			'revisions' => fn($q) => $q->orderBy('revision_no'),
			'opportunity',
			'parentEstimate',
		]);

		$user            = auth()->user();
		$templateService = app(EmailTemplateService::class);
		$template        = $templateService->getTemplate($user, 'estimate');
		$templateVars    = [
			'customer_name'    => $estimate->homeowner_name ?: $estimate->customer_name,
			'estimate_number'  => $estimate->estimate_number,
			'grand_total'      => '$' . number_format((float) $estimate->grand_total, 2),
			'job_name'         => $estimate->job_name,
			'job_no'           => $estimate->job_no,
			'job_address'      => $estimate->job_address,
			'pm_name'          => $estimate->pm_name,
			'pm_first_name'    => explode(' ', trim($estimate->pm_name ?? ''))[0],
			'salesperson_name' => $estimate->salesperson1Employee?->first_name
				? $estimate->salesperson1Employee->first_name . ' ' . $estimate->salesperson1Employee->last_name
				: $user->name,
			'sender_name'      => $user->name,
			'sender_email'     => $user->email,
		];
		$emailSubject = $templateService->render($template['subject'], $templateVars);
		$emailBody    = $templateService->render($template['body'], $templateVars);

		return view('pages.estimates.show', compact('estimate', 'emailSubject', 'emailBody'));
	}

	public function edit(Estimate $estimate)
	{
		$estimate->load([
			'rooms' => fn($q) => $q->orderBy('sort_order'),
			'rooms.items' => fn($q) => $q->orderBy('sort_order'),
			'rooms.items.productStyle',
			'salesperson1Employee',
			'salesperson2Employee',
			'opportunity.projectManager',
			'sale',
			'parentEstimate',
		]);

		$taxGroups = DB::table('tax_rate_groups')
			->select('tax_rate_groups.*')
			->whereNull('tax_rate_groups.deleted_at')
			->orderBy('tax_rate_groups.name')
			->get();

		$defaultTaxGroupId = DB::table('default_tax')->where('id', 1)->value('tax_rate_group_id');

		$employees = \App\Models\Employee::orderBy('first_name')->orderBy('last_name')->get();

		// Pre-render the estimate email template for the Send Email modal
		$user            = auth()->user();
		$templateService = app(EmailTemplateService::class);
		$template        = $templateService->getTemplate($user, 'estimate');
		$templateVars    = [
			'customer_name'    => $estimate->homeowner_name ?: $estimate->customer_name,
			'estimate_number'  => $estimate->estimate_number,
			'grand_total'      => '$' . number_format((float) $estimate->grand_total, 2),
			'job_name'         => $estimate->job_name,
			'job_no'           => $estimate->job_no,
			'job_address'      => $estimate->job_address,
			'pm_name'          => $estimate->pm_name,
			'pm_first_name'    => explode(' ', trim($estimate->pm_name ?? ''))[0],
			'salesperson_name' => $estimate->salesperson1Employee?->first_name
				? $estimate->salesperson1Employee->first_name . ' ' . $estimate->salesperson1Employee->last_name
				: $user->name,
			'sender_name'      => $user->name,
			'sender_email'     => $user->email,
		];
		$emailSubject = $templateService->render($template['subject'], $templateVars);
		$emailBody    = $templateService->render($template['body'], $templateVars);

		$pmEmail = $estimate->opportunity?->projectManager?->email;

		return view('admin.estimates.edit', compact(
			'estimate', 'taxGroups', 'defaultTaxGroupId', 'employees',
			'emailSubject', 'emailBody', 'pmEmail',
		));
	}

public function update(Request $request, Estimate $estimate)
{
    if ($estimate->status === 'approved') {
        return back()->with('error', 'This estimate is approved and locked. Use "Make Revision" to create an editable copy.');
    }

    $data = $request->validate([
        'parent_customer_name' => ['nullable', 'string', 'max:255'],
        'pm_name'              => ['nullable', 'string', 'max:255'],
        'job_number'           => ['nullable', 'string', 'max:255'],
        'job_name'             => ['nullable', 'string', 'max:255'],
        'job_address'          => ['nullable', 'string', 'max:255'],
		'salesperson_1_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
		'salesperson_2_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        'notes'                => ['nullable', 'string'],
		'status' => ['required', 'in:draft,sent,revised,approved,rejected'],
		
		'homeowner_name'  => ['nullable', 'string', 'max:255'],
		'homeowner_phone' => ['nullable', 'string', 'max:255'],
		'homeowner_email' => ['nullable', 'string', 'max:255'],

        'subtotal_materials'   => ['nullable', 'numeric'],
        'subtotal_labour'      => ['nullable', 'numeric'],
        'subtotal_freight'     => ['nullable', 'numeric'],
        'pretax_total'         => ['nullable', 'numeric'],
        'tax_amount'           => ['nullable', 'numeric'],
        'grand_total'          => ['nullable', 'numeric'],
        'tax_group_id'         => ['nullable', 'integer'],
        'tax_rate_percent'     => ['nullable', 'numeric'],

        'rooms'                => ['nullable', 'array'],
        'rooms.*.id'           => ['nullable', 'integer'],
        'rooms.*.room_name'    => ['nullable', 'string', 'max:255'],

        'rooms.*.materials'    => ['nullable', 'array'],
        'rooms.*.freight'      => ['nullable', 'array'],
        'rooms.*.labour'                 => ['nullable', 'array'],
'rooms.*.labour.*.id'            => ['nullable', 'integer'],
'rooms.*.labour.*.line_item_order' => ['nullable', 'integer'],
'rooms.*.labour.*.labour_type'   => ['nullable', 'string'],
'rooms.*.labour.*.description'   => ['nullable', 'string'],
'rooms.*.labour.*.quantity'      => ['nullable', 'numeric'],
'rooms.*.labour.*.unit'          => ['nullable', 'string', 'max:50'],
'rooms.*.labour.*.notes'         => ['nullable', 'string'],
'rooms.*.labour.*.sell_price'    => ['nullable', 'numeric'],
'rooms.*.labour.*.line_total'    => ['nullable', 'numeric'],
'rooms.*.labour.*.cost_price'    => ['nullable', 'numeric'],
'rooms.*.labour.*.cost_total'    => ['nullable', 'numeric'],
'rooms.*.labour.*.order_qty'     => ['nullable', 'numeric'],

		'rooms.*.materials.*.product_type'      => ['nullable', 'string'],
'rooms.*.materials.*.manufacturer'      => ['nullable', 'string'],
'rooms.*.materials.*.style'             => ['nullable', 'string'],
'rooms.*.materials.*.color_item_number' => ['nullable', 'string'],
'rooms.*.materials.*.po_notes'          => ['nullable', 'string'],
'rooms.*.materials.*.quantity'          => ['nullable', 'numeric'],
'rooms.*.materials.*.unit'              => ['nullable', 'string', 'max:50'],
'rooms.*.materials.*.cost_price'        => ['nullable', 'numeric'],
'rooms.*.materials.*.cost_total'        => ['nullable', 'numeric'],
'rooms.*.materials.*.sell_price'        => ['nullable', 'numeric'],
'rooms.*.materials.*.line_total'        => ['nullable', 'numeric'],
'rooms.*.materials.*.order_qty'         => ['nullable', 'numeric'],
'rooms.*.materials.*.notes'             => ['nullable', 'string'],

'rooms.*.freight.*.freight_description' => ['nullable', 'string'],
'rooms.*.freight.*.quantity'            => ['nullable', 'numeric'],
'rooms.*.freight.*.unit'                => ['nullable', 'string', 'max:50'],
'rooms.*.freight.*.cost_price'          => ['nullable', 'numeric'],
'rooms.*.freight.*.cost_total'          => ['nullable', 'numeric'],
'rooms.*.freight.*.sell_price'          => ['nullable', 'numeric'],
'rooms.*.freight.*.line_total'          => ['nullable', 'numeric'],
'rooms.*.freight.*.notes'               => ['nullable', 'string'],
    ]);

			\Log::info('[Estimate update] raw vs validated labour', [
				'raw_rooms' => $request->input('rooms'),
				'validated_rooms' => $data['rooms'] ?? null,
			]);

	
	// Force tax_rate_percent to be the TAX GROUP TOTAL (not the effective %)
$taxGroupId = $data['tax_group_id'] ?? null;

$rateCol = 'sales_rate';
foreach (['tax_rate_sales', 'sales_rate'] as $candidate) {
    if (\Schema::hasColumn('tax_rates', $candidate)) {
        $rateCol = $candidate;
        break;
    }
}

$groupPercent = $taxGroupId
    ? (float) \DB::table('tax_rate_group_items as tgi')
        ->join('tax_rates as tr', 'tr.id', '=', 'tgi.tax_rate_id')
        ->where('tgi.tax_rate_group_id', $taxGroupId)
        ->sum("tr.$rateCol")
    : 0.0;

$data['tax_rate_percent'] = $groupPercent;

    DB::transaction(function () use ($estimate, $data) {

		// --- Server-side tax calc (authoritative) ---
$taxGroupId = $data['tax_group_id'] ?? $estimate->tax_group_id;

$taxRatePercent = 0.0;

if (!empty($taxGroupId)) {
    $taxRatePercent = (float) DB::table('tax_rate_group_items as gi')
        ->join('tax_rates as tr', 'tr.id', '=', 'gi.tax_rate_id')
        ->where('gi.tax_rate_group_id', (int) $taxGroupId)
        ->sum('tr.sales_rate');// percent values (e.g. 5.00)
}

$pretaxTotal = (float) ($data['pretax_total'] ?? 0);

$taxAmount  = round($pretaxTotal * ($taxRatePercent / 100), 2);
$grandTotal = round($pretaxTotal + $taxAmount, 2);

		// --- Tax calculation (GST/PST can apply to different bases) ---
$taxGroupId = $data['tax_group_id'] ?? $estimate->tax_group_id;

$subtotalMaterials = (float) ($data['subtotal_materials'] ?? 0);
$subtotalLabour    = (float) ($data['subtotal_labour'] ?? 0);
$subtotalFreight   = (float) ($data['subtotal_freight'] ?? 0);

$pretaxTotal = (float) ($data['pretax_total'] ?? ($subtotalMaterials + $subtotalLabour + $subtotalFreight));

$taxAmount = 0.0;
$effectivePercent = 0.0;

if ($taxGroupId) {
    $taxRates = DB::table('tax_rate_group_items as gi')
        ->join('tax_rates as tr', 'tr.id', '=', 'gi.tax_rate_id')
        ->where('gi.tax_rate_group_id', (int) $taxGroupId)
        ->select('tr.sales_rate', 'tr.applies_to')
        ->get();

    foreach ($taxRates as $tr) {
        $rate = (float) ($tr->sales_rate ?? 0);

        $base = match ($tr->applies_to) {
            'materials' => $subtotalMaterials,
            'labour'    => $subtotalLabour,
            'freight'   => $subtotalFreight,
            default     => $pretaxTotal, // 'all' or anything unknown
        };

        $taxAmount += ($base * ($rate / 100));
    }

    $taxAmount = round($taxAmount, 2);

    // Effective % for display: tax / pretax (handles mixed bases)
    $effectivePercent = $pretaxTotal > 0 ? round(($taxAmount / $pretaxTotal) * 100, 3) : 0.0;
}

		\Log::info('[Estimate update] homeowner before save', [
    'incoming_name'  => $data['homeowner_name']  ?? '(missing)',
    'incoming_phone' => $data['homeowner_phone'] ?? '(missing)',
    'incoming_email' => $data['homeowner_email'] ?? '(missing)',
    'db_name'        => $estimate->homeowner_name,
    'db_phone'       => $estimate->homeowner_phone,
    'db_email'       => $estimate->homeowner_email,
]);

        // 1) Update estimate header + totals
        $estimate->forceFill([
            'customer_name'      => $data['parent_customer_name'] ?? $estimate->customer_name,
            'pm_name'            => $data['pm_name'] ?? $estimate->pm_name,
			'salesperson_1_employee_id' => $data['salesperson_1_employee_id'] ?? null,
			'salesperson_2_employee_id' => $data['salesperson_2_employee_id'] ?? null,
            'job_no'             => $data['job_number'] ?? $estimate->job_no,
            'job_name'           => $data['job_name'] ?? $estimate->job_name,
            'job_address'        => $data['job_address'] ?? $estimate->job_address,
			'homeowner_name'  => $data['homeowner_name'] ?? $estimate->homeowner_name,
			'homeowner_phone' => $data['homeowner_phone'] ?? $estimate->homeowner_phone,
			'homeowner_email' => $data['homeowner_email'] ?? $estimate->homeowner_email,
            'notes'              => $data['notes'] ?? $estimate->notes,
			'status' => $data['status'],

            'subtotal_materials' => (float)($data['subtotal_materials'] ?? 0),
            'subtotal_labour'    => (float)($data['subtotal_labour'] ?? 0),
            'subtotal_freight'   => (float)($data['subtotal_freight'] ?? 0),
            'pretax_total'       => $pretaxTotal,
			
            'tax_group_id'       => $taxGroupId,
			'tax_rate_percent'   => $effectivePercent,
			'tax_amount'         => $taxAmount,
			'grand_total'        => round($pretaxTotal + $taxAmount, 2),

            'updated_by'         => auth()->id(),
        ])->save();

        // 2) Rooms + items
        $rooms = $data['rooms'] ?? [];
		
		// DELETE rooms that were removed in the UI
$existingRoomIds = $estimate->rooms()->pluck('id')->all();

$submittedRoomIds = collect($rooms)
    ->pluck('id')
    ->filter()
    ->map(fn ($v) => (int) $v)
    ->all();

$roomIdsToDelete = array_values(array_diff($existingRoomIds, $submittedRoomIds));

if (!empty($roomIdsToDelete)) {
    EstimateItem::where('estimate_id', $estimate->id)
        ->whereIn('estimate_room_id', $roomIdsToDelete)
        ->delete();

    EstimateRoom::where('estimate_id', $estimate->id)
        ->whereIn('id', $roomIdsToDelete)
        ->delete();
}

        foreach ($rooms as $roomIndex => $roomData) {
            $roomId = $roomData['id'] ?? null;
			
			\Log::info('[Estimate update] room costs payload', [
    'room_index' => $roomIndex,
    'materials' => $roomData['materials'] ?? [],
    'freight' => $roomData['freight'] ?? [],
    'labour' => $roomData['labour'] ?? [],
]);

// Create or load the room
if ($roomId) {
    $room = EstimateRoom::where('id', $roomId)
        ->where('estimate_id', $estimate->id)
        ->firstOrFail();
} else {
    $room = new EstimateRoom();
    $room->estimate_id = $estimate->id;
}

// Save room meta (both update + create)
$room->room_name  = $roomData['room_name'] ?? null;
$room->sort_order = (int)$roomIndex;
$room->save();

// IMPORTANT: use the saved ID for items
$roomId = $room->id;

			\Log::info('[Estimate update] room payload before item delete', [
    'room_index' => $roomIndex,
    'room_id' => $roomId,
    'room_name' => $roomData['room_name'] ?? null,
    'materials_count' => count($roomData['materials'] ?? []),
    'freight_count' => count($roomData['freight'] ?? []),
    'labour_count' => count($roomData['labour'] ?? []),
    'labour_payload' => $roomData['labour'] ?? [],
]);
			//added log
			\Log::info('[Estimate update] before delete room items', [
    'room_id' => $roomId,
    'existing_items' => EstimateItem::where('estimate_id', $estimate->id)
        ->where('estimate_room_id', $roomId)
        ->get(['id', 'item_type', 'labour_type', 'description', 'quantity', 'sell_price'])
        ->toArray(),
]);
			
            // delete all items for this room then re-insert
            EstimateItem::where('estimate_id', $estimate->id)
                ->where('estimate_room_id', $roomId)
                ->delete();

            // MATERIALS
            foreach (($roomData['materials'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['product_type', 'quantity', 'sell_price'])) continue;

                EstimateItem::create([
                    'estimate_id'       => $estimate->id,
                    'estimate_room_id'  => $roomId,
                    'item_type'         => 'material',
                    'sort_order'        => (int)$i,
                    'product_type'      => $item['product_type'] ?? null,
                    'product_line_id'   => ($item['product_line_id'] ?? '') !== '' ? (int)$item['product_line_id'] : null,
                    'product_style_id'  => ($item['product_style_id'] ?? '') !== '' ? (int)$item['product_style_id'] : null,
                    'manufacturer'      => $item['manufacturer'] ?? null,
                    'style'             => $item['style'] ?? null,
                    'color_item_number' => $item['color_item_number'] ?? null,
                    'po_notes'          => $item['po_notes'] ?? null,
                    'quantity'          => (float)($item['quantity'] ?? 0),
                    'unit'              => $item['unit'] ?? null,
					'cost_price'        => (float)($item['cost_price'] ?? 0),
					'cost_total'        => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                    'sell_price'        => (float)($item['sell_price'] ?? 0),
                    'line_total'        => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                    'notes'             => $item['notes'] ?? null,
                ]);
            }

            // FREIGHT
            foreach (($roomData['freight'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['freight_description', 'quantity', 'sell_price'])) continue;

                EstimateItem::create([
                    'estimate_id'        => $estimate->id,
                    'estimate_room_id'   => $roomId,
                    'item_type'          => 'freight',
                    'sort_order'         => (int)$i,
                    'freight_description'=> $item['freight_description'] ?? null,
                    'quantity'           => (float)($item['quantity'] ?? 0),
                    'unit'               => $item['unit'] ?? null,
					'cost_price' => (float)($item['cost_price'] ?? 0),
					'cost_total' => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                    'sell_price'         => (float)($item['sell_price'] ?? 0),
                    'line_total'         => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                    'notes'              => $item['notes'] ?? null,
                ]);
            }

            // LABOUR
            foreach (($roomData['labour'] ?? []) as $i => $item) {
                if ($this->isRowEmpty($item, ['labour_type', 'description', 'quantity', 'sell_price'])) continue;

                EstimateItem::create([
                    'estimate_id'      => $estimate->id,
                    'estimate_room_id' => $roomId,
                    'item_type'        => 'labour',
                    'sort_order'       => (int)$i,
                    'labour_type'      => $item['labour_type'] ?? null,
                    'description'      => $item['description'] ?? null,
                    'quantity'         => (float)($item['quantity'] ?? 0),
                    'unit'             => $item['unit'] ?? null,
					'cost_price' => (float)($item['cost_price'] ?? 0),
					'cost_total' => round((float)($item['quantity'] ?? 0) * (float)($item['cost_price'] ?? 0), 2),
                    'sell_price'       => (float)($item['sell_price'] ?? 0),
                    'line_total'       => round((float)($item['quantity'] ?? 0) * (float)($item['sell_price'] ?? 0), 2),
                    'notes'            => $item['notes'] ?? null,
                ]);
            }
        }
    });

    return redirect()
        ->route('pages.estimates.edit', $estimate)
        ->with('success', 'Estimate updated.');
}

public function saveProfitCosts(Request $request, Estimate $estimate)
{
    $data = $request->validate([
        'items' => ['required', 'array'],
        'items.*.id' => ['required', 'integer'],
        'items.*.cost_price' => ['nullable', 'numeric'],
    ]);

    foreach ($data['items'] as $row) {
        $item = EstimateItem::where('estimate_id', $estimate->id)
            ->where('id', $row['id'])
            ->first();

        if (!$item) {
            continue;
        }

        $qty = (float) ($item->quantity ?? 0);
        $costPrice = (float) ($row['cost_price'] ?? 0);

        $item->cost_price = $costPrice;
        $item->cost_total = round($qty * $costPrice, 2);
        $item->save();
    }

    return redirect()
    ->route('pages.estimates.profits.show', $estimate->id)
    ->with('success', 'Profit costs saved successfully.');
}


    private function isRowEmpty(array $row, array $keysToCheck): bool
    {
        foreach ($keysToCheck as $key) {
            if (!empty($row[$key])) {
                return false;
            }
        }
        return true;
    }
	
	public function apiProductTypes()
{
    $rows = \App\Models\ProductType::query()
        ->where('status', 'active')
        ->with([
            'soldByUnit:id,code,label,status',
        ])
        ->orderBy('name')
        ->get(['id', 'name', 'sold_by_unit_id']);

    return response()->json(
        $rows->map(function ($pt) {
            return [
                'id' => $pt->id,
                'name' => $pt->name,
                'sold_by_unit' => $pt->soldByUnit ? [
                    'id' => $pt->soldByUnit->id,
                    'code' => $pt->soldByUnit->code,
                    'label' => $pt->soldByUnit->label,
                ] : null,
            ];
        })
    );
}

public function apiManufacturers(Request $request)
{
    $request->validate([
        'product_type_id' => ['required', 'integer'],
    ]);

    $manufacturers = DB::table('product_lines')
        ->where('product_type_id', (int) $request->product_type_id)
        ->whereNotNull('manufacturer')
        ->where('manufacturer', '<>', '')
        ->distinct()
        ->orderBy('manufacturer')
        ->pluck('manufacturer')
        ->values();

    return response()->json([
        'manufacturers' => $manufacturers,
    ]);
}

public function apiStyles(Request $request)
{
    $productTypeId = (int) $request->query('product_type_id');
    $manufacturer  = trim((string) $request->query('manufacturer'));

    if (!$productTypeId || $manufacturer === '') {
        return response()->json([]);
    }

    // Find product_line IDs matching product type + manufacturer
    $lineIds = DB::table('product_lines')
        ->where('product_type_id', $productTypeId)
        ->whereNotNull('manufacturer')
        ->where('manufacturer', '<>', '')
        ->where('manufacturer', $manufacturer)
        ->pluck('id');

    if ($lineIds->isEmpty()) {
        return response()->json([]);
    }

    // Return styles for those product lines
    $styles = DB::table('product_styles')
        ->whereIn('product_line_id', $lineIds->all())
        ->where('status', 'active')   // remove this line if you don't use 'active'
        ->orderBy('name')
        ->get(['id', 'product_line_id', 'name', 'sku', 'style_number', 'sell_price']);

    return response()->json($styles);
}

	public function apiProductLines(\Illuminate\Http\Request $request)
{
    $productTypeId = (int) $request->query('product_type_id', 0);
    $manufacturer  = trim((string) $request->query('manufacturer', ''));

    if ($productTypeId <= 0 || $manufacturer === '') {
        return response()->json([]);
    }

    $lines = \DB::table('product_lines')
        ->select('id', 'name')
        ->where('status', 'active')
        ->where('product_type_id', $productTypeId)
        ->where('manufacturer', $manufacturer)
        ->orderBy('name')
        ->get();

    return response()->json($lines);
}

	public function destroy(\App\Models\Estimate $estimate)
{
    if (\App\Models\Sale::where('source_estimate_id', $estimate->id)->exists()) {
        return back()->with('error', 'This estimate cannot be deleted because it has an associated sale.');
    }

    $estimate->delete();

    return redirect()->route('pages.estimates.index')->with('success', 'Estimate deleted.');
}

	public function convertToSale(\App\Models\Estimate $estimate)
{
    abort_unless($estimate->status === 'approved', 422, 'Only approved estimates can be converted to a sale.');

    // Prevent duplicates (1 sale per estimate)
    $existing = \App\Models\Sale::where('source_estimate_id', $estimate->id)->first();
    if ($existing) {
        // NOTE: adjust this route if your sales edit route name differs
        return redirect()->route('pages.sales.edit', $existing->id)
            ->with('info', 'A sale already exists for this estimate.');
    }

    $sale = DB::transaction(function () use ($estimate) {

        // Create sale header
        $sale = \App\Models\Sale::create([
            'opportunity_id'            => $estimate->opportunity_id ?? null,
            'source_estimate_id'        => $estimate->id,
            'source_estimate_number'    => $estimate->estimate_number ?? null,

            'status' => 'open',

            'customer_name'             => $estimate->customer_name ?? null,
            'job_name'                  => $estimate->job_name ?? null,
            'job_no'                    => $estimate->job_no ?? null,
            'job_address'               => $estimate->job_address ?? null,
            'pm_name'                   => $estimate->pm_name ?? null,

            'homeowner_name'            => $estimate->homeowner_name ?? null,
            'job_phone'                 => $estimate->homeowner_phone ?? null,
            'job_email'                 => $estimate->homeowner_email ?? null,

            'salesperson_1_employee_id' => $estimate->salesperson_1_employee_id ?? null,
            'salesperson_2_employee_id' => $estimate->salesperson_2_employee_id ?? null,

            'notes'                     => $estimate->notes ?? null,

            // Totals
            'subtotal_materials'        => (float) ($estimate->subtotal_materials ?? 0),
            'subtotal_labour'           => (float) ($estimate->subtotal_labour ?? 0),
            'subtotal_freight'          => (float) ($estimate->subtotal_freight ?? 0),
            'pretax_total'              => (float) ($estimate->pretax_total ?? 0),

            'tax_group_id'              => $estimate->tax_group_id ?? null,
            'tax_rate_percent'          => (float) ($estimate->tax_rate_percent ?? 0),
            'tax_amount'                => (float) ($estimate->tax_amount ?? 0),
            'grand_total'               => (float) ($estimate->grand_total ?? 0),

            'created_by'                => auth()->id(),
            'updated_by'                => auth()->id(),
        ]);

        // Copy rooms
        $roomIdMap = []; // estimate_room_id => sale_room_id

        $estimateRooms = \App\Models\EstimateRoom::where('estimate_id', $estimate->id)
            ->orderBy('sort_order')
            ->get();

        foreach ($estimateRooms as $er) {
            $sr = \App\Models\SaleRoom::create([
                'sale_id'                 => $sale->id,
                'source_estimate_room_id' => $er->id,
                'room_name'               => $er->room_name,
                'sort_order'              => $er->sort_order,
                'subtotal_materials'      => (float) ($er->subtotal_materials ?? 0),
                'subtotal_labour'         => (float) ($er->subtotal_labour ?? 0),
                'subtotal_freight'        => (float) ($er->subtotal_freight ?? 0),
                'room_total'              => (float) ($er->room_total ?? 0),
                'is_changed'              => false,
            ]);

            $roomIdMap[$er->id] = $sr->id;
        }

        // Copy items
        $estimateItems = \App\Models\EstimateItem::where('estimate_id', $estimate->id)
            ->orderBy('estimate_room_id')
            ->orderBy('sort_order')
            ->get();

        foreach ($estimateItems as $ei) {
            $saleRoomId = $roomIdMap[$ei->estimate_room_id] ?? null;

			\Log::info('[Convert to sale] estimate item costs before sale item create', [
    'estimate_item_id' => $ei->id,
    'estimate_id' => $ei->estimate_id,
    'quantity' => $ei->quantity,
    'cost_price' => $ei->cost_price,
    'cost_total' => $ei->cost_total,
    'raw_attributes' => $ei->getAttributes(),
]);
			
            \App\Models\SaleItem::create([
                'sale_id'                 => $sale->id,
                'sale_room_id'            => $saleRoomId,
                'source_estimate_item_id' => $ei->id,

                'item_type'               => $ei->item_type,
                'quantity'                => (float) ($ei->quantity ?? 0),
                'order_qty'               => $ei->order_qty !== null ? (float) $ei->order_qty : null,
                'unit'                    => $ei->unit,
                'sell_price'              => (float) ($ei->sell_price ?? 0),
                'line_total'              => (float) ($ei->line_total ?? 0),
				'cost_price'              => (float) ($ei->cost_price ?? 0),
                'notes'                   => $ei->notes,
                'sort_order'              => (int) ($ei->sort_order ?? 0),

                'product_type'            => $ei->product_type,
                'product_line_id'         => $ei->product_line_id,
                'product_style_id'        => $ei->product_style_id,
                'manufacturer'            => $ei->manufacturer,
                'style'                   => $ei->style,
                'color_item_number'       => $ei->color_item_number,
                'po_notes'                => $ei->po_notes,

                'labour_type'             => $ei->labour_type,
                'description'             => $ei->description,
                'freight_description'     => $ei->freight_description,

                'is_changed'              => false,
                'is_removed'              => false,
            ]);
        }

        return $sale;
    });

    // NOTE: adjust this route if your sales edit route name differs
    return redirect()->route('pages.sales.edit', $sale->id)
        ->with('success', 'Sale created from approved estimate.');
}

public function makeRevision(Estimate $estimate)
{
    // Determine the root parent
    $parentId = $estimate->parent_estimate_id ?? $estimate->id;

    // Next revision number = highest existing revision for this parent + 1
    $nextRevNo = Estimate::where('parent_estimate_id', $parentId)->max('revision_no') ?? 0;
    $nextRevNo = max($nextRevNo, $estimate->revision_no) + 1;
    $revLabel  = 'Rev' . str_pad($nextRevNo, 2, '0', STR_PAD_LEFT);

    // Build the revision estimate number from the root parent's number
    $parentNumber    = Estimate::where('id', $parentId)->value('estimate_number');
    $revisionNumber  = $parentNumber ? "{$parentNumber}-{$revLabel}" : null;

    $revision = DB::transaction(function () use ($estimate, $parentId, $nextRevNo, $revLabel, $revisionNumber) {

        // Copy header
        $revision = Estimate::create([
            'parent_estimate_id'        => $parentId,
            'revision_no'               => $nextRevNo,
            'estimate_number'           => $revisionNumber,
            'opportunity_id'            => $estimate->opportunity_id,
            'status'                    => 'draft',
            'customer_name'             => $estimate->customer_name,
            'job_name'                  => $estimate->job_name,
            'job_no'                    => $estimate->job_no,
            'job_address'               => $estimate->job_address,
            'pm_name'                   => $estimate->pm_name,
            'homeowner_name'            => $estimate->homeowner_name,
            'homeowner_phone'           => $estimate->homeowner_phone,
            'homeowner_email'           => $estimate->homeowner_email,
            'salesperson_1_employee_id' => $estimate->salesperson_1_employee_id,
            'salesperson_2_employee_id' => $estimate->salesperson_2_employee_id,
            'notes'                     => $estimate->notes,
            'subtotal_materials'        => $estimate->subtotal_materials,
            'subtotal_labour'           => $estimate->subtotal_labour,
            'subtotal_freight'          => $estimate->subtotal_freight,
            'pretax_total'              => $estimate->pretax_total,
            'tax_group_id'              => $estimate->tax_group_id,
            'tax_rate_percent'          => $estimate->tax_rate_percent,
            'tax_amount'                => $estimate->tax_amount,
            'grand_total'               => $estimate->grand_total,
            'created_by'                => auth()->id(),
            'updated_by'                => auth()->id(),
        ]);

        // Copy rooms and items
        foreach ($estimate->rooms()->with('items')->orderBy('sort_order')->get() as $room) {
            $newRoom = EstimateRoom::create([
                'estimate_id'        => $revision->id,
                'room_name'          => $room->room_name,
                'sort_order'         => $room->sort_order,
                'subtotal_materials' => $room->subtotal_materials,
                'subtotal_labour'    => $room->subtotal_labour,
                'subtotal_freight'   => $room->subtotal_freight,
                'room_total'         => $room->room_total,
            ]);

            foreach ($room->items as $item) {
                EstimateItem::create([
                    'estimate_id'        => $revision->id,
                    'estimate_room_id'   => $newRoom->id,
                    'item_type'          => $item->item_type,
                    'sort_order'         => $item->sort_order,
                    'quantity'           => $item->quantity,
                    'order_qty'          => $item->order_qty,
                    'unit'               => $item->unit,
                    'sell_price'         => $item->sell_price,
                    'line_total'         => $item->line_total,
                    'cost_price'         => $item->cost_price,
                    'cost_total'         => $item->cost_total,
                    'notes'              => $item->notes,
                    'product_type'       => $item->product_type,
                    'product_line_id'    => $item->product_line_id,
                    'product_style_id'   => $item->product_style_id,
                    'manufacturer'       => $item->manufacturer,
                    'style'              => $item->style,
                    'color_item_number'  => $item->color_item_number,
                    'po_notes'           => $item->po_notes,
                    'labour_type'        => $item->labour_type,
                    'description'        => $item->description,
                    'freight_description'=> $item->freight_description,
                ]);
            }
        }

        return $revision;
    });

    return redirect()->route('pages.estimates.edit', $revision->id)
        ->with('success', "Revision {$revLabel} created from estimate #{$estimate->estimate_number}.");
}

	public function showProfits(Estimate $estimate)
{
    $estimate->load([
        'rooms.items.productStyle',
    ]);

    return view('pages.profits.show', [
        'recordType' => 'estimate',
        'record' => $estimate,
        'rooms' => $estimate->rooms,
    ]);
}

    public function previewPdf(Estimate $estimate)
    {
        $estimate->loadMissing(['rooms.items']);
        $pdf = Pdf::loadView('pdf.estimate', compact('estimate'));
        $filename = 'Estimate-' . ($estimate->estimate_number ?? $estimate->id) . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function sendEmail(Request $request, Estimate $estimate)
    {
        $request->validate([
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
            'cc'      => ['nullable', 'array'],
            'cc.*'    => ['nullable', 'email'],
        ]);

        $user   = auth()->user();
        $mailer = app(GraphMailService::class);
        $cc     = array_filter($request->input('cc', []));

        $estimate->loadMissing(['rooms.items']);
        $pdfContent = Pdf::loadView('pdf.estimate', compact('estimate'))->output();
        $attachment = [
            'filename' => 'Estimate-' . ($estimate->estimate_number ?? $estimate->id) . '.pdf',
            'content'  => base64_encode($pdfContent),
        ];

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $request->input('to'), $request->input('subject'), $request->input('body'), 'estimate', $attachment, $cc ?: null)
            : false;

        if (! $sent) {
            $sent = $mailer->send($request->input('to'), $request->input('subject'), $request->input('body'), 'estimate', null, $attachment, $cc ?: null);
        }

        if (! $sent) {
            return back()->with('error', 'Failed to send estimate email. Check the mail log for details.');
        }

        $estimate->update(['status' => 'sent']);

        return back()->with('success', 'Estimate emailed to ' . $request->input('to') . ' and status updated to Sent.');
    }

    private function buildJobAddress(array $data): ?string
    {
        // If a raw job_address was submitted (e.g. from the edit form), use it directly
        if (! empty($data['job_address'])) {
            return $data['job_address'];
        }

        $street   = trim($data['job_street'] ?? '');
        $cityLine = trim(collect([
            $data['job_city']     ?? '',
            $data['job_province'] ?? '',
            $data['job_postal']   ?? '',
        ])->filter()->implode(', '));

        return trim(collect([$street, $cityLine])->filter()->implode("\n")) ?: null;
    }

}