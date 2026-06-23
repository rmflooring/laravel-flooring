<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\SmsConversation;
use App\Models\SmsMessage;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsPortalController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->input('view', 'active');

        $query = SmsConversation::with(['customer', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])->orderByDesc('last_message_at');

        if ($view === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }

        $conversations = $query->paginate(30)->withQueryString();

        $totalUnread   = SmsConversation::sum('unread_count');
        $archivedCount = SmsConversation::archived()->count();

        return view('pages.sms.index', compact('conversations', 'totalUnread', 'archivedCount', 'view'));
    }

    public function show(SmsConversation $conversation)
    {
        $conversation->load(['customer', 'opportunity.parentCustomer', 'messages.sentBy']);

        // Mark all unread inbound messages as read
        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->update(['unread_count' => 0]);

        return view('pages.sms.show', compact('conversation'));
    }

    public function reply(Request $request, SmsConversation $conversation)
    {
        $validated = $request->validate([
            'body' => 'required|string|max:1600',
        ]);

        $smsService = app(SmsService::class);

        $sent = $smsService->send(
            $conversation->phone,
            $validated['body'],
            'portal_reply',
            $conversation->customer
        );

        if (! $sent) {
            return back()->with('error', 'Failed to send SMS. Check SMS settings.');
        }

        SmsMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'body'            => $validated['body'],
            'sent_by_id'      => auth()->id(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('pages.sms.show', $conversation)
            ->with('success', 'Message sent.');
    }

    public function unreadCount()
    {
        return response()->json([
            'count' => (int) SmsConversation::sum('unread_count'),
        ]);
    }

    public function archive(SmsConversation $conversation)
    {
        $conversation->update(['status' => 'archived']);

        return redirect()->route('pages.sms.index')
            ->with('success', 'Conversation archived.');
    }

    public function unarchive(SmsConversation $conversation)
    {
        $conversation->update(['status' => 'active']);

        return redirect()->route('pages.sms.show', $conversation)
            ->with('success', 'Conversation restored to inbox.');
    }

    public function destroy(SmsConversation $conversation)
    {
        $conversation->delete();

        return redirect()->route('pages.sms.index')
            ->with('success', 'Conversation permanently deleted.');
    }

    public function searchCustomers(Request $request)
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
            ->get(['id', 'name', 'company_name', 'phone', 'mobile']);

        return response()->json($customers->map(fn ($c) => [
            'id'    => $c->id,
            'label' => $c->company_name ? "{$c->company_name} ({$c->name})" : $c->name,
            'phone' => $c->mobile ?: $c->phone,
        ]));
    }

    public function compose(Request $request)
    {
        $validated = $request->validate([
            'phone'       => 'required|string|max:20',
            'body'        => 'required|string|max:1600',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $smsService = app(SmsService::class);
        $customer   = $validated['customer_id'] ? Customer::find($validated['customer_id']) : null;
        $normalized = $smsService->normalizePhone($validated['phone']);

        $sent = $smsService->send($normalized, $validated['body'], 'portal_outbound', $customer);

        if (! $sent) {
            return back()->withInput()->with('error', 'Failed to send SMS. Check SMS settings.');
        }

        $conversation = SmsConversation::firstOrCreate(
            ['phone' => $normalized],
            ['status' => 'active', 'customer_id' => $customer?->id]
        );

        if ($customer && ! $conversation->customer_id) {
            $conversation->update(['customer_id' => $customer->id]);
        }

        SmsMessage::create([
            'conversation_id' => $conversation->id,
            'direction'       => 'outbound',
            'body'            => $validated['body'],
            'sent_by_id'      => auth()->id(),
        ]);

        $conversation->update(['last_message_at' => now()]);

        return redirect()->route('pages.sms.show', $conversation)
            ->with('success', 'Message sent.');
    }

    public function linkCustomer(Request $request, SmsConversation $conversation)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::find($validated['customer_id']);

        $opportunity = Opportunity::where(function ($q) use ($customer) {
                $q->where('parent_customer_id', $customer->id)
                  ->orWhere('job_site_customer_id', $customer->id);
            })
            ->where('is_active', true)
            ->latest()
            ->first();

        $conversation->update([
            'customer_id'    => $customer->id,
            'opportunity_id' => $opportunity?->id,
        ]);

        return redirect()->route('pages.sms.show', $conversation)
            ->with('success', 'Conversation linked to ' . $customer->name . '.');
    }

    public function createOpportunity(Request $request, SmsConversation $conversation)
    {
        $validated = $request->validate([
            'opportunity_name' => 'required|string|max:255',
            'notes'            => 'nullable|string|max:5000',
        ]);

        $opportunity = DB::transaction(function () use ($conversation, $validated) {
            $customer = Customer::create([
                'name'  => $validated['opportunity_name'],
                'phone' => $conversation->phone,
                'notes' => 'Created from SMS conversation',
            ]);

            $opportunity = Opportunity::create([
                'parent_customer_id'   => $customer->id,
                'job_site_customer_id' => $customer->id,
                'job_no'               => $validated['opportunity_name'],
                'status'               => 'New',
                'is_active'            => true,
            ]);

            if (! empty($validated['notes'])) {
                OpportunityNote::create([
                    'opportunity_id' => $opportunity->id,
                    'user_id'        => auth()->id(),
                    'body'           => $validated['notes'],
                ]);
            }

            $conversation->update([
                'customer_id'    => $customer->id,
                'opportunity_id' => $opportunity->id,
            ]);

            return $opportunity;
        });

        return redirect()->route('pages.opportunities.show', $opportunity)
            ->with('success', 'Opportunity created from SMS conversation.');
    }
}
