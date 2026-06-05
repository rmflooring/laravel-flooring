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

        // Generate QR code as base64 SVG — points to public scan page (no auth)
        $mobileUrl = route('scan.sample', $sample->sample_id);
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

        // Paper size in points (72pt/in): 5371 = 3.5"×2", 5388 = 3"×5"
        // ql700 = landscape 90mm×62mm — Brother driver rotates it 90° onto the 62mm tape
        $paperSize = match ($format) {
            '5388'  => [0, 0, 216, 360],
            'ql700' => [0, 0, 255, 176],
            default => [0, 0, 252, 144],
        };

        $showPrice = $request->boolean('show_price', true);

        $pdf = Pdf::loadView('pdf.sample-label', compact(
            'sample', 'format', 'qrSvg', 'logoDataUri', 'companyName', 'showPrice'
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

    // -----------------------------------------------------------------------
    // ADD FROM STYLES FORM
    // -----------------------------------------------------------------------

    public function addFromStylesForm(Request $request)
    {
        $styleIds = array_filter(array_map('intval', (array) $request->input('styles', [])));

        if (empty($styleIds)) {
            return redirect()->route('pages.samples.index')
                ->with('error', 'Select at least one style to add as samples.');
        }

        $styles = ProductStyle::whereIn('id', $styleIds)
            ->with('productLine')
            ->where('status', '<>', 'archived')
            ->orderBy('name')
            ->get();

        if ($styles->isEmpty()) {
            return redirect()->route('pages.samples.index')
                ->with('error', 'No valid styles found for the selection.');
        }

        return view('pages.samples.add-from-styles-form', compact('styles'));
    }

    // -----------------------------------------------------------------------
    // ADD FROM STYLES — CREATE
    // -----------------------------------------------------------------------

    public function addFromStyles(Request $request)
    {
        $request->validate([
            'styles'       => 'required|array|min:1',
            'styles.*'     => 'integer|exists:product_styles,id',
            'qty'          => 'required|array',
            'qty.*'        => 'integer|min:1|max:99',
            'location'     => 'nullable|string|max:255',
            'received_at'  => 'nullable|date',
        ]);

        $location    = $request->input('location');
        $receivedAt  = $request->input('received_at');
        $created     = 0;

        foreach ($request->input('styles') as $styleId) {
            $qty = max(1, min(99, (int) ($request->input('qty')[$styleId] ?? 1)));
            Sample::create([
                'product_style_id' => $styleId,
                'quantity'         => $qty,
                'location'         => $location ?: null,
                'received_at'      => $receivedAt ?: null,
                'status'           => 'active',
            ]);
            $created++;
        }

        return redirect()->route('pages.samples.index')
            ->with('success', $created . ' ' . ($created === 1 ? 'sample' : 'samples') . ' created.');
    }

    // -----------------------------------------------------------------------
    // BATCH LABEL FORM
    // -----------------------------------------------------------------------

    public function batchLabelForm(Request $request)
    {
        $sampleIds = array_filter(array_map('intval', (array) $request->input('samples', [])));
        $setIds    = array_filter(array_map('intval', (array) $request->input('sets', [])));
        $showPrice = $request->boolean('show_price', false);

        if (empty($sampleIds) && empty($setIds)) {
            return redirect()->route('pages.samples.index')
                ->with('error', 'Select at least one sample or set before printing labels.');
        }

        $samples = $sampleIds
            ? Sample::whereIn('id', $sampleIds)->with('productStyle.productLine')->orderBy('sample_id')->get()
            : collect();

        $sets = $setIds
            ? SampleSet::whereIn('id', $setIds)->with('productLine')->orderBy('set_id')->get()
            : collect();

        return view('pages.samples.batch-label-form', compact('samples', 'sets', 'showPrice'));
    }

    // -----------------------------------------------------------------------
    // BATCH LABEL PDF
    // -----------------------------------------------------------------------

    public function batchLabel(Request $request)
    {
        $sampleIds = array_filter(array_map('intval', (array) $request->input('samples', [])));
        $setIds    = array_filter(array_map('intval', (array) $request->input('sets', [])));
        $showPrice  = $request->boolean('show_price', false);
        $qty        = $request->input('qty', []);
        $topOffsetMm  = max(-20, min(20, (float) $request->input('top_offset_mm', 0)));
        $leftOffsetMm = max(-20, min(20, (float) $request->input('left_offset_mm', 0)));

        $samples = $sampleIds
            ? Sample::whereIn('id', $sampleIds)->with('productStyle.productLine')->get()->keyBy('id')
            : collect();

        $sets = $setIds
            ? SampleSet::whereIn('id', $setIds)->with('productLine')->get()->keyBy('id')
            : collect();

        // Branding
        $logoPath    = Setting::get('branding_logo_path');
        $logoDataUri = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoData    = Storage::disk('public')->get($logoPath);
            $logoMime    = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }
        $companyName = Setting::get('branding_company_name', 'RM Flooring');

        // Build flat label list, generating QR once per record
        $labels = [];

        foreach ($sampleIds as $id) {
            if (! $samples->has($id)) {
                continue;
            }
            $sample = $samples[$id];
            $copies = max(1, min(20, (int) ($qty["s_{$id}"] ?? 1)));
            $qrSvg  = base64_encode(QrCode::format('svg')->size(100)->generate(route('scan.sample', $sample->sample_id)));
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = ['type' => 'sample', 'model' => $sample, 'qrSvg' => $qrSvg];
            }
        }

        foreach ($setIds as $id) {
            if (! $sets->has($id)) {
                continue;
            }
            $set    = $sets[$id];
            $copies = max(1, min(20, (int) ($qty["set_{$id}"] ?? 1)));
            $qrSvg  = base64_encode(QrCode::format('svg')->size(100)->generate(route('scan.sample', $set->set_id)));
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = ['type' => 'set', 'model' => $set, 'qrSvg' => $qrSvg];
            }
        }

        if (empty($labels)) {
            return redirect()->route('pages.samples.index')
                ->with('error', 'No valid items selected for printing.');
        }

        $rows = array_chunk($labels, 2);

        return Pdf::loadView('pdf.batch-labels-5163', compact('rows', 'logoDataUri', 'companyName', 'showPrice', 'topOffsetMm', 'leftOffsetMm'))
            ->setPaper('letter')
            ->stream('batch-labels.pdf');
    }
}
