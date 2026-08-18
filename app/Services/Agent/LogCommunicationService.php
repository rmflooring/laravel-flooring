<?php

namespace App\Services\Agent;

use App\Models\AgentTask;
use App\Models\OpportunityNote;
use App\Services\Agent\Concerns\ValidatesAgentAttachments;

/**
 * Executes the `log_communication` Claude tool. Writes a summary of an email/
 * correspondence thread onto the resolved opportunity's activity log
 * (OpportunityNote), tagged with a category and, if known, who the
 * correspondence was from.
 */
class LogCommunicationService
{
    use ValidatesAgentAttachments;

    public const CATEGORIES = [
        'client_communication',
        'insurance_communication',
        'vendor_communication',
        'internal_note',
        'other',
    ];

    /**
     * @return array{note_id: int, opportunity_id: int, category: string}
     */
    public function execute(
        AgentTask $task,
        int $opportunityId,
        string $summary,
        ?string $from,
        string $category,
    ): array {
        $opportunity = $this->assertOpportunityMatches($task, $opportunityId);

        $summary = trim($summary);
        if ($summary === '') {
            throw new AgentToolValidationException('summary is required.');
        }

        if (! in_array($category, self::CATEGORIES, true)) {
            throw new AgentToolValidationException("Invalid category \"{$category}\".");
        }

        // opportunity_notes.user_id is a required FK — reuse the requester (the FM staff
        // member whose forward landed in the agent inbox) rather than inventing a system
        // user. This is null only when the sender's address didn't match any FM user.
        if (! $task->requester_user_id) {
            throw new AgentToolValidationException(
                "Cannot log communication — the requester email ({$task->requester_email}) does not match a Floor Manager user account."
            );
        }

        $note = OpportunityNote::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => $task->requester_user_id,
            'body' => $from ? "From: {$from}\n\n{$summary}" : $summary,
            'category' => $category,
            'source' => 'agent',
        ]);

        return [
            'note_id' => $note->id,
            'opportunity_id' => $opportunity->id,
            'category' => $category,
        ];
    }
}
