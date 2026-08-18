<?php

namespace App\Services\Agent;

use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\OpportunityNote;

/**
 * Reverses a completed agent task where feasible, using the before-state snapshot(s)
 * ProcessAgentTask accumulates onto AgentTask::undo_data as each write action executes.
 * Dashboard-only for v1 (see AgentTaskController) — not exposed as a Claude tool, since
 * letting an LLM's read of an ambiguous email autonomously reverse completed work is a
 * materially different risk than the forward-only actions in AgentToolRegistry.
 *
 * A task can now complete multiple actions (e.g. create_opportunity + attach_images +
 * update_opportunity, all from one email) — undo_data is a list of per-action snapshots,
 * not a single one. Undo reverses whichever entries are undoable and reports on any that
 * aren't, rather than all-or-nothing refusing the whole task.
 *
 * create_opportunity is deliberately not undoable: deleting an Opportunity cascades
 * through opportunity_notes/documents/purchase_orders/shares, but sales/estimates are
 * nullOnDelete — if either was created on that opportunity since, undoing the create
 * would silently orphan them rather than cleanly reverse anything. Too high a blast
 * radius for a snapshot-based undo; stays human-only, same as other risky writes.
 */
class UndoLastActionService
{
    public const UNDOABLE_TYPES = ['attach_images', 'attach_document', 'log_communication', 'update_opportunity'];

    public function execute(AgentTask $task): string
    {
        if ($task->status !== 'completed') {
            throw new AgentToolValidationException('Only completed tasks can be undone.');
        }

        if ($task->undone_at) {
            throw new AgentToolValidationException('This task has already been undone.');
        }

        $entries = $this->normalizeUndoData($task->undo_data);
        if (empty($entries)) {
            throw new AgentToolValidationException('No undo data was captured for this task.');
        }

        $undoable = array_values(array_filter($entries, fn (array $e) => in_array($e['type'] ?? null, self::UNDOABLE_TYPES, true)));
        $skippedTypes = array_values(array_unique(array_map(
            fn (array $e) => $e['type'] ?? 'unknown',
            array_filter($entries, fn (array $e) => ! in_array($e['type'] ?? null, self::UNDOABLE_TYPES, true)),
        )));

        if (empty($undoable)) {
            throw new AgentToolValidationException("Undo isn't available for this task's action(s).");
        }

        $messages = [];
        foreach ($undoable as $data) {
            $messages[] = match ($data['type']) {
                'attach_images', 'attach_document' => $this->undoAttachments($data),
                'log_communication' => $this->undoLogCommunication($data),
                'update_opportunity' => $this->undoUpdateOpportunity($data),
            };
        }

        if (! empty($skippedTypes)) {
            $messages[] = 'Note: this task also included ' . implode(', ', $skippedTypes)
                . ' action(s), which can\'t be automatically undone.';
        }

        $task->update(['undone_at' => now()]);

        return implode(' ', $messages);
    }

    /**
     * Backward-compatible with tasks created before multi-action support: those stored a
     * single flat undo_data object (`{type: ..., ...}`); newer tasks store a list of them
     * (`[{type: ...}, ...]`). Normalizes both shapes into a list.
     *
     * @return array<int, array{type: string}>
     */
    private function normalizeUndoData(?array $undoData): array
    {
        if (! $undoData) {
            return [];
        }

        return isset($undoData['type']) ? [$undoData] : $undoData;
    }

    public function hasUndoableAction(AgentTask $task): bool
    {
        foreach ($this->normalizeUndoData($task->undo_data) as $entry) {
            if (in_array($entry['type'] ?? null, self::UNDOABLE_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    private function undoAttachments(array $data): string
    {
        $ids = $data['document_ids'] ?? [];
        $count = OpportunityDocument::whereIn('id', $ids)->get()->each->delete()->count();

        return $count > 0
            ? "Archived {$count} document(s) that were attached."
            : 'The attached document(s) were already removed.';
    }

    private function undoLogCommunication(array $data): string
    {
        $note = OpportunityNote::find($data['note_id'] ?? null);
        if (! $note) {
            return 'The logged note was already removed.';
        }

        $note->delete();

        return 'Removed the logged communication note.';
    }

    private function undoUpdateOpportunity(array $data): string
    {
        $opportunity = Opportunity::find($data['opportunity_id'] ?? null);
        if (! $opportunity) {
            throw new AgentToolValidationException('The opportunity no longer exists.');
        }

        $opportunity->update($data['previous_values'] ?? []);

        return 'Reverted the opportunity fields to their previous values.';
    }
}
