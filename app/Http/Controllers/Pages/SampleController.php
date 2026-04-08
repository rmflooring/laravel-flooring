<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\Sample;
use App\Models\SampleCheckout;
use App\Models\SampleSet;
use App\Models\Setting;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SampleController extends Controller
{
    // -----------------------------------------------------------------------
    // INDEX
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $type     = $request->input('type', 'all'); // all | individual | set
        $search   = $request->input('search', '');
        $status   = $request->input('status', '');
        $location = $request->input('location', '');
        $overdue  = $request->boolean('overdue');

        // ── Individual samples ────────────────────────────────────────────────
        $samples = collect();
        if ($type !== 'set') {
            $q = Sample::with(['productStyle.productLine', 'activeCheckouts'])
                ->withCount('activeCheckouts');

            if ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('sample_id', 'like', "%{$search}%")
                       ->orWhere('location', 'like', "%{$search}%")
                       ->orWhereHas('productStyle', fn ($s) =>
                           $s->where('name', 'like', "%{$search}%")
                             ->orWhere('sku', 'like', "%{$search}%")
                             ->orWhere('color', 'like', "%{$search}%"))
                       ->orWhereHas('productStyle.productLine', fn ($pl) =>
                           $pl->where('name', 'like', "%{$search}%")
                              ->orWhere('manufacturer', 'like', "%{$search}%"));
                });
            }
            if ($status) {
                $q->where('status', $status);
            }
            if ($location) {
                $q->where('location', 'like', "%{$location}%");
            }
            if ($overdue) {
                $q->whereHas('activeCheckouts', fn ($q) =>
                    $q->whereNotNull('due_back_at')->where('due_back_at', '<', now()->toDateString()));
            }

            $samples = $type === 'individual'
                ? $q->orderBy('sample_id')->paginate(30)->withQueryString()
                : $q->orderBy('sample_id')->get();
        }

        // ── Sample sets ───────────────────────────────────────────────────────
        $sampleSets = collect();
        if ($type !== 'individual') {
            $sq = SampleSet::with(['productLine', 'activeCheckout', 'items'])
                ->withCount('items');

            if ($search) {
                $sq->where(function ($q) use ($search) {
                    $q->where('set_id', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhereHas('productLine', fn ($pl) =>
                          $pl->where('name', 'like', "%{$search}%")
                             ->orWhere('manufacturer', 'like', "%{$search}%"));
                });
            }
            if ($status) {
                $sq->where('status', $status);
            }
            if ($location) {
                $sq->where('location', 'like', "%{$location}%");
            }
            if ($overdue) {
                $sq->overdue();
            }

            $sampleSets = $type === 'set'
                ? $sq->orderBy('set_id')->paginate(30)->withQueryString()
                : $sq->orderBy('set_id')->get();
        }

        $locations = Sample::whereNotNull('location')->distinct()->orderBy('location')->pluck('location')
            ->merge(SampleSet::whereNotNull('location')->distinct()->orderBy('location')->pluck('location'))
            ->unique()->sort()->values();

        $filters = $request->only('search', 'status', 'overdue', 'location', 'type');

        return view('pages.samples.index', [
            'samples'    => $samples,
            'sampleSets' => $sampleSets,
            'type'       => $type,
            'statuses'   => Sample::STATUSES,
            'locations'  => $locations,
            'filters'    => $filters,
        ]);
    }

    // -----------------------------------------------------------------------
    // CREATE
    // -----------------------------------------------------------------------

    public function create()
    {
        return view('pages.samples.create');
    }

    // -----------------------------------------------------------------------
    // STORE
    // -----------------------------------------------------------------------

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_style_id' => ['required', 'exists:product_styles,id'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'location'         => ['nullable', 'string', 'max:255'],
            'display_price'    => ['nullable', 'numeric', 'min:0'],
            'notes'            => ['nullable', 'string'],
            'received_at'      => ['nullable', 'date'],
        ]);

        $sample = Sample::create($validated);

        return redirect()->route('pages.samples.show', $sample)
            ->with('success', "Sample {$sample->sample_id} created.");
    }

    // -----------------------------------------------------------------------
    // SHOW
    // -----------------------------------------------------------------------

    public function show(Sample $sample)
    {
        $sample->load([
            'productStyle.productLine',
            'productStyle.photos',
            'activeCheckouts.customer',
            'activeCheckouts.user',
            'activeCheckouts.checkedOutBy',
            'checkouts.customer',
            'checkouts.user',
            'checkouts.checkedOutBy',
            'creator',
            'updater',
        ]);

        return view('pages.samples.show', compact('sample'));
    }

    // -----------------------------------------------------------------------
    // EDIT
    // -----------------------------------------------------------------------

    public function edit(Sample $sample)
    {
        $sample->load(['productStyle.productLine', 'productStyle.photos']);

        return view('pages.samples.edit', compact('sample'));
    }

    // -----------------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------------

    public function update(Request $request, Sample $sample)
    {
        $validated = $request->validate([
            'quantity'      => ['required', 'integer', 'min:1'],
            'location'      => ['nullable', 'string', 'max:255'],
            'display_price' => ['nullable', 'numeric', 'min:0'],
            'status'        => ['required', 'in:active,checked_out,discontinued,retired,lost'],
            'notes'         => ['nullable', 'string'],
            'received_at'   => ['nullable', 'date'],
        ]);

        if ($validated['status'] === 'discontinued' && $sample->status !== 'discontinued') {
            $validated['discontinued_at'] = now();
        }

        $sample->update($validated);

        return redirect()->route('pages.samples.show', $sample)
            ->with('success', "Sample {$sample->sample_id} updated.");
    }

    // -----------------------------------------------------------------------
    // DESTROY
    // -----------------------------------------------------------------------

    public function destroy(Sample $sample)
    {
        if ($sample->activeCheckouts()->exists()) {
            return back()->with('error', "Cannot delete {$sample->sample_id} — it has active checkouts.");
        }

        $sampleId = $sample->sample_id;
        $sample->delete();

        return redirect()->route('pages.samples.index')
            ->with('success', "Sample {$sampleId} deleted.");
    }

    // -----------------------------------------------------------------------
    // LABEL PDF
    // -----------------------------------------------------------------------

    public function label(Request $request, Sample $sample)
    {
        $format = $request->input('format', '5371');

        $sample->load(['productStyle.productLine', 'productStyle.photos']);

        // Generate QR code as base64 SVG
        $mobileUrl = route('mobile.samples.show', $sample->sample_id);
        $qrSvg     = base64_encode(QrCode::format('svg')->size(150)->generate($mobileUrl));

        // Branding logo
        $logoPath    = Setting::get('branding_logo_path');
        $logoDataUri = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoData    = Storage::disk('public')->get($logoPath);
            $logoMime    = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }

        $companyName = Setting::get('branding_company_name', 'RM Flooring');

        // Paper size: 5371 = 3.5"×2", 5388 = 3"×5"
        $paperSize = $format === '5388'
            ? [0, 0, 216, 360]   // 3" × 5" in points (72pt/in)
            : [0, 0, 252, 144];  // 3.5" × 2" in points

        $pdf = Pdf::loadView('pdf.sample-label', compact(
            'sample', 'format', 'qrSvg', 'logoDataUri', 'companyName'
        ))->setPaper($paperSize);

        return $pdf->stream("label-{$sample->sample_id}.pdf");
    }

    // -----------------------------------------------------------------------
    // RETURN A CHECKOUT
    // -----------------------------------------------------------------------

    public function returnCheckout(Request $request, Sample $sample, SampleCheckout $checkout)
    {
        if ($checkout->sample_id !== $sample->id) {
            abort(404);
        }

        if ($checkout->returned_at) {
            return back()->with('error', 'This checkout has already been returned.');
        }

        $checkout->update([
            'returned_at'  => now(),
            'return_notes' => $request->input('return_notes'),
        ]);

        // If no more active checkouts and sample was checked_out, flip back to active
        if ($sample->status === 'checked_out' && $sample->activeCheckouts()->doesntExist()) {
            $sample->update(['status' => 'active']);
        }

        return back()->with('success', 'Sample marked as returned.');
    }

    // -----------------------------------------------------------------------
    // PRODUCT STYLE SEARCH (AJAX — for create/edit typeahead)
    // -----------------------------------------------------------------------

    public function searchStyles(Request $request)
    {
        $search = $request->input('q', '');

        $styles = ProductStyle::with('productLine')
            ->where('status', '<>', 'archived')
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('style_number', 'like', "%{$search}%")
                  ->orWhereHas('productLine', function ($pl) use ($search) {
                      $pl->where('name', 'like', "%{$search}%")
                         ->orWhere('manufacturer', 'like', "%{$search}%");
                  });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'product_line_id', 'name', 'sku', 'color', 'sell_price']);

        return response()->json($styles->map(fn ($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'sku'         => $s->sku,
            'color'       => $s->color,
            'sell_price'  => $s->sell_price,
            'line_name'   => $s->productLine?->name,
            'manufacturer'=> $s->productLine?->manufacturer,
        ]));
    }
}
