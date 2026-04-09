<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\SampleCheckout;
use App\Models\SampleSet;
use App\Models\SampleSetItem;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SampleSetController extends Controller
{
    // -----------------------------------------------------------------------
    // CREATE
    // -----------------------------------------------------------------------

    public function create()
    {
        $productLines = ProductLine::orderBy('name')->get(['id', 'name', 'manufacturer']);

        return view('pages.sample-sets.create', compact('productLines'));
    }

    // -----------------------------------------------------------------------
    // STORE
    // -----------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_line_id'  => ['required', 'exists:product_lines,id'],
            'name'             => ['nullable', 'string', 'max:255'],
            'location'         => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
            'style_ids'        => ['required', 'array', 'min:1'],
            'style_ids.*'      => ['exists:product_styles,id'],
            'display_prices'   => ['nullable', 'array'],
            'display_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $set = SampleSet::create([
            'product_line_id' => $validated['product_line_id'],
            'name'            => $validated['name'] ?? null,
            'location'        => $validated['location'] ?? null,
            'notes'           => $validated['notes'] ?? null,
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ]);

        foreach ($validated['style_ids'] as $styleId) {
            SampleSetItem::create([
                'sample_set_id'    => $set->id,
                'product_style_id' => $styleId,
                'display_price'    => $validated['display_prices'][$styleId] ?? null,
            ]);
        }

        return redirect()->route('pages.sample-sets.show', $set)
            ->with('success', "Sample Set {$set->set_id} created.");
    }

    // -----------------------------------------------------------------------
    // SHOW
    // -----------------------------------------------------------------------

    public function show(SampleSet $sampleSet)
    {
        $sampleSet->load([
            'productLine',
            'items.productStyle',
            'activeCheckout.customer',
            'activeCheckout.user',
            'activeCheckout.checkedOutBy',
            'checkouts.customer',
            'checkouts.user',
            'checkouts.checkedOutBy',
            'creator',
            'updater',
        ]);

        return view('pages.sample-sets.show', compact('sampleSet'));
    }

    // -----------------------------------------------------------------------
    // EDIT
    // -----------------------------------------------------------------------

    public function edit(SampleSet $sampleSet)
    {
        $sampleSet->load(['productLine', 'items.productStyle']);

        $productLines = ProductLine::orderBy('name')->get(['id', 'name', 'manufacturer']);

        // Styles for the selected product line (for repopulating the picker)
        $styles = ProductStyle::where('product_line_id', $sampleSet->product_line_id)
            ->where('status', '<>', 'archived')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'color', 'sell_price']);

        return view('pages.sample-sets.edit', compact('sampleSet', 'productLines', 'styles'));
    }

    // -----------------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------------

    public function update(Request $request, SampleSet $sampleSet)
    {
        $validated = $request->validate([
            'product_line_id'  => ['required', 'exists:product_lines,id'],
            'name'             => ['nullable', 'string', 'max:255'],
            'location'         => ['nullable', 'string', 'max:255'],
            'status'           => ['required', 'in:active,checked_out,discontinued,retired,lost'],
            'notes'            => ['nullable', 'string'],
            'style_ids'        => ['required', 'array', 'min:1'],
            'style_ids.*'      => ['exists:product_styles,id'],
            'display_prices'   => ['nullable', 'array'],
            'display_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validated['status'] === 'discontinued' && $sampleSet->status !== 'discontinued') {
            $sampleSet->discontinued_at = now();
        }

        $sampleSet->update([
            'product_line_id' => $validated['product_line_id'],
            'name'            => $validated['name'] ?? null,
            'location'        => $validated['location'] ?? null,
            'status'          => $validated['status'],
            'notes'           => $validated['notes'] ?? null,
            'discontinued_at' => $sampleSet->discontinued_at,
            'updated_by'      => auth()->id(),
        ]);

        // Sync items: delete removed, add new
        $existingStyleIds = $sampleSet->items->pluck('product_style_id')->all();
        $newStyleIds       = $validated['style_ids'];

        // Remove items no longer in the list
        $sampleSet->items()
            ->whereNotIn('product_style_id', $newStyleIds)
            ->delete();

        // Add new items
        $toAdd = array_diff($newStyleIds, $existingStyleIds);
        foreach ($toAdd as $styleId) {
            SampleSetItem::create([
                'sample_set_id'    => $sampleSet->id,
                'product_style_id' => $styleId,
                'display_price'    => $validated['display_prices'][$styleId] ?? null,
            ]);
        }

        // Update display_prices for existing items
        foreach ($sampleSet->items()->whereIn('product_style_id', array_intersect($newStyleIds, $existingStyleIds))->get() as $item) {
            $price = $validated['display_prices'][$item->product_style_id] ?? null;
            $item->update(['display_price' => $price]);
        }

        return redirect()->route('pages.sample-sets.show', $sampleSet)
            ->with('success', "Sample Set {$sampleSet->set_id} updated.");
    }

    // -----------------------------------------------------------------------
    // LABEL PDF
    // -----------------------------------------------------------------------

    public function label(Request $request, SampleSet $sampleSet)
    {
        $format = $request->input('format', '5371');

        $sampleSet->load(['productLine', 'items.productStyle']);

        $mobileUrl = route('mobile.samples.show', $sampleSet->set_id);
        $qrSvg     = base64_encode(QrCode::format('svg')->size(150)->generate($mobileUrl));

        $logoPath    = Setting::get('branding_logo_path');
        $logoDataUri = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoData    = Storage::disk('public')->get($logoPath);
            $logoMime    = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }

        $companyName = Setting::get('branding_company_name', 'RM Flooring');

        $paperSize = $format === '5388'
            ? [0, 0, 216, 360]
            : [0, 0, 252, 144];

        $pdf = Pdf::loadView('pdf.sample-set-label', compact(
            'sampleSet', 'format', 'qrSvg', 'logoDataUri', 'companyName'
        ))->setPaper($paperSize);

        return $pdf->stream("label-{$sampleSet->set_id}.pdf");
    }

    // -----------------------------------------------------------------------
    // DESTROY
    // -----------------------------------------------------------------------

    public function destroy(SampleSet $sampleSet)
    {
        if ($sampleSet->activeCheckout()->exists()) {
            return back()->with('error', "Cannot delete {$sampleSet->set_id} — it is currently checked out.");
        }

        $setId = $sampleSet->set_id;
        $sampleSet->delete();

        return redirect()->route('pages.samples.index')
            ->with('success', "Sample Set {$setId} deleted.");
    }

    // -----------------------------------------------------------------------
    // STYLES BY PRODUCT LINE (AJAX)
    // -----------------------------------------------------------------------

    public function stylesByLine(Request $request, ProductLine $productLine)
    {
        $styles = ProductStyle::where('product_line_id', $productLine->id)
            ->where('status', '<>', 'archived')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'color', 'sell_price']);

        return response()->json($styles);
    }

    // -----------------------------------------------------------------------
    // CHECKOUT
    // -----------------------------------------------------------------------

    public function checkout(Request $request, SampleSet $sampleSet)
    {
        if ($sampleSet->status !== 'active') {
            return back()->with('error', 'Only active sets can be checked out.');
        }

        $validated = $request->validate([
            'checkout_type' => ['required', 'in:customer,staff'],
            'customer_id'   => ['nullable', 'required_if:checkout_type,customer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'user_id'       => ['nullable', 'required_if:checkout_type,staff', 'exists:users,id'],
            'due_back_at'   => ['nullable', 'date'],
            'notes'         => ['nullable', 'string'],
        ]);

        SampleCheckout::create([
            'sample_id'     => null,
            'sample_set_id' => $sampleSet->id,
            'checkout_type' => $validated['checkout_type'],
            'customer_id'   => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'] ?? null,
            'user_id'       => $validated['user_id'] ?? null,
            'due_back_at'   => $validated['due_back_at'] ?? null,
            'notes'         => $validated['notes'] ?? null,
        ]);

        $sampleSet->update(['status' => 'checked_out']);

        return back()->with('success', "{$sampleSet->set_id} checked out.");
    }

    // -----------------------------------------------------------------------
    // RETURN
    // -----------------------------------------------------------------------

    public function returnCheckout(Request $request, SampleSet $sampleSet, SampleCheckout $checkout)
    {
        if ($checkout->sample_set_id !== $sampleSet->id) {
            abort(404);
        }

        if ($checkout->returned_at) {
            return back()->with('error', 'This checkout has already been returned.');
        }

        $checkout->update([
            'returned_at'  => now(),
            'return_notes' => $request->input('return_notes'),
        ]);

        // Flip back to active if no more open checkouts
        if ($sampleSet->status === 'checked_out' && $sampleSet->activeCheckout()->doesntExist()) {
            $sampleSet->update(['status' => 'active']);
        }

        return back()->with('success', 'Set marked as returned.');
    }
}
