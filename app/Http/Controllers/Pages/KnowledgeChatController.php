<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\AgentQuery;
use App\Services\Agent\RunKnowledgeChatQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KnowledgeChatController extends Controller
{
    /**
     * A blank new chat, with the user's chat list alongside.
     */
    public function index(Request $request): View
    {
        return $this->chatView($request, null);
    }

    public function show(Request $request, AgentConversation $agentConversation): View
    {
        $this->authorizeOwner($request, $agentConversation);

        return $this->chatView($request, $agentConversation);
    }

    public function ask(Request $request, RunKnowledgeChatQuery $runner): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer', 'exists:agent_conversations,id'],
        ]);

        if (! empty($validated['conversation_id'])) {
            $conversation = AgentConversation::findOrFail($validated['conversation_id']);
            $this->authorizeOwner($request, $conversation);
        } else {
            $conversation = AgentConversation::create([
                'user_id' => $request->user()->id,
                'title' => Str::limit(Str::squish($validated['question']), 60),
            ]);
        }

        // Asking in an archived chat brings it back to the main list
        if ($conversation->archived_at) {
            $conversation->update(['archived_at' => null]);
        }

        try {
            $query = $runner->run($request->user(), $validated['question'], $conversation);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Something went wrong answering that — please try again.',
                'conversation' => $this->conversationSummary($conversation),
            ], 500);
        }

        return response()->json([
            'id' => $query->id,
            'question' => $query->question,
            'answer' => $query->answer,
            'tools_used' => $query->tools_used,
            'source_refs' => $query->source_refs,
            'conversation' => $this->conversationSummary($conversation->fresh()),
        ]);
    }

    public function archive(Request $request, AgentConversation $agentConversation): JsonResponse
    {
        $this->authorizeOwner($request, $agentConversation);

        $agentConversation->update(['archived_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function unarchive(Request $request, AgentConversation $agentConversation): JsonResponse
    {
        $this->authorizeOwner($request, $agentConversation);

        $agentConversation->update(['archived_at' => null]);

        return response()->json(['ok' => true]);
    }

    public function feedback(Request $request, AgentQuery $agentQuery): JsonResponse
    {
        if ($agentQuery->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'feedback' => ['required', 'in:up,down'],
        ]);

        $agentQuery->update(['feedback' => $validated['feedback']]);

        return response()->json(['ok' => true]);
    }

    private function chatView(Request $request, ?AgentConversation $active): View
    {
        // Viewing an archived chat opens the Archived list so it shows as selected
        $showArchived = $request->boolean('archived') || (bool) $active?->archived_at;

        $conversations = AgentConversation::where('user_id', $request->user()->id)
            ->archived($showArchived)
            ->latest('updated_at')
            ->limit(100)
            ->get()
            ->map(fn (AgentConversation $c) => $this->conversationSummary($c));

        return view('pages.knowledge-chat.index', [
            'conversations' => $conversations,
            'showArchived' => $showArchived,
            'active' => $active ? $this->conversationSummary($active) : null,
            'queries' => $active ? $active->queries()->get() : collect(),
        ]);
    }

    private function conversationSummary(AgentConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title ?: 'Untitled chat',
            'archived' => (bool) $conversation->archived_at,
            'updated' => $conversation->updated_at?->diffForHumans(short: true),
            'url' => route('pages.knowledge-chat.show', $conversation),
        ];
    }

    private function authorizeOwner(Request $request, AgentConversation $conversation): void
    {
        if ($conversation->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
