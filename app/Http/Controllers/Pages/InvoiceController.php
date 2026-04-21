<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PaymentTerm;
use App\Models\Sale;
use App\Models\Setting;
use App\Services\GraphMailService;
use App\Services\InvoiceService;
use App\Services\QboSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $paymentMethods = InvoicePayment::PAYMENT_METHODS;

        return view('pages.invoices.show', compact('sale', 'invoice', 'paymentMethods'));
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(Sale $sale, Invoice $invoice)
    {

        if ($invoice->status === 'voided') {
            return redirect()->route('pages.sales.invoices.show', [$sale, $invoice])
                ->with('error', 'Voided invoices cannot be edited.');
        }

        $invoice->load(['rooms.items', 'paymentTerm']);
        $paymentTerms = PaymentTerm::where('is_active', true)->orderBy('name')->get();

        return view('pages.invoices.edit', compact('sale', 'invoice', 'paymentTerms'));
    }

    public function update(Request $request, Sale $sale, Invoice $invoice)
    {

        if ($invoice->status === 'voided') {
            return back()->with('error', 'Voided invoices cannot be edited.');
        }

        $data = $request->validate([
            'payment_term_id'    => ['nullable', 'exists:payment_terms,id'],
            'due_date'           => ['nullable', 'date'],
            'customer_po_number' => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'status'             => ['nullable', 'in:draft,sent,paid,overdue,partially_paid,voided'],
        ]);

        if (($data['status'] ?? null) === 'voided' && $invoice->status !== 'voided') {
            $data['voided_at']   = now();
            $data['void_reason'] = $request->input('void_reason');
        }

        $invoice->update($data);

        // If voided, sync sale status
        if (($data['status'] ?? null) === 'voided') {
            $this->service->syncSaleInvoiceStatus($sale);
        }

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

        InvoicePayment::create($data);

        $this->service->recalculateAfterPayment($invoice);

        return back()->with('success', 'Payment recorded.');
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
            'to'      => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
            'cc'      => ['nullable', 'array'],
            'cc.*'    => ['nullable', 'email'],
        ]);

        $cc         = array_filter($request->input('cc', []));
        $attachment = $this->buildPdfAttachment($sale, $invoice);

        $mailer = app(GraphMailService::class);
        $user   = Auth::user();

        $pdfUrl = route('pages.sales.invoices.pdf', [$sale, $invoice]);

        $sent = $user->microsoftAccount?->mail_connected
            ? $mailer->sendAsUser($user, $request->input('to'), $request->input('subject'), $request->input('body'), 'invoice', $attachment, $cc ?: null, null, $invoice->id, 'invoice', $pdfUrl)
            : false;

        if (! $sent) {
            $mailer->send($request->input('to'), $request->input('subject'), $request->input('body'), 'invoice', null, $attachment, $cc ?: null, null, $invoice->id, 'invoice', $pdfUrl);
        }

        // Mark as sent if still draft
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent', 'sent_at' => now()]);
            $this->service->syncSaleInvoiceStatus($sale);
        }

        return back()->with('success', 'Invoice emailed successfully.');
    }

    // -------------------------------------------------------------------------
    // PDF
    // -------------------------------------------------------------------------

    public function pdf(Sale $sale, Invoice $invoice)
    {

        $invoice->load(['rooms.items', 'paymentTerm', 'payments']);
        $sale->load(['opportunity.projectManager', 'opportunity.customer']);

        $branding = $this->getBranding();

        $pdf = Pdf::loadView('pdf.invoice', compact('sale', 'invoice', 'branding'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }

    public function pushToQbo(Sale $sale, Invoice $invoice, QboSyncService $sync)
    {
        if (! app(\App\Services\QuickBooksService::class)->isConnected()) {
            return back()->with('error', 'QuickBooks is not connected. Visit Settings → QuickBooks Online.');
        }

        $incomeItemId = Setting::get('qbo_income_item_id');
        if (! $incomeItemId) {
            return back()->with('error', 'No QBO income item configured. Visit Settings → QuickBooks Online to set it up.');
        }

        $result = $sync->pushInvoice($invoice, $incomeItemId);

        return back()->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildPdfAttachment(Sale $sale, Invoice $invoice): ?array
    {
        try {
            $invoice->load(['rooms.items', 'paymentTerm', 'payments']);
            $sale->load(['opportunity.projectManager', 'opportunity.customer']);
            $branding = $this->getBranding();

            $pdf  = Pdf::loadView('pdf.invoice', compact('sale', 'invoice', 'branding'))->setPaper('letter', 'portrait');
            $data = base64_encode($pdf->output());

            return [
                '@odata.type'  => '#microsoft.graph.fileAttachment',
                'name'         => "invoice-{$invoice->invoice_number}.pdf",
                'contentType'  => 'application/pdf',
                'contentBytes' => $data,
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
