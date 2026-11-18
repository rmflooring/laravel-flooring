<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsConversation;
use App\Services\SmsPortalSender;
use Illuminate\Http\Request;

/**
 * The SMS inbox on mobile (/m/sms). Sending goes through SmsPortalSender, same as the
 * desktop portal. Linking customers, creating opportunities, delivery errors and
 * permanent delete stay desktop-only.
 */
class SmsController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->input('tab'), ['unread', 'archived'], true) ? $request->input('tab') : 'inbox';

        $query = SmsConversation::with(['customer', 'latestMessage'])->orderByDesc('last_message_at');

        match ($tab) {
            'archived' => $query->archived(),
            'unread'   => $query->active()->unread(),
            default    => $query->active(),
        };

        $conversations = $query->paginate(25)->withQueryString();
        $unreadCount   = (int) SmsConversation::active()->sum('unread_count');

        return view('mobile.sms.index', compact('conversations', 'tab', 'unreadCount'));
    }

    public function show(SmsConversation $conversation)
    {
        $conversation->load(['customer', 'opportunity', 'messages.sentBy']);

        // Opening the thread marks it read, same as desktop
        $conversation->messages()
            ->where('direction', 'inbound')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $conversation->update(['unread_count' => 0]);

        return view('mobile.sms.show', compact('conversation'));
    }

    public function reply(Request $request, SmsConversation $conversation, SmsPortalSender $sender)
    {
        $validated = $request->validate($sender->rules(), $sender->messages());

        $result = $sender->reply($conversation, $validated['body'] ?? '', $request->file('photos', []));

        if (! $result['ok']) {
            return back()->withInput()->with(...$result['flash']);
        }

        return redirect()->route('mobile.sms.show', $conversation)->with(...$result['flash']);
    }

    public function create(Request $request)
    {
        $customer = $request->filled('customer_id') ? Customer::find($request->input('customer_id')) : null;

        return view('mobile.sms.create', compact('customer'));
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

        return redirect()->route('mobile.sms.show', $result['conversation'])->with(...$result['flash']);
    }

    public function archive(SmsConversation $conversation)
    {
        $conversation->update(['status' => 'archived']);

        return redirect()->route('mobile.sms.index')->with('success', 'Conversation archived.');
    }

    public function unarchive(SmsConversation $conversation)
    {
        $conversation->update(['status' => 'active']);

        return redirect()->route('mobile.sms.show', $conversation)->with('success', 'Conversation restored to inbox.');
    }
}
