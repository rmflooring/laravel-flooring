<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessNewLead;
use App\Models\IncomingLead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class IncomingLeadController extends Controller
{
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source'          => 'required|string|max:100',
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:20',
            'email'           => 'nullable|email|max:255',
            'sms_consent'     => 'boolean',
            'service_type'    => 'nullable|string|max:100',
            'project_type'    => 'nullable|string|max:100',
            'area'            => 'nullable|string|max:100',
            'timeline'        => 'nullable|string|max:100',
            'message'         => 'nullable|string|max:2000',
            'referral_source' => 'nullable|string|max:100',
            'city'            => 'nullable|string|max:100',
            'ip_address'      => 'nullable|string|max:100',
        ]);

        // Visitor IP forwarded by the website form. Never reject a lead over a bad value — just drop it.
        $ip = filter_var(trim((string) ($validated['ip_address'] ?? '')), FILTER_VALIDATE_IP) ?: null;
        unset($validated['ip_address']);
        if ($ip && Schema::hasColumn('incoming_leads', 'ip_address')) {
            $validated['ip_address'] = $ip;
        }

        $lead = IncomingLead::create($validated);

        ProcessNewLead::dispatch($lead->id);

        return response()->json(['success' => true, 'lead_id' => $lead->id]);
    }
}
