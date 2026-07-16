<?php

namespace App\Services\Agent\Concerns;

use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Services\Agent\AgentToolValidationException;

/**
 * Shared validation/decoding helpers for agent tools that attach an
 * AgentTask::attachments entry to an opportunity's document storage
 * (AttachImagesService, AttachDocumentService).
 */
trait ValidatesAgentAttachments
{
    protected function assertOpportunityMatches(AgentTask $task, int $opportunityId): Opportunity
    {
        if ($task->opportunity_id === null || (int) $task->opportunity_id !== $opportunityId) {
            throw new AgentToolValidationException(
                'opportunity_id does not match the opportunity already resolved for this task.'
            );
        }

        $opportunity = Opportunity::find($opportunityId);
        if (! $opportunity) {
            throw new AgentToolValidationException("Opportunity {$opportunityId} not found.");
        }

        return $opportunity;
    }

    protected function decodeAttachmentBytes(array $attachment, int $maxBytes): string
    {
        $bytes = base64_decode($attachment['content_base64'], true);
        if ($bytes === false || strlen($bytes) === 0) {
            throw new AgentToolValidationException("Attachment \"{$attachment['original_name']}\" could not be decoded.");
        }
        if (strlen($bytes) > $maxBytes) {
            $maxMb = (int) ($maxBytes / (1024 * 1024));
            throw new AgentToolValidationException("Attachment \"{$attachment['original_name']}\" exceeds the {$maxMb}MB limit.");
        }

        return $bytes;
    }

    protected function storageFolderFor(Opportunity $opportunity): string
    {
        return 'opportunities/' . $opportunity->storageFolderName();
    }
}
