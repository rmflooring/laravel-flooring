<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceRoom;
use App\Models\PaymentTerm;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\EmailTemplateService;
use App\Services\GraphMailService;
use App\Services\InvoiceService;
use App\Services\QboSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    // -------------------------------------------------------------------------
    // Create / Store
    // -------------------------------------------------------------------------

    public function create(Sale $sale)
    {
        if (! in_array($sale->status, ['approved', 'scheduled', 'in_progress', 'completed', 'partially_invoiced', 'invoiced', 'change_in_progress'])) {
            return redirect()->route('pages.sales.show', $sale)
                ->with('error', 'Invoices can only be created on approved or active sales.');
        }

        $sale->load(['rooms' => fn ($q) => $q->orderBy('sort_order'), 'rooms.items' => fn ($q) => $q->orderBy('sort_order')]);

        // How much of each sale item has already been invoiced (non-voided)
        $invoicedQty = $this->service->getInvoicedQtyBySaleItem($sale);

        $paymentTerms = PaymentTerm::where('is_active', true)->orderBy('name')->get();

        $taxRates = $sale->tax_group_id
            ? \DB::table('tax_rate_group_items')
                ->join('tax_rates', 'tax_rates.id', '=', 'tax_rate_group_items.tax_rate_id')
                ->where('tax_rate_group_id', $sale->tax_group_id)
                ->get(['tax_rates.name', 'tax_rates.sales_rate'])
            : collect();

        return view('pages.invoices.create', compact('sale', 'invoicedQty', 'paymentTerms', 'taxRates'));
    }

    public function store(Request $request, Sale $sale)
    {

        $data = $request->validate([
            'payment_term_id'    => ['nullable', 'exists:payment_terms,id'],
            'due_date'           => ['nullable', 'date'],
            'customer_po_number' => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*'            => ['numeric', 'min:0'],
        ]);

        // Filter to only items with qty > 0
        $selectedItems = array_filter($data['items'], fn ($qty) => $qty > 0);

        if (empty($selectedItems)) {
            return back()->withErrors(['items' => 'Please select at least one item to invoice.'])->withInput();
        }

        // Validate quantities don't exceed remaining available
        $invoicedQty = $this->service->getInvoicedQtyBySaleItem($sale);
        $sale->load(['rooms.items']);

        foreach ($selectedItems as $saleItemId => $qty) {
            $saleItem = $sale->rooms->flatMap->items->firstWhere('id', $saleItemId);
            if (! $saleItem) continue;

            $alreadyInvoiced = $invoicedQty[$saleItemId] ?? 0;
            $remaining       = (float) $saleItem->quantity - $alreadyInvoiced;

            if ((float) $qty > $remaining + 0.001) {
                return back()->withErrors([
                    "items.{$saleItemId}" => "Quantity exceeds remaining uninvoiced amount ({$remaining} available)."
                ])->withInput();
            }
        }

        $invoice = $this->service->createFromSale($sale, $data, $selectedItems);

        return redirect()->route('pages.sales.invoices.show', [$sale, $invoice])
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function show(Sale $sale, Invoice $invoice)
    {
        $invoice->load(['rooms.items', 'payments.recordedBy', 'payments.salePayment', 'paymentTerm']);
        $sale->load(['opportunity.projectManager', 'opportunity.parentCustomer', 'opportunity.jobSiteCustomer']);

        $paymentMethods   = InvoicePayment::PAYMENT_METHODS;
        $pmEmail          = $sale->opportunity?->projectManager?->email;
        $homeownerEmail   = $sale->job_email ?: ($sale->sourceEstimate?->homeowner_email ?? '');
        $customerContacts = $sale->opportunity?->parentCustomer?->contacts ?? collect();

        [$emailSubject, $emailBody] = $this->resolveEmailTemplate($sale, $invoice);

        $taxRates = $sale->tax_group_id
            ? \DB::table('tax_rate_group_items')
                ->join('tax_rates', 'tax_rates.id', '=', 'tax_rate_group_items.tax_rate_id')
                ->where('tax_rate_group_id', $sale->tax_group_id)
                ->get(['tax_rates.name', 'tax_rates.sales_rate'])
            : collect();

        $openedAt = \App\Models\MailLog::where('related_id', $invoice->id)
            ->where('related_type', 'invoice')
            ->whereNotNull('opened_at')
            ->orderByDesc('opened_at')
            ->value('opened_at');

        return view('pages.invoices.show', compact(
            'sale', 'invoice', 'paymentMethods', 'taxRates',
            'pmEmail', 'homeownerEmail', 'customerContacts', 'emailSubject', 'emailBody',
            'openedAt'
        ));
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(Sale $sale, Invoice $invoice)
    {
        if (! in_array($invoice->status, ['draft', 'sent'])) {
            return redirect()->route('pages.sales.invoices.show', [$sale, $invoice])
                ->with('error', 'Only draft or sent invoices can be edited.');
        }

        $invoice->load(['rooms' => fn ($q) => $q->orderBy('sort_order'), 'rooms.items' => fn ($q) => $q->orderBy('sort_order'), 'paymentTerm']);
        $paymentTerms = PaymentTerm::where('is_active', true)->orderBy('name')->get();

        return view('pages.invoices.edit', compact('sale', 'invoice', 'paymentTerms'));
    }

    public function update(Request $request, Sale $sale, Invoice $invoice)
    {
        if (! in_array($invoice->status, ['draft', 'sent'])) {
            return back()->with('error', 'Only draft or sent invoices can be edited.');
        }

        $request->validate([
            'status'             => ['required', 'in:draft,sent'],
            'payment_term_id'    => ['nullable', 'exists:payment_terms,id'],
            'due_date'           => ['nullable', 'date'],
            'customer_po_number' => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'rooms'              => ['nullable', 'array'],
        ]);

        $invoice->update([
            'status'             => $request->status,
            'payment_term_id'    => $request->payment_term_id,
            'due_date'           => $request->due_date,
            'customer_po_number' => $request->customer_po_number,
            'notes'              => $request->notes,
        ]);

        $taxRate          = (float) ($sale->tax_rate_percent ?? 0) / 100;
        $submittedRoomIds = [];
        $subtotal         = 0;

        foreach ($request->input('rooms', []) as $ri => $roomData) {
            // Handle soft-deleted rooms (flag set by JS when existing room is removed)
            if (($roomData['_delete'] ?? '0') === '1') {
                if ($roomId = $roomData['id'] ?? null) {
                    InvoiceRoom::where('id', $roomId)->where('invoice_id', $invoice->id)->delete();
                }
                continue;
            }

            $roomId   = $roomData['id'] ?? null;
            $roomName = $roomData['room_name'] ?? $roomData['name'] ?? '';

            if ($roomId && $room = InvoiceRoom::where('id', $roomId)->where('invoice_id', $invoice->id)->first()) {
                $room->update(['name' => $roomName, 'sort_order' => $ri]);
            } else {
                $room = InvoiceRoom::create([
                    'invoice_id' => $invoice->id,
                    'name'       => $roomName,
                    'sort_order' => $ri,
                ]);
            }

            $submittedRoomIds[] = $room->id;
            $submittedItemIds   = [];
            $sortOrder          = 0;

            // ── Materials ────────────────────────────────────────────────────
            foreach ($roomData['materials'] ?? [] as $mat) {
                $parts = array_filter([
                    trim($mat['product_type'] ?? ''),
                    trim($mat['manufacturer'] ?? ''),
                    trim($mat['style'] ?? ''),
                    trim($mat['color_item_number'] ?? ''),
                ]);
                $label     = implode(' — ', $parts) ?: 'Material';
                $qty       = (float) ($mat['quantity'] ?? 0);
                $sell      = (float) ($mat['sell_price'] ?? 0);
                $lineTotal = round($qty * $sell, 2);

                $payload = [
                    'invoice_id'      => $invoice->id,
                    'invoice_room_id' => $room->id,
                    'item_type'       => 'material',
                    'label'           => $label,
                    'quantity'        => $qty,
                    'unit'            => $mat['unit'] ?? null,
                    'sell_price'      => $sell,
                    'line_total'      => $lineTotal,
                    'tax_rate'        => $sale->tax_rate_percent ?? 0,
                    'tax_amount'      => round($lineTotal * $taxRate, 2),
                    'tax_group_id'    => $sale->tax_group_id,
                    'sort_order'      => $sortOrder++,
                ];

                $itemId = $mat['id'] ?? null;
                if ($itemId && $item = InvoiceItem::where('id', $itemId)->where('invoice_room_id', $room->id)->first()) {
                    $item->update($payload);
                } else {
                    $item = InvoiceItem::create($payload);
                }

                $submittedItemIds[] = $item->id;
                $subtotal += $lineTotal;
            }

            // ── Freight ──────────────────────────────────────────────────────
            foreach ($roomData['freight'] ?? [] as $fr) {
                $label     = trim($fr['freight_description'] ?? '') ?: 'Freight';
                $qty       = (float) ($fr['quantity'] ?? 0);
                $sell      = (float) ($fr['sell_price'] ?? 0);
                $lineTotal = round($qty * $sell, 2);

                $payload = [
                    'invoice_id'      => $invoice->id,
                    'invoice_room_id' => $room->id,
                    'item_type'       => 'freight',
                    'label'           => $label,
                    'quantity'        => $qty,
                    'unit'            => null,
                    'sell_price'      => $sell,
                    'line_total'      => $lineTotal,
                    'tax_rate'        => $sale->tax_rate_percent ?? 0,
                    'tax_amount'      => round($lineTotal * $taxRate, 2),
                    'tax_group_id'    => $sale->tax_group_id,
                    'sort_order'      => $sortOrder++,
                ];

                $itemId = $fr['id'] ?? null;
                if ($itemId && $item = InvoiceItem::where('id', $itemId)->where('invoice_room_id', $room->id)->first()) {
                    $item->update($payload);
                } else {
                    $item = InvoiceItem::create($payload);
                }

                $submittedItemIds[] = $item->id;
                $subtotal += $lineTotal;
            }

            // ── Labour ───────────────────────────────────────────────────────
            foreach ($roomData['labour'] ?? [] as $lab) {
                $labType = trim($lab['labour_type'] ?? '');
                $desc    = trim($lab['description'] ?? '');
                $label   = ($labType && $desc) ? "{$labType} — {$desc}" : ($labType ?: ($desc ?: 'Labour'));
                $qty       = (float) ($lab['quantity'] ?? 0);
                $sell      = (float) ($lab['sell_price'] ?? 0);
                $lineTotal = round($qty * $sell, 2);

                $payload = [
                    'invoice_id'      => $invoice->id,
                    'invoice_room_id' => $room->id,
                    'item_type'       => 'labour',
                    'label'           => $label,
                    'quantity'        => $qty,
                    'unit'            => $lab['unit'] ?? null,
                    'sell_price'      => $sell,
                    'line_total'      => $lineTotal,
                    'tax_rate'        => $sale->tax_rate_percent ?? 0,
                    'tax_amount'      => round($lineTotal * $taxRate, 2),
                    'tax_group_id'    => $sale->tax_group_id,
                    'sort_order'      => $sortOrder++,
                ];

                $itemId = $lab['id'] ?? null;
                if ($itemId && $item = InvoiceItem::where('id', $itemId)->where('invoice_room_id', $room->id)->first()) {
                    $item->update($payload);
                } else {
                    $item = InvoiceItem::create($payload);
                }

                $submittedItemIds[] = $item->id;
                $subtotal += $lineTotal;
            }

            $room->items()->whereNotIn('id', $submittedItemIds)->delete();
        }

        $invoice->rooms()->whereNotIn('id', $submittedRoomIds)->delete();

        $taxTotal   = round($subtotal * $taxRate, 2);
        $grandTotal = round($subtotal + $taxTotal, 2);

        $invoice->update([
            'subtotal'    => round($subtotal, 2),
            'tax_amount'  => $taxTotal,
            'grand_total' => $grandTotal,
        ]);

        $this->service->recalculateAfterPayment($invoice);
        $this->service->syncSaleInvoiceStatus($sale);

        return redirect()->route('pages.sales.invoices.show', [$sale, $invoice])
            ->with('success', 'Invoice updated.');
    }

    // -------------------------------------------------------------------------
    // Void
    // -------------------------------------------------------------------------

    public function void(Request $request, Sale $sale, Invoice $invoice)
    {

        $request->validate([
            'void_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice->update([
            'status'      => 'voided',
            'voided_at'   => now(),
            'void_reason' => $request->input('void_reason'),
        ]);

        $this->service->syncSaleInvoiceStatus($sale);

        return redirect()->route('pages.sales.invoices.show', [$sale, $invoice])
            ->with('success', 'Invoice voided.');
    }

    // -------------------------------------------------------------------------
    // Payments
    // -------------------------------------------------------------------------

    public function storePayment(Request $request, Sale $sale, Invoice $invoice)
    {

        if ($invoice->status === 'voided') {
            return back()->with('error', 'Cannot add a payment to a voided invoice.');
        }

        $data = $request->validate([
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'payment_date'     => ['required', 'date'],
            'payment_method'   => ['required', 'in:' . implode(',', array_keys(InvoicePayment::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $data['invoice_id']   = $invoice->id;
        $data['recorded_by']  = Auth::id();

        $payment = InvoicePayment::create($data);

        $this->service->recalculateAfterPayment($invoice);

        if ($invoice->qbo_id && app(\App\Services\QuickBooksService::class)->isConnected()) {
            app(\App\Services\QboSyncService::class)->pushPayment($payment);
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function pushPaymentToQbo(Sale $sale, Invoice $invoice, InvoicePayment $payment)
    {
        $result = app(\App\Services\QboSyncService::class)->pushPayment($payment);
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destroyPayment(Sale $sale, Invoice $invoice, InvoicePayment $payment)
    {

        $payment->delete();

        $this->service->recalculateAfterPayment($invoice);

        return back()->with('success', 'Payment removed.');
    }

    // -------------------------------------------------------------------------
    // Send Email
    // -------------------------------------------------------------------------

    public function sendEmail(Request $request, Sale $sale, Invoice $invoice)
    {

        $request->validate([
            'to'            => ['required', 'email'],
            'subject'       => ['required', 'string', 'max:255'],
            'body'          => ['required', 'string'],
            'cc'            => ['nullable', 'array'],
            'cc.*'          => ['nullable', 'email'],
            'attachments'   => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['nullable', 'file', 'max:3072'],
        ]);

        $cc                 = array_filter($request->input('cc', []));
        $requestReadReceipt = $request->boolean('request_read_receipt', true);
        $trackingToken      = $requestReadReceipt ? (string) \Illuminate\Support\Str::uuid() : null;
        $attachment         = $this->buildPdfAttachment($sale, $invoice);

        $extraAttachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $extraAttachments[] = [
                'filename' => $file->getClientOriginalName(),
                'content'  => base64_encode(file_get_contents($file->getRealPath())),
                'mime'     => $file->getMimeType() ?? 'application/octet-stream',
            ];
        }

        $mailer = app(GraphMailService::class);
        $user   = Auth::user();

        $pdfUrl = route('pages.mail-attachments.pdf', ['type' => 'invoice', 'id' => $invoice->id]);

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $request->input('to'), $request->input('subject'), $request->input('body'), 'invoice', $attachment, $cc ?: null, null, $invoice->id, 'invoice', $pdfUrl, $requestReadReceipt, $trackingToken, $extraAttachments)
            : false;

        if (! $sent) {
            $mailer->send($request->input('to'), $request->input('subject'), $request->input('body'), 'invoice', null, $attachment, $cc ?: null, null, $invoice->id, 'invoice', $pdfUrl, $requestReadReceipt, $trackingToken, $extraAttachments);
        }

        $invoice->sent_at = now();
        if ($invoice->status === 'draft') {
            $invoice->status = 'sent';
            $invoice->save();
            $this->service->syncSaleInvoiceStatus($sale);
        } else {
            $invoice->save();
        }

        return back()->with('success', 'Invoice emailed successfully.');
    }

    // -------------------------------------------------------------------------
    // PDF
    // -------------------------------------------------------------------------

    public function pdf(Sale $sale, Invoice $invoice)
    {

        $invoice->load(['rooms.items', 'paymentTerm', 'payments']);
        $sale->load(['opportunity.projectManager', 'opportunity.customer', 'opportunity.parentCustomer', 'opportunity.jobSiteCustomer']);

        $taxRates = $sale->tax_group_id
            ? \DB::table('tax_rate_group_items')
                ->join('tax_rates', 'tax_rates.id', '=', 'tax_rate_group_items.tax_rate_id')
                ->where('tax_rate_group_id', $sale->tax_group_id)
                ->get(['tax_rates.name', 'tax_rates.sales_rate'])
            : collect();

        $branding = $this->getBranding();

        $pdf = Pdf::loadView('pdf.invoice', compact('sale', 'invoice', 'branding', 'taxRates'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }

    public function pushToQbo(Sale $sale, Invoice $invoice, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        $itemIds = [
            'material' => Setting::get('qbo_income_material_item_id'),
            'freight'  => Setting::get('qbo_income_freight_item_id'),
            'labour'   => Setting::get('qbo_income_labour_item_id'),
        ];

        $missing = array_keys(array_filter($itemIds, fn($v) => ! $v));
        if ($missing) {
            return back()->with('error', 'Missing QBO income item(s): ' . implode(', ', $missing) . '. Visit Settings → QuickBooks Online to set them up.');
        }

        $result = $sync->pushInvoice($invoice, $itemIds);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveEmailTemplate(Sale $sale, Invoice $invoice): array
    {
        $user            = auth()->user();
        $templateService = app(EmailTemplateService::class);
        $template        = $templateService->getTemplate($user, 'invoice');

        $vars = [
            'customer_name'    => $sale->sourceEstimate?->homeowner_name ?: $sale->customer_name,
            'invoice_number'   => $invoice->invoice_number,
            'sale_number'      => $sale->sale_number,
            'grand_total'      => '$' . number_format((float) $invoice->grand_total, 2),
            'balance_due'      => '$' . number_format((float) max(0, $invoice->balance_due), 2),
            'due_date'         => $invoice->due_date?->format('F j, Y') ?? '',
            'job_name'         => $sale->job_name,
            'job_no'           => $sale->job_no,
            'job_address'      => $sale->job_address,
            'job_phone'        => $sale->job_phone,
            'pm_name'          => $sale->pm_name,
            'pm_first_name'    => explode(' ', trim($sale->pm_name ?? ''))[0],
            'sender_name'      => $user->name,
            'sender_email'     => $user->email,
        ];

        return [
            $templateService->render($template['subject'], $vars),
            $templateService->render($template['body'], $vars),
        ];
    }

    private function buildPdfAttachment(Sale $sale, Invoice $invoice): ?array
    {
        try {
            $invoice->load(['rooms.items', 'paymentTerm', 'payments']);
            $sale->load(['opportunity.projectManager', 'opportunity.customer']);
            $taxRates = $sale->tax_group_id
                ? \DB::table('tax_rate_group_items')
                    ->join('tax_rates', 'tax_rates.id', '=', 'tax_rate_group_items.tax_rate_id')
                    ->where('tax_rate_group_id', $sale->tax_group_id)
                    ->get(['tax_rates.name', 'tax_rates.sales_rate'])
                : collect();
            $branding = $this->getBranding();

            $pdf        = Pdf::loadView('pdf.invoice', compact('sale', 'invoice', 'branding', 'taxRates'))->setPaper('letter', 'portrait');
            $pdfContent = $pdf->output();

            Storage::disk('local')->put("mail-attachments/invoice/{$invoice->id}.pdf", $pdfContent);

            return [
                'filename' => "invoice-{$invoice->invoice_number}.pdf",
                'content'  => base64_encode($pdfContent),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getBranding(): array
    {
        $keys = [
            'branding_company_name', 'branding_tagline', 'branding_street',
            'branding_city', 'branding_province', 'branding_postal',
            'branding_phone', 'branding_email', 'branding_website', 'branding_logo_path',
        ];

        $rows = \DB::table('app_settings')->whereIn('key', $keys)->pluck('value', 'key');

        $branding = [];
        foreach ($keys as $key) {
            $branding[str_replace('branding_', '', $key)] = $rows[$key] ?? null;
        }

        if ($branding['logo_path'] && file_exists(storage_path('app/public/' . $branding['logo_path']))) {
            $mime  = mime_content_type(storage_path('app/public/' . $branding['logo_path']));
            $data  = base64_encode(file_get_contents(storage_path('app/public/' . $branding['logo_path'])));
            $branding['logo_data_uri'] = "data:{$mime};base64,{$data}";
        } else {
            $branding['logo_data_uri'] = null;
        }

        return $branding;
    }
}
