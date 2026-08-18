<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\AgentQuery;
use App\Services\Agent\RunKnowledgeChatQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeChatController extends Controller
{
    public function index(Request $request): View
    {
        $recent = AgentQuery::where('user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('pages.knowledge-chat.index', ['recent' => $recent]);
    }

    public function ask(Request $request, RunKnowledgeChatQuery $runner): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $query = $runner->run($request->user(), $validated['question']);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Something went wrong answering that — please try again.',
            ], 500);
        }

        return response()->json([
            'id' => $query->id,
            'question' => $query->question,
            'answer' => $query->answer,
            'tools_used' => $query->tools_used,
            'source_refs' => $query->source_refs,
        ]);
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
}
