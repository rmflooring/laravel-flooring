<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\FlooringSignOff;
use App\Models\FlooringSignOffCondition;
use App\Models\FlooringSignOffItem;
use App\Models\Opportunity;
use App\Models\Sale;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FlooringSignOffController extends Controller
{
    // ── Step 1: item picker ───────────────────────────────────────────────────

    public function create(Opportunity $opportunity, Request $request)
    {
        $sale = Sale::with('rooms.items')->findOrFail($request->sale_id);
        abort_if((int) $sale->opportunity_id !== (int) $opportunity->id, 404);

        $opportunity->loadMissing(['parentCustomer', 'jobSiteCustomer', 'projectManager']);

        $customer = $opportunity->parentCustomer;
        $jobSite  = $opportunity->jobSiteCustomer;
        $pm       = $opportunity->projectManager;

        $defaults = [
            'date'             => now()->format('Y-m-d'),
            'customer_name'    => $customer?->company_name ?: ($customer?->name ?? ''),
            'job_no'           => $opportunity->job_no ?? '',
            'job_site_name'    => $jobSite?->company_name ?: ($jobSite?->name ?? ''),
            'job_site_address' => implode(', ', array_filter([
                $jobSite?->address, $jobSite?->city, $jobSite?->province, $jobSite?->postal_code,
            ])),
            'job_site_phone'   => $jobSite?->phone ?? '',
            'job_site_email'   => $jobSite?->email ?? '',
            'pm_name'          => $pm?->name ?? '',
        ];

        // Group material items by room, skip empty rooms
        $rooms = $sale->rooms
            ->map(fn ($room) => [
                'room'  => $room,
                'items' => $room->items->where('item_type', 'material')->values(),
            ])
            ->filter(fn ($r) => $r['items']->isNotEmpty())
            ->values();

        return view('pages.opportunities.sign-offs.create', compact(
            'opportunity', 'sale', 'rooms', 'defaults'
        ));
    }

    // ── Step 2: save + redirect to editable page ──────────────────────────────

    public function store(Opportunity $opportunity, Request $request)
    {
        $request->validate([
            'sale_id'          => ['required', 'exists:sales,id'],
            'date'             => ['required', 'date'],
            'customer_name'    => ['nullable', 'string', 'max:255'],
            'job_no'           => ['nullable', 'string', 'max:100'],
            'job_site_name'    => ['nullable', 'string', 'max:255'],
            'job_site_address' => ['nullable', 'string'],
            'job_site_phone'   => ['nullable', 'string', 'max:50'],
            'job_site_email'   => ['nullable', 'string', 'max:255'],
            'pm_name'          => ['nullable', 'string', 'max:255'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.room_name'           => ['required', 'string'],
            'items.*.product_description' => ['nullable', 'string'],
        ]);

        $signOff = FlooringSignOff::create([
            'opportunity_id'   => $opportunity->id,
            'sale_id'          => $request->sale_id,
            'status'           => 'draft',
            'date'             => $request->date,
            'customer_name'    => $request->customer_name ?? '',
            'job_no'           => $request->job_no ?? '',
            'job_site_name'    => $request->job_site_name ?? '',
            'job_site_address' => $request->job_site_address,
            'job_site_phone'   => $request->job_site_phone,
            'job_site_email'   => $request->job_site_email,
            'pm_name'          => $request->pm_name,
        ]);

        foreach ($request->items as $index => $itemData) {
            FlooringSignOffItem::create([
                'sign_off_id'         => $signOff->id,
                'room_name'           => $itemData['room_name'],
                'product_description' => $itemData['product_description'] ?? '',
                'color_item_number'   => $itemData['color_item_number'] ?? null,
                'sort_order'          => $index,
            ]);
        }

        return redirect()
            ->route('pages.opportunities.sign-offs.show', [$opportunity->id, $signOff->id])
            ->with('success', 'Sign-off created. Review and save below.');
    }

    // ── Editable sign-off page ────────────────────────────────────────────────

    public function show(Opportunity $opportunity, FlooringSignOff $signOff)
    {
        $this->assertBelongs($opportunity, $signOff);

        $signOff->load('items', 'condition', 'sale');
        $conditions = FlooringSignOffCondition::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $branding = $this->branding();

        return view('pages.opportunities.sign-offs.show', compact(
            'opportunity', 'signOff', 'conditions', 'branding'
        ));
    }

    // ── Save edits ────────────────────────────────────────────────────────────

    public function update(Opportunity $opportunity, FlooringSignOff $signOff, Request $request)
    {
        $this->assertBelongs($opportunity, $signOff);

        $request->validate([
            'date'             => ['required', 'date'],
            'customer_name'    => ['nullable', 'string', 'max:255'],
            'job_no'           => ['nullable', 'string', 'max:100'],
            'job_site_name'    => ['nullable', 'string', 'max:255'],
            'job_site_address' => ['nullable', 'string'],
            'job_site_phone'   => ['nullable', 'string', 'max:50'],
            'job_site_email'   => ['nullable', 'string', 'max:255'],
            'pm_name'          => ['nullable', 'string', 'max:255'],
            'condition_id'     => ['nullable', 'exists:flooring_sign_off_conditions,id'],
            'condition_text'   => ['nullable', 'string'],
            'status'           => ['nullable', 'string', 'in:draft,finalized'],
            'items'            => ['nullable', 'array'],
            'items.*.room_name'           => ['required_with:items', 'string'],
            'items.*.product_description' => ['nullable', 'string'],
        ]);

        $signOff->update([
            'date'             => $request->date,
            'customer_name'    => $request->customer_name ?? '',
            'job_no'           => $request->job_no ?? '',
            'job_site_name'    => $request->job_site_name ?? '',
            'job_site_address' => $request->job_site_address,
            'job_site_phone'   => $request->job_site_phone,
            'job_site_email'   => $request->job_site_email,
            'pm_name'          => $request->pm_name,
            'condition_id'     => $request->condition_id ?: null,
            'condition_text'   => $request->condition_text,
            'status'           => $request->status ?? $signOff->status,
        ]);

        $signOff->items()->delete();

        foreach ($request->items ?? [] as $index => $itemData) {
            FlooringSignOffItem::create([
                'sign_off_id'         => $signOff->id,
                'room_name'           => $itemData['room_name'],
                'product_description' => $itemData['product_description'] ?? '',
                'color_item_number'   => $itemData['color_item_number'] ?? null,
                'sort_order'          => $index,
            ]);
        }

        return back()->with('success', 'Sign-off saved.');
    }

    // ── PDF ───────────────────────────────────────────────────────────────────

    public function pdf(Opportunity $opportunity, FlooringSignOff $signOff)
    {
        $this->assertBelongs($opportunity, $signOff);

        $signOff->load('items', 'condition');
        $branding   = $this->branding();
        $logoDataUri = null;

        if ($branding['logo_path'] && Storage::disk('public')->exists($branding['logo_path'])) {
            $raw         = Storage::disk('public')->get($branding['logo_path']);
            $mime        = Storage::disk('public')->mimeType($branding['logo_path']);
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $pdf = Pdf::loadView('pdf.flooring-sign-off', compact('signOff', 'branding', 'logoDataUri'))
            ->setPaper('letter', 'portrait');

        $filename = 'flooring-sign-off-' . $signOff->id . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertBelongs(Opportunity $opportunity, FlooringSignOff $signOff): void
    {
        abort_if((int) $signOff->opportunity_id !== (int) $opportunity->id, 404);
    }

    private function branding(): array
    {
        return [
            'company_name' => Setting::get('branding_company_name', 'RM Flooring'),
            'tagline'      => Setting::get('branding_tagline', ''),
            'street'       => Setting::get('branding_street', ''),
            'city'         => Setting::get('branding_city', ''),
            'province'     => Setting::get('branding_province', ''),
            'postal'       => Setting::get('branding_postal', ''),
            'phone'        => Setting::get('branding_phone', ''),
            'email'        => Setting::get('branding_email', ''),
            'website'      => Setting::get('branding_website', ''),
            'logo_path'    => Setting::get('branding_logo_path', ''),
        ];
    }
}
