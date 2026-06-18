<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\IncomingLead;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadManagementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        if (! in_array($status, ['pending', 'approved', 'denied'])) {
            $status = 'pending';
        }

        $leads = IncomingLead::where('status', $status)
            ->with('reviewer')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $pendingCount = IncomingLead::where('status', 'pending')->count();

        return view('pages.leads.index', compact('leads', 'status', 'pendingCount'));
    }

    public function show(IncomingLead $lead)
    {
        $lead->load('opportunity', 'reviewer');
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('pages.leads.show', compact('lead', 'users'));
    }

    public function approve(Request $request, IncomingLead $lead)
    {
        if (! $lead->isPending()) {
            return back()->with('error', 'This lead has already been reviewed.');
        }

        $validated = $request->validate([
            'opportunity_name' => 'required|string|max:255',
            'assigned_to'      => 'nullable|exists:users,id',
            'notes'            => 'nullable|string|max:5000',
        ]);

        $opportunity = DB::transaction(function () use ($lead, $validated) {
            $customer = Customer::create([
                'name'  => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'notes' => 'Created from web lead — ' . $lead->source,
            ]);

            $opportunity = Opportunity::create([
                'parent_customer_id'   => $customer->id,
                'job_site_customer_id' => $customer->id,
                'job_no'               => $validated['opportunity_name'],
                'status'               => 'New',
            ]);

            $noteLines = array_filter([
                $lead->service_type    ? 'Service: ' . $lead->service_type           : null,
                $lead->project_type    ? 'Project Type: ' . $lead->project_type       : null,
                $lead->area            ? 'Area: ' . $lead->area                       : null,
                $lead->timeline        ? 'Timeline: ' . $lead->timeline               : null,
                $lead->referral_source ? 'Referral: ' . $lead->referral_source        : null,
                $lead->message         ? "\n" . $lead->message                        : null,
                ! empty($validated['notes']) && $validated['notes'] !== $lead->message
                    ? "\n---\n" . $validated['notes']
                    : null,
            ]);

            if (! empty($noteLines)) {
                OpportunityNote::create([
                    'opportunity_id' => $opportunity->id,
                    'user_id'        => auth()->id(),
                    'body'           => implode("\n", $noteLines),
                ]);
            }

            $lead->update([
                'status'         => 'approved',
                'opportunity_id' => $opportunity->id,
                'reviewed_by'    => auth()->id(),
                'reviewed_at'    => now(),
            ]);

            return $opportunity;
        });

        return redirect()
            ->route('pages.opportunities.show', $opportunity)
            ->with('success', 'Lead approved — Opportunity created.');
    }

    public function deny(Request $request, IncomingLead $lead)
    {
        if (! $lead->isPending()) {
            return back()->with('error', 'This lead has already been reviewed.');
        }

        $validated = $request->validate([
            'denial_reason' => 'nullable|string|max:1000',
        ]);

        $lead->update([
            'status'        => 'denied',
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'denial_reason' => $validated['denial_reason'] ?? null,
        ]);

        return redirect()
            ->route('pages.leads.index')
            ->with('success', 'Lead denied.');
    }
}
