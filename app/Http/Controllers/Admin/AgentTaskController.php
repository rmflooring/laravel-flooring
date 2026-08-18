<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAgentTask;
use App\Models\AgentMessage;
use App\Models\AgentTask;
use App\Services\Agent\AgentToolValidationException;
use App\Services\Agent\UndoLastActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentTaskController extends Controller
{
    public const STATUSES = ['queued', 'pending_clarification', 'pending_confirmation', 'completed', 'failed', 'ignored'];
    public const TASK_TYPES = [
        'create_opportunity', 'update_opportunity', 'attach_images', 'attach_document',
        'log_communication', 'check_status', 'no_actionable_intent', 'other',
    ];
    public const SOURCES = ['email', 'chat'];

    public function index(Request $request): View
    {
        $query = AgentTask::with(['opportunity', 'requester'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('task_type')) {
            $query->where('task_type', $request->task_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $tasks = $query->paginate(25)->withQueryString();

        return view('admin.agent.tasks.index', [
            'tasks' => $tasks,
            'statuses' => self::STATUSES,
            'taskTypes' => self::TASK_TYPES,
            'sources' => self::SOURCES,
        ]);
    }

    public function show(AgentTask $agentTask, UndoLastActionService $undo): View
    {
        $agentTask->load(['messages', 'opportunity', 'requester', 'notifications']);

        return view('admin.agent.tasks.show', [
            'task' => $agentTask,
            'canUndo' => $undo->hasUndoableAction($agentTask),
        ]);
    }

    public function reply(Request $request, AgentTask $agentTask): RedirectResponse
    {
        if ($agentTask->status !== 'pending_clarification') {
            return back()->with('error', 'This task is not awaiting clarification.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        AgentMessage::create([
            'task_id' => $agentTask->id,
            'sender' => 'user',
            'body' => $validated['body'],
        ]);

        $agentTask->update(['status' => 'queued']);

        ProcessAgentTask::dispatch($agentTask->id);

        return back()->with('status', 'Reply sent — the agent will pick this back up shortly.');
    }

    public function undo(AgentTask $agentTask, UndoLastActionService $undo): RedirectResponse
    {
        try {
            $message = $undo->execute($agentTask);
        } catch (AgentToolValidationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $message);
    }
}
