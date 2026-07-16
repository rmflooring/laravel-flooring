<?php

namespace App\Services\Agent;

use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Services\Agent\Concerns\ValidatesAgentAttachments;
use App\Services\DocumentStorageService;
use Illuminate\Support\Facades\Storage;

/**
 * Executes the `attach_images` Claude tool: stores email image attachments already
 * captured on AgentTask::attachments as OpportunityDocument rows (category=media),
 * reusing the same storage conventions as the manual mobile photo upload flow.
 */
class AttachImagesService
{
    use ValidatesAgentAttachments;

    /** Categories Claude must choose from — kept as a small allowlist rather than
     *  freetext so the resulting label is consistent and auditable. */
    public const CATEGORIES = ['before', 'after', 'moisture', 'damage', 'completion', 'other'];

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const MAX_BYTES = 20 * 1024 * 1024; // 20MB, matches PhotoGalleryController::uploadPhotos

    /**
     * @param  int[]  $attachmentIndices  Indices into AgentTask::attachments to attach.
     *                                    Empty array = attach every image-type attachment on the task.
     * @return array{document_ids: int[], count: int}
     */
    public function execute(
        AgentTask $task,
        int $opportunityId,
        array $attachmentIndices,
        ?string $label,
        string $category,
    ): array {
        if (! in_array($category, self::CATEGORIES, true)) {
            throw new AgentToolValidationException(
                'Invalid category "' . $category . '". Must be one of: ' . implode(', ', self::CATEGORIES)
            );
        }

        $opportunity = $this->assertOpportunityMatches($task, $opportunityId);

        $attachments = $task->attachments ?? [];
        $imageAttachments = $this->selectAttachments($attachments, $attachmentIndices);

        if (empty($imageAttachments)) {
            throw new AgentToolValidationException('No image attachments found to attach.');
        }

        $disk = DocumentStorageService::disk();
        $folder = $this->storageFolderFor($opportunity);
        $documentIds = [];

        foreach ($imageAttachments as $attachment) {
            $documentIds[] = $this->storeOne($opportunity, $disk, $folder, $attachment, $label, $category, $task);
        }

        return [
            'document_ids' => $documentIds,
            'count' => count($documentIds),
        ];
    }

    /**
     * @param  array<int, array{original_name: string, mime_type: string, size: int, content_base64: string}>  $attachments
     * @param  int[]  $indices
     * @return array<int, array>
     */
    private function selectAttachments(array $attachments, array $indices): array
    {
        $isImage = fn (array $a): bool => in_array($a['mime_type'] ?? '', self::ALLOWED_MIME_TYPES, true);

        if (empty($indices)) {
            return array_values(array_filter($attachments, $isImage));
        }

        $selected = [];
        foreach ($indices as $index) {
            if (! array_key_exists($index, $attachments)) {
                throw new AgentToolValidationException("Attachment index {$index} does not exist on this task.");
            }
            if (! $isImage($attachments[$index])) {
                throw new AgentToolValidationException("Attachment index {$index} is not a supported image type.");
            }
            $selected[] = $attachments[$index];
        }

        return $selected;
    }

    private function storeOne(
        Opportunity $opportunity,
        string $disk,
        string $folder,
        array $attachment,
        ?string $label,
        string $category,
        AgentTask $task,
    ): int {
        $bytes = $this->decodeAttachmentBytes($attachment, self::MAX_BYTES);

        $extension = pathinfo($attachment['original_name'], PATHINFO_EXTENSION) ?: 'jpg';
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
            'category' => 'media',
            'label_text' => $category,
            'description' => $label,
            'created_by' => $task->requester_user_id,
            'updated_by' => $task->requester_user_id,
        ]);
        // OpportunityDocument::booted() already queues the OneDrive mirror on create.

        return $document->id;
    }
}
