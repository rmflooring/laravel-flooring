<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Models\SmsConversation;
use App\Models\SmsLog;
use App\Models\SmsMessage;
use App\Services\SmsMediaStore;
use App\Services\SmsPortalSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsPortalController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->input('view', 'active');

        $totalUnread   = SmsConversation::sum('unread_count');
        $archivedCount = SmsConversation::archived()->count();
        // Badge only counts recent failures so old ones don't nag forever; the tab lists all.
        $errorCount    = SmsLog::whereIn('status', ['failed', 'unconfirmed'])->where('created_at', '>=', now()->subDays(7))->count();

        if ($view === 'errors') {
            $failures = SmsLog::whereIn('status', ['failed', 'unconfirmed'])->latest()->paginate(30)->withQueryString();

            return view('pages.sms.index', compact('failures', 'totalUnread', 'archivedCount', 'errorCount', 'view'));
        }

        $query = SmsConversation::with(['customer', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])->orderByDesc('last_message_at');

        if ($view === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }

        $unreadOnly = $request->boolean('unread');
        if ($unreadOnly) {
            $query->where('unread_count', '>', 0);
        }

        $conversations = $query->paginate(30)->withQueryString();

        return view('pages.sms.index', compact('conversations', 'totalUnread', 'archivedCount', 'errorCount', 'view', 'unreadOnly'));
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

    public function reply(Request $request, SmsConversation $conversation, SmsPortalSender $sender)
    {
        $validated = $request->validate($sender->rules(), $sender->messages());

        $result = $sender->reply($conversation, $validated['body'] ?? '', $request->file('photos', []));

        if (! $result['ok']) {
            return back()->withInput()->with(...$result['flash']);
        }

        return redirect()->route('pages.sms.show', $conversation)->with(...$result['flash']);
    }

    /**
     * Shows a picture from a message — kept on the private disk, so only signed-in staff
     * with access to the SMS portal can open it.
     */
    public function media(SmsMessage $smsMessage, int $index, SmsMediaStore $store)
    {
        $item = $smsMessage->media[$index] ?? null;

        if (! $item || ! $store->disk()->exists($item['path'])) {
            abort(404);
        }

        return $store->disk()->response($item['path'], null, [
            'Content-Type'  => $item['mime'],
            'Cache-Control' => 'private, max-age=86400',
        ]);
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
        $media = $conversation->messages()->whereNotNull('media')->pluck('media')->flatten(1)->all();

        $conversation->delete();

        app(SmsMediaStore::class)->delete($media);

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

    public function compose(Request $request, SmsPortalSender $sender)
    {
        $validated = $request->validate([
            'phone'       => 'required|string|max:20',
            'customer_id' => 'nullable|exists:customers,id',
        ] + $sender->rules(), $sender->messages());

        $customer = ($validated['customer_id'] ?? null) ? Customer::find($validated['customer_id']) : null;
        $result   = $sender->compose($validated['phone'], $customer, $validated['body'] ?? '', $request->file('photos', []));

        if (! $result['ok']) {
            return back()->withInput()->with(...$result['flash']);
        }

        return redirect()->route('pages.sms.show', $result['conversation'])->with(...$result['flash']);
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
