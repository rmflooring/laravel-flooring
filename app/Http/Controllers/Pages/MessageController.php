<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        $threads = MessageThread::whereHas('participants', fn ($q) => $q->where('user_id', $userId))
            ->with([
                'latestMessage.sender',
                'participants',
                'threadable',
            ])
            ->get()
            ->each(function ($thread) {
                // Lazy-load nested relations for context label
                if ($thread->threadable_type === 'App\\Models\\Opportunity') {
                    $thread->threadable?->loadMissing('jobSiteCustomer');
                } elseif ($thread->threadable_type === 'App\\Models\\Sale') {
                    $thread->threadable?->loadMissing('opportunity.jobSiteCustomer');
                }
            })
            ->sortByDesc(fn ($t) => optional($t->latestMessage)->created_at ?? $t->created_at)
            ->values();

        // Pre-fill data for "new thread from show page" links
        $preThreadableType = $request->get('threadable_type'); // 'opportunity' or 'sale'
        $preThreadableId   = $request->get('threadable_id');
        $preOpen           = $request->boolean('new');

        return view('pages.messages.index', compact('threads', 'preThreadableType', 'preThreadableId', 'preOpen'));
    }

    public function show(MessageThread $thread)
    {
        $userId = auth()->id();

        abort_unless(
            $thread->participants()->where('user_id', $userId)->exists(),
            403
        );

        $thread->load(['messages.sender', 'participants', 'threadable']);

        if ($thread->threadable_type === 'App\\Models\\Opportunity') {
            $thread->threadable?->loadMissing('jobSiteCustomer');
        } elseif ($thread->threadable_type === 'App\\Models\\Sale') {
            $thread->threadable?->loadMissing('opportunity.jobSiteCustomer');
        }

        // Mark read
        $thread->participants()->updateExistingPivot($userId, ['last_read_at' => now()]);

        return view('pages.messages.show', compact('thread'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'         => 'required|string|max:255',
            'recipients'      => 'required|array|min:1',
            'recipients.*'    => 'exists:users,id',
            'body'            => 'required|string|max:10000',
            'threadable_type' => 'nullable|in:opportunity,sale',
            'threadable_id'   => 'nullable|integer',
        ]);

        $threadableType = null;
        $threadableId   = null;
        if ($request->threadable_type && $request->threadable_id) {
            $map = [
                'opportunity' => \App\Models\Opportunity::class,
                'sale'        => \App\Models\Sale::class,
            ];
            $threadableType = $map[$request->threadable_type] ?? null;
            $threadableId   = (int) $request->threadable_id;
        }

        $userId = auth()->id();

        $thread = DB::transaction(function () use ($request, $userId, $threadableType, $threadableId) {
            $thread = MessageThread::create([
                'subject'         => $request->subject,
                'created_by'      => $userId,
                'threadable_type' => $threadableType,
                'threadable_id'   => $threadableId,
            ]);

            $participants = collect($request->recipients)
                ->push($userId)
                ->unique()
                ->mapWithKeys(fn ($id) => [$id => ['last_read_at' => $id == $userId ? now() : null]]);

            $thread->participants()->attach($participants);

            Message::create([
                'message_thread_id' => $thread->id,
                'sender_id'         => $userId,
                'body'              => $request->body,
            ]);

            return $thread;
        });

        return redirect()->route('pages.messages.show', $thread)
            ->with('success', 'Message sent.');
    }

    public function reply(Request $request, MessageThread $thread)
    {
        $userId = auth()->id();

        abort_unless(
            $thread->participants()->where('user_id', $userId)->exists(),
            403
        );

        $request->validate(['body' => 'required|string|max:10000']);

        Message::create([
            'message_thread_id' => $thread->id,
            'sender_id'         => $userId,
            'body'              => $request->body,
        ]);

        // Mark sender as read
        $thread->participants()->updateExistingPivot($userId, ['last_read_at' => now()]);

        return redirect()->route('pages.messages.show', $thread)
            ->with('success', 'Reply sent.');
    }

    public function unreadCount()
    {
        return response()->json(['count' => auth()->user()->unreadMessageCount()]);
    }

    public function searchJobs(Request $request)
    {
        $q = $request->get('q', '');
        $results = [];

        $opps = \App\Models\Opportunity::with('jobSiteCustomer')
            ->where(function ($query) use ($q) {
                $query->where('job_no', 'like', "%{$q}%")
                      ->orWhereHas('jobSiteCustomer', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
            })
            ->limit(6)
            ->get();

        foreach ($opps as $opp) {
            $name  = $opp->jobSiteCustomer?->name ?? ('Opportunity #' . $opp->id);
            $jobNo = $opp->job_no ? ' (' . $opp->job_no . ')' : '';
            $results[] = [
                'type'  => 'opportunity',
                'id'    => $opp->id,
                'label' => 'Opportunity: ' . $name . $jobNo,
            ];
        }

        $sales = \App\Models\Sale::with('opportunity.jobSiteCustomer')
            ->where(function ($query) use ($q) {
                $query->where('sale_number', 'like', "%{$q}%")
                      ->orWhereHas('opportunity.jobSiteCustomer', fn ($q2) => $q2->where('name', 'like', "%{$q}%"));
            })
            ->limit(6)
            ->get();

        foreach ($sales as $sale) {
            $num  = $sale->sale_number ?? $sale->id;
            $name = $sale->opportunity?->jobSiteCustomer?->name;
            $results[] = [
                'type'  => 'sale',
                'id'    => $sale->id,
                'label' => 'Sale #' . $num . ($name ? ' — ' . $name : ''),
            ];
        }

        return response()->json($results);
    }

    public function searchUsers(Request $request)
    {
        $q = $request->get('q', '');
        $users = User::where('id', '<>', auth()->id())
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
