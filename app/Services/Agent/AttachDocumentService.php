<?php

namespace App\Services\Agent;

use App\Models\AgentTask;
use App\Models\OpportunityDocument;
use App\Services\Agent\Concerns\ValidatesAgentAttachments;
use App\Services\DocumentStorageService;
use Illuminate\Support\Facades\Storage;

/**
 * Executes the `attach_document` Claude tool: stores a single email document
 * attachment already captured on AgentTask::attachments as an OpportunityDocument
 * row (category=document), reusing the same storage conventions as attach_images.
 */
class AttachDocumentService
{
    use ValidatesAgentAttachments;

    /** document_type Claude must choose from — kept as a small allowlist rather
     *  than freetext so the resulting label is consistent and auditable. */
    public const DOCUMENT_TYPES = [
        'scope_of_work',
        'contract',
        'insurance_certificate',
        'permit',
        'inspection_report',
        'other',
    ];

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private const MAX_BYTES = 20 * 1024 * 1024; // 20MB, matches AttachImagesService

    /**
     * @return array{document_id: int}
     */
    public function execute(
        AgentTask $task,
        int $opportunityId,
        int $attachmentIndex,
        ?string $label,
        string $documentType,
    ): array {
        if (! in_array($documentType, self::DOCUMENT_TYPES, true)) {
            throw new AgentToolValidationException(
                'Invalid document_type "' . $documentType . '". Must be one of: ' . implode(', ', self::DOCUMENT_TYPES)
            );
        }

        $opportunity = $this->assertOpportunityMatches($task, $opportunityId);

        $attachments = $task->attachments ?? [];
        if (! array_key_exists($attachmentIndex, $attachments)) {
            throw new AgentToolValidationException("Attachment index {$attachmentIndex} does not exist on this task.");
        }

        $attachment = $attachments[$attachmentIndex];
        if (! in_array($attachment['mime_type'] ?? '', self::ALLOWED_MIME_TYPES, true)) {
            throw new AgentToolValidationException("Attachment index {$attachmentIndex} is not a supported document type.");
        }

        $bytes = $this->decodeAttachmentBytes($attachment, self::MAX_BYTES);

        $disk = DocumentStorageService::disk();
        $folder = $this->storageFolderFor($opportunity);
        $extension = pathinfo($attachment['original_name'], PATHINFO_EXTENSION) ?: 'pdf';
        $storedName = uniqid('agent_', true) . '.' . $extension;
        $path = $folder . '/' . $storedName;

        Storage::disk($disk)->put($path, $bytes);

        $document = OpportunityDocument::create([
            'opportunity_id' => $opportunity->id,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $attachment['original_name'],
            'stored_name' => $storedName,
            'mime_type' => $attachment['mime_type'],
            'extension' => $extension,
            'size_bytes' => strlen($bytes),
            'category' => 'document',
            'label_text' => $documentType,
            'description' => $label,
            'created_by' => $task->requester_user_id,
            'updated_by' => $task->requester_user_id,
        ]);
        // OpportunityDocument::booted() already queues the OneDrive mirror on create.

        return ['document_id' => $document->id];
    }
}
