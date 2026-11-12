<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\ProductType;
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

    /**
     * Return a manual (typed-in) checkout line — it has no sample or set to
     * route through, so it gets its own endpoint keyed on the checkout row.
     */
    public function returnManualCheckout(Request $request, SampleCheckout $checkout)
    {
        if (! $checkout->is_manual) {
            abort(404);
        }

        if ($checkout->returned_at) {
            return back()->with('error', 'This item has already been returned.');
        }

        $checkout->update([
            'returned_at'  => now(),
            'return_notes' => $request->input('return_notes'),
        ]);

        return back()->with('success', 'Item marked as returned.');
    }

    // -----------------------------------------------------------------------
    // CHECKOUT FORM (desktop — one or many samples in a single batch)
    // -----------------------------------------------------------------------

    public function checkoutForm(Request $request)
    {
        // Optional pre-fill (e.g. the "Check Out" button on a sample's or sample set's
        // detail page, or checked boxes on the index) — the page itself always lets you
        // search and add more, so this is just a convenience starting point, not a requirement.
        $sampleIds = array_filter(array_map('intval', (array) $request->input('samples', [])));
        $setIds    = array_filter(array_map('intval', (array) $request->input('sets', [])));

        $cartItems = collect();

        if ($sampleIds) {
            $cartItems = $cartItems->concat(
                Sample::whereIn('id', $sampleIds)
                    ->with('productStyle.productLine')
                    ->orderBy('sample_id')
                    ->get()
                    ->filter(fn (Sample $sample) => $sample->available_qty > 0)
                    ->map(fn (Sample $s) => [
                        'cart_key'      => 'sample-' . $s->id,
                        'type'          => 'sample',
                        'id'            => $s->id,
                        'display_id'    => $s->sample_id,
                        'style_name'    => $s->productStyle?->name,
                        'manufacturer'  => $s->productStyle?->productLine?->manufacturer,
                        'location'      => $s->location,
                        'available_qty' => $s->available_qty,
                    ])
            );
        }

        if ($setIds) {
            $cartItems = $cartItems->concat(
                SampleSet::whereIn('id', $setIds)
                    ->with('productLine')
                    ->where('status', 'active')
                    ->orderBy('set_id')
                    ->get()
                    ->map(fn (SampleSet $s) => [
                        'cart_key'      => 'set-' . $s->id,
                        'type'          => 'set',
                        'id'            => $s->id,
                        'display_id'    => $s->set_id,
                        'style_name'    => $s->name,
                        'manufacturer'  => $s->productLine?->manufacturer,
                        'location'      => $s->location,
                        'available_qty' => 1,
                    ])
            );
        }

        $staffUsers = User::whereHas('roles', fn ($q) => $q->whereNotIn('name', ['installer']))
            ->orderBy('name')->get(['id', 'name']);
        $defaultDays = (int) Setting::get('sample_checkout_days', 5);

        // "Add Items" from an existing checkout — borrower is locked to that checkout's.
        $addTo = null;
        if ($request->filled('add_to')) {
            $existing = $this->existingCheckoutHead((string) $request->input('add_to'));
            abort_unless($existing, 404);

            $addTo = [
                'checkout_number' => $existing->checkout_number,
                'checkout_type'   => $existing->checkout_type,
                'borrower_name'   => $existing->borrower_name,
                'customer_phone'  => $existing->customer_phone,
                'customer_email'  => $existing->customer_email,
                'due_back_at'     => $existing->due_back_at?->format('Y-m-d'),
                'destination'     => $existing->destination,
                'open_count'      => SampleCheckout::where('checkout_number', $existing->checkout_number)->whereNull('returned_at')->count(),
            ];
        }

        return view('pages.samples.checkout', [
            'cartItems'   => $cartItems->values(),
            'staffUsers'  => $staffUsers,
            'defaultDays' => $defaultDays,
            'addTo'       => $addTo,
        ]);
    }

    // -----------------------------------------------------------------------
    // CHECKOUT CUSTOMER SEARCH (AJAX — typeahead on the checkout page)
    // -----------------------------------------------------------------------

    public function searchCheckoutCustomers(Request $request)
    {
        $q = $request->input('q', '');

        $customers = Customer::where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('company_name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
                      ->orWhere('mobile', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'company_name', 'phone', 'mobile', 'email']);

        return response()->json($customers->map(function (Customer $c) {
            $label = $c->company_name && $c->name && $c->name !== $c->company_name
                ? "{$c->company_name} ({$c->name})"
                : ($c->company_name ?: $c->name);

            return [
                'id'    => $c->id,
                'label' => $label,
                'phone' => $c->mobile ?: $c->phone,
                'email' => $c->email,
            ];
        }));
    }

    // -----------------------------------------------------------------------
    // CHECKOUT SAMPLE SEARCH (AJAX — search-and-add on the checkout page)
    // -----------------------------------------------------------------------

    public function searchAvailable(Request $request)
    {
        $search = $request->input('q', '');

        $samples = Sample::with('productStyle.productLine')
            ->where('status', '<>', 'discontinued')
            ->where(function ($q) use ($search) {
                $q->where('sample_id', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('productStyle', fn ($s) =>
                      $s->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('color', 'like', "%{$search}%"))
                  ->orWhereHas('productStyle.productLine', fn ($pl) =>
                      $pl->where('name', 'like', "%{$search}%")
                         ->orWhere('manufacturer', 'like', "%{$search}%"));
            })
            ->orderBy('sample_id')
            ->limit(40)
            ->get()
            ->filter(fn (Sample $sample) => $sample->available_qty > 0)
            ->take(20)
            ->values();

        // Sample sets are checked out as one indivisible unit (a curated template of
        // styles, not physical inventory), so they show up alongside individual samples
        // in the same search-and-add cart, tagged by `type` so the UI can treat them
        // differently (fixed qty of 1, no per-unit availability count).
        $sets = SampleSet::with('productLine')
            ->where('status', 'active')
            ->where(function ($q) use ($search) {
                $q->where('set_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('productLine', fn ($pl) =>
                      $pl->where('name', 'like', "%{$search}%")
                         ->orWhere('manufacturer', 'like', "%{$search}%"));
            })
            ->orderBy('set_id')
            ->limit(10)
            ->get();

        $sampleResults = $samples->map(fn (Sample $s) => [
            'cart_key'      => 'sample-' . $s->id,
            'type'          => 'sample',
            'id'            => $s->id,
            'display_id'    => $s->sample_id,
            'style_name'    => $s->productStyle?->name,
            'manufacturer'  => $s->productStyle?->productLine?->manufacturer,
            'location'      => $s->location,
            'available_qty' => $s->available_qty,
        ]);

        $setResults = $sets->map(fn (SampleSet $s) => [
            'cart_key'      => 'set-' . $s->id,
            'type'          => 'set',
            'id'            => $s->id,
            'display_id'    => $s->set_id,
            'style_name'    => $s->name,
            'manufacturer'  => $s->productLine?->manufacturer,
            'location'      => $s->location,
            'available_qty' => 1,
        ]);

        return response()->json($sampleResults->concat($setResults)->values());
    }

    // -----------------------------------------------------------------------
    // CHECKOUT STORE
    // -----------------------------------------------------------------------

    public function storeCheckout(Request $request)
    {
        // Adding to an existing checkout: the borrower comes from that checkout,
        // never from the form, so the new lines can't drift onto someone else.
        $existing = null;
        if ($request->filled('add_to')) {
            $existing = $this->existingCheckoutHead((string) $request->input('add_to'));
            if (! $existing) {
                return back()->withErrors(['add_to' => 'That checkout could not be found.'])->withInput();
            }

            $request->merge([
                'checkout_type'  => $existing->checkout_type,
                'customer_id'    => $existing->customer_id,
                'customer_name'  => $existing->customer_name,
                'customer_phone' => $existing->customer_phone,
                'customer_email' => $existing->customer_email,
                'user_id'        => $existing->user_id,
            ]);
        }

        $validated = $request->validate([
            'checkout_type'   => ['required', 'in:customer,staff'],
            'sample_ids'      => ['nullable', 'array'],
            'sample_ids.*'    => ['integer', 'exists:samples,id'],
            'qty'             => ['nullable', 'array'],
            'qty.*'           => ['integer', 'min:1'],
            'set_ids'         => ['nullable', 'array'],
            'set_ids.*'       => ['integer', 'exists:sample_sets,id'],

            // Typed-in items that aren't in the system yet
            'manual_items'               => ['nullable', 'array', 'max:50'],
            'manual_items.*.description' => ['required', 'string', 'max:255'],
            'manual_items.*.details'     => ['nullable', 'string', 'max:255'],
            'manual_items.*.qty'         => ['required', 'integer', 'min:1', 'max:999'],

            'due_back_at'     => ['nullable', 'date', 'after_or_equal:today'],
            'destination'     => ['nullable', 'string', 'max:255'],

            // Customer fields
            'customer_id'     => ['nullable', 'exists:customers,id'],
            'customer_name'   => ['nullable', 'string', 'max:255'],
            'customer_phone'  => ['nullable', 'string', 'max:50'],
            'customer_email'  => ['nullable', 'email', 'max:255'],

            // Staff fields
            'user_id'         => ['nullable', 'exists:users,id'],
        ], [
            'manual_items.*.description.required' => 'Enter a description for each item that\'s not in the system.',
            'manual_items.*.qty.*'                => 'Enter a quantity of at least 1 for each item that\'s not in the system.',
        ]);

        $sampleIds   = $validated['sample_ids'] ?? [];
        $setIds      = $validated['set_ids'] ?? [];
        $manualItems = array_values($validated['manual_items'] ?? []);

        if (empty($sampleIds) && empty($setIds) && empty($manualItems)) {
            return back()->withErrors(['sample_ids' => 'Add at least one sample or set.'])->withInput();
        }

        if (! $existing
            && $validated['checkout_type'] === 'customer'
            && empty($validated['customer_id'])
            && empty($validated['customer_name'])) {
            return back()->withErrors(['customer_name' => 'Please select a customer or enter a name.'])->withInput();
        }

        if (! $existing && ! empty($validated['customer_id'])) {
            $customer = Customer::find($validated['customer_id']);
            $validated['customer_name']  = ($validated['customer_name']  ?? null) ?: ($customer->company_name ?: $customer->name);
            $validated['customer_phone'] = ($validated['customer_phone'] ?? null) ?: $customer->phone;
            $validated['customer_email'] = ($validated['customer_email'] ?? null) ?: $customer->email;
        }

        $samples = Sample::whereIn('id', $sampleIds)->get()->keyBy('id');
        $sets    = SampleSet::whereIn('id', $setIds)->get()->keyBy('id');

        // Confirm every requested quantity/availability is still good before committing
        // anything — it may have changed since the form was loaded.
        foreach ($sampleIds as $sampleId) {
            $sample = $samples->get($sampleId);
            $qty    = (int) ($validated['qty'][$sampleId] ?? 0);

            if (! $sample || $qty < 1) {
                return back()->withErrors(['qty' => 'Invalid quantity submitted.'])->withInput();
            }

            if ($qty > $sample->available_qty) {
                return back()
                    ->withErrors(['qty' => "Only {$sample->available_qty} of {$sample->sample_id} available — requested {$qty}."])
                    ->withInput();
            }
        }

        foreach ($setIds as $setId) {
            $set = $sets->get($setId);

            if (! $set || $set->status !== 'active') {
                return back()
                    ->withErrors(['set_ids' => ($set ? $set->set_id : 'A selected set') . ' is no longer available.'])
                    ->withInput();
            }
        }

        $batchId        = (string) \Illuminate\Support\Str::uuid();
        $checkoutNumber = $existing?->checkout_number ?? SampleCheckout::generateCheckoutNumber();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $samples, $sets, $sampleIds, $setIds, $manualItems, $batchId, $checkoutNumber, $existing, $request) {
            foreach ($sampleIds as $sampleId) {
                $sample = $samples->get($sampleId);
                $qty    = (int) $validated['qty'][$sampleId];

                SampleCheckout::create([
                    'sample_id'         => $sample->id,
                    'checkout_batch_id' => $batchId,
                    'checkout_number'   => $checkoutNumber,
                    'checkout_type'     => $validated['checkout_type'],
                    'qty_checked_out'   => $qty,
                    'due_back_at'       => $validated['due_back_at'] ?? null,
                    'destination'       => $validated['destination'] ?? null,
                    'customer_id'       => $validated['customer_id'] ?? null,
                    'customer_name'     => $validated['customer_name'] ?? null,
                    'customer_phone'    => $validated['customer_phone'] ?? null,
                    'customer_email'    => $validated['customer_email'] ?? null,
                    'user_id'           => $validated['user_id'] ?? null,
                ]);

                if ($sample->fresh()->available_qty <= 0) {
                    $sample->update(['status' => 'checked_out']);
                }
            }

            foreach ($setIds as $setId) {
                $set = $sets->get($setId);

                SampleCheckout::create([
                    'sample_set_id'     => $set->id,
                    'checkout_batch_id' => $batchId,
                    'checkout_number'   => $checkoutNumber,
                    'checkout_type'     => $validated['checkout_type'],
                    'due_back_at'       => $validated['due_back_at'] ?? null,
                    'destination'       => $validated['destination'] ?? null,
                    'customer_id'       => $validated['customer_id'] ?? null,
                    'customer_name'     => $validated['customer_name'] ?? null,
                    'customer_phone'    => $validated['customer_phone'] ?? null,
                    'customer_email'    => $validated['customer_email'] ?? null,
                    'user_id'           => $validated['user_id'] ?? null,
                ]);

                $set->update(['status' => 'checked_out']);
            }

            foreach ($manualItems as $manual) {
                SampleCheckout::create([
                    'manual_description' => trim($manual['description']),
                    'manual_details'     => filled($manual['details'] ?? null) ? trim($manual['details']) : null,
                    'checkout_batch_id'  => $batchId,
                    'checkout_number'    => $checkoutNumber,
                    'checkout_type'      => $validated['checkout_type'],
                    'qty_checked_out'    => (int) $manual['qty'],
                    'due_back_at'        => $validated['due_back_at'] ?? null,
                    'destination'        => $validated['destination'] ?? null,
                    'customer_id'        => $validated['customer_id'] ?? null,
                    'customer_name'      => $validated['customer_name'] ?? null,
                    'customer_phone'     => $validated['customer_phone'] ?? null,
                    'customer_email'     => $validated['customer_email'] ?? null,
                    'user_id'            => $validated['user_id'] ?? null,
                ]);
            }

            // Optionally move everything still out on this checkout to the new due date,
            // so the whole checkout stays on one schedule (and one reminder).
            if ($existing && $request->boolean('apply_due_to_open') && ! empty($validated['due_back_at'])) {
                SampleCheckout::where('checkout_number', $checkoutNumber)
                    ->whereNull('returned_at')
                    ->update(['due_back_at' => $validated['due_back_at']]);
            }
        });

        $count   = count($sampleIds) + count($setIds) + count($manualItems);
        $borrower = $validated['checkout_type'] === 'customer'
            ? ($validated['customer_name'] ?? 'the customer')
            : (User::find($validated['user_id'])?->name ?? 'staff');

        $message = $existing
            ? "{$count} item" . ($count === 1 ? '' : 's') . " added to checkout {$checkoutNumber} for {$existing->borrower_name}."
            : "{$count} item" . ($count === 1 ? '' : 's') . " checked out to {$borrower}. Reference # {$checkoutNumber}.";

        return redirect()->route('pages.samples.checkouts.show', $checkoutNumber)
            ->with('success', $message);
    }

    /**
     * The first (original) row of an existing checkout — holds the borrower
     * details every line in that checkout shares.
     */
    private function existingCheckoutHead(string $checkoutNumber): ?SampleCheckout
    {
        return SampleCheckout::with(['customer', 'user'])
            ->where('checkout_number', $checkoutNumber)
            ->orderBy('id')
            ->first();
    }

    // -----------------------------------------------------------------------
    // CHECKOUTS — consolidated list (one row per checkout event, not per sample)
    // -----------------------------------------------------------------------

    public function checkoutsIndex(Request $request)
    {
        $search     = $request->input('q', '');
        $manualOnly = $request->boolean('manual');

        $base = SampleCheckout::whereNotNull('checkout_number')
            ->when($search, fn ($q) => $q->where(function ($qq) use ($search) {
                $qq->where('checkout_number', 'like', "%{$search}%")
                   ->orWhere('customer_name', 'like', "%{$search}%")
                   ->orWhere('manual_description', 'like', "%{$search}%");
            }))
            ->when($manualOnly, fn ($q) => $q->whereNull('sample_id')->whereNull('sample_set_id')->whereNotNull('manual_description'));

        $numbers = (clone $base)
            ->select('checkout_number')
            ->selectRaw('MAX(checked_out_at) as latest')
            ->groupBy('checkout_number')
            ->orderByDesc('latest')
            ->paginate(25)
            ->withQueryString();

        $rows = SampleCheckout::whereIn('checkout_number', $numbers->pluck('checkout_number'))
            ->with(['sample.productStyle.productLine', 'sampleSet.productLine', 'customer', 'user'])
            ->get()
            ->groupBy('checkout_number');

        $checkouts = $numbers->getCollection()->map(function ($row) use ($rows) {
            $items         = $rows->get($row->checkout_number, collect());
            $first         = $items->first();
            $total         = $items->count();
            $returnedCount = $items->filter(fn ($i) => $i->returned_at !== null)->count();
            $anyOverdue    = $items->contains(fn ($i) => $i->is_overdue);

            $status = $returnedCount === $total
                ? 'returned'
                : ($returnedCount > 0 ? 'partial' : ($anyOverdue ? 'overdue' : 'active'));

            return (object) [
                'checkout_number' => $row->checkout_number,
                'borrower_name'   => $first?->borrower_name,
                'checkout_type'   => $first?->checkout_type,
                'checked_out_at'  => $first?->checked_out_at,
                'due_back_at'     => $first?->due_back_at,
                'item_count'      => $total,
                'manual_count'    => $items->filter(fn ($i) => $i->is_manual)->count(),
                'returned_count'  => $returnedCount,
                'status'          => $status,
            ];
        });

        $numbers->setCollection($checkouts);

        return view('pages.samples.checkouts.index', ['checkouts' => $numbers, 'search' => $search, 'manualOnly' => $manualOnly]);
    }

    // -----------------------------------------------------------------------
    // CHECKOUTS — detail page (every sample in one checkout event)
    // -----------------------------------------------------------------------

    public function checkoutShow(string $checkoutNumber)
    {
        $items = SampleCheckout::where('checkout_number', $checkoutNumber)
            ->with(['sample.productStyle.productLine', 'sampleSet.productLine', 'customer', 'user'])
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            abort(404);
        }

        return view('pages.samples.checkouts.show', [
            'checkoutNumber' => $checkoutNumber,
            'items'          => $items,
        ]);
    }

    // -----------------------------------------------------------------------
    // CHECKOUTS — return every still-open sample in one checkout event
    // -----------------------------------------------------------------------

    public function returnAllForCheckout(Request $request, string $checkoutNumber)
    {
        $items = SampleCheckout::where('checkout_number', $checkoutNumber)
            ->whereNull('returned_at')
            ->with(['sample', 'sampleSet'])
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Nothing left to return on this checkout.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($items, $request) {
            foreach ($items as $checkout) {
                $checkout->update([
                    'returned_at'  => now(),
                    'return_notes' => $request->input('return_notes'),
                ]);

                $sample = $checkout->sample;
                if ($sample && $sample->status === 'checked_out' && $sample->activeCheckouts()->doesntExist()) {
                    $sample->update(['status' => 'active']);
                }

                $set = $checkout->sampleSet;
                if ($set && $set->status === 'checked_out' && $set->activeCheckout()->doesntExist()) {
                    $set->update(['status' => 'active']);
                }
            }
        });

        return redirect()->route('pages.samples.checkouts.show', $checkoutNumber)
            ->with('success', 'Everything in this checkout marked as returned.');
    }

    // -----------------------------------------------------------------------
    // CHECKOUTS — printable PDF receipt
    // -----------------------------------------------------------------------

    public function checkoutReceipt(string $checkoutNumber)
    {
        $items = SampleCheckout::where('checkout_number', $checkoutNumber)
            ->with(['sample.productStyle.productLine', 'sampleSet.productLine'])
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            abort(404);
        }

        $first = $items->first();

        $logoPath    = Setting::get('branding_logo_path');
        $logoDataUri = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoData    = Storage::disk('public')->get($logoPath);
            $logoMime    = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }

        $companyName = Setting::get('branding_company_name', 'RM Flooring');

        $pdf = Pdf::loadView('pdf.sample-checkout-receipt', [
            'checkoutNumber' => $checkoutNumber,
            'items'          => $items,
            'first'          => $first,
            'logoDataUri'    => $logoDataUri,
            'companyName'    => $companyName,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream("checkout-{$checkoutNumber}.pdf");
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
    // PRODUCT LINE LABEL FORM
    // -----------------------------------------------------------------------

    public function productLineLabelForm(Request $request)
    {
        $search    = $request->input('search', '');
        $typeId    = $request->input('type_id', '');

        $q = ProductLine::with('productType')
            ->where('status', 'active')
            ->orderBy('name');

        if ($search) {
            $q->where(function ($sq) use ($search) {
                $sq->where('name', 'like', "%{$search}%")
                   ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($typeId) {
            $q->where('product_type_id', $typeId);
        }

        $productLines = $q->withCount(['productStyles' => fn ($sq) => $sq->where('status', 'active')])->get();
        $productTypes = ProductType::orderBy('name')->get(['id', 'name']);

        return view('pages.samples.product-line-label-form', compact(
            'productLines', 'productTypes', 'search', 'typeId'
        ));
    }

    // -----------------------------------------------------------------------
    // PRODUCT LINE LABEL PDF
    // -----------------------------------------------------------------------

    public function productLineLabel(Request $request)
    {
        $lineIds      = array_filter(array_map('intval', (array) $request->input('lines', [])));
        $showPrice    = $request->boolean('show_price', false);
        $qty          = $request->input('qty', []);
        $topOffsetMm  = max(-20, min(20, (float) $request->input('top_offset_mm', 0)));
        $leftOffsetMm = max(-20, min(20, (float) $request->input('left_offset_mm', 0)));

        if (empty($lineIds)) {
            return redirect()->route('pages.samples.product-line-labels.form')
                ->with('error', 'Select at least one product line before printing labels.');
        }

        $lines = ProductLine::whereIn('id', $lineIds)
            ->with(['productType', 'unit'])
            ->withCount(['productStyles' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->keyBy('id');

        // Branding
        $logoPath    = Setting::get('branding_logo_path');
        $logoDataUri = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoData    = Storage::disk('public')->get($logoPath);
            $logoMime    = Storage::disk('public')->mimeType($logoPath);
            $logoDataUri = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
        }
        $companyName = Setting::get('branding_company_name', 'RM Flooring');

        $labels = [];

        foreach ($lineIds as $id) {
            if (! $lines->has($id)) {
                continue;
            }
            $line   = $lines[$id];
            $copies = max(1, min(20, (int) ($qty["l_{$id}"] ?? 1)));
            $qrSvg  = base64_encode(QrCode::format('svg')->size(100)->generate(route('scan.product-line', $line->id)));
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = [
                    'model'       => $line,
                    'qrSvg'       => $qrSvg,
                    'style_count' => $line->product_styles_count,
                ];
            }
        }

        if (empty($labels)) {
            return redirect()->route('pages.samples.product-line-labels.form')
                ->with('error', 'No valid product lines found for the selection.');
        }

        $rows = array_chunk($labels, 2);

        return Pdf::loadView('pdf.product-line-batch-labels-5163', compact(
            'rows', 'logoDataUri', 'companyName', 'showPrice', 'topOffsetMm', 'leftOffsetMm'
        ))
            ->setPaper('letter')
            ->stream('product-line-labels.pdf');
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
