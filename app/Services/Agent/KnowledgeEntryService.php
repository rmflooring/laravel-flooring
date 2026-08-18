<?php

namespace App\Services\Agent;

use App\Models\KnowledgeEmbedding;
use App\Models\KnowledgeEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Admin-only create/update for knowledge_entries, including chunking + embedding.
 *
 * Fail-loud by design: embedBatch() is called BEFORE anything is written to the
 * database. If Voyage fails or rate-limits, the exception propagates straight up to
 * the controller — nothing is saved. We never want a knowledge_entries row sitting
 * there with no (or partial) embeddings, silently unsearchable.
 */
class KnowledgeEntryService
{
    public function __construct(private EmbeddingService $embeddings) {}

    /**
     * Simple paragraph-based chunking — split on blank lines, trim, drop empties.
     * No overlap between chunks. If we start seeing answers that miss context split
     * across a paragraph boundary, add a small overlap (repeat the last sentence or
     * two from one chunk into the start of the next) rather than something fancier.
     *
     * @return array<int, string>
     */
    public function chunk(string $content): array
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($content)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $p) => trim($p))
            ->filter(fn (string $p) => $p !== '')
            ->values()
            ->all();
    }

    public function create(array $data, User $creator): KnowledgeEntry
    {
        $chunks = $this->chunk($data['content']);
        if (empty($chunks)) {
            throw new \InvalidArgumentException('Content produced no chunks to embed.');
        }

        $vectors = $this->embeddings->embedBatch($chunks);

        return DB::transaction(function () use ($data, $creator, $chunks, $vectors) {
            $entry = KnowledgeEntry::create([
                'title' => $data['title'],
                'category' => $data['category'],
                'content' => $data['content'],
                'structured_data' => $data['structured_data'] ?? null,
                'visible_to_roles' => $data['visible_to_roles'],
                'created_by' => $creator->id,
            ]);

            $this->storeEmbeddings($entry, $chunks, $vectors);

            return $entry;
        });
    }

    public function update(KnowledgeEntry $entry, array $data): KnowledgeEntry
    {
        $chunks = $this->chunk($data['content']);
        if (empty($chunks)) {
            throw new \InvalidArgumentException('Content produced no chunks to embed.');
        }

        $vectors = $this->embeddings->embedBatch($chunks);

        return DB::transaction(function () use ($entry, $data, $chunks, $vectors) {
            $entry->update([
                'title' => $data['title'],
                'category' => $data['category'],
                'content' => $data['content'],
                'structured_data' => $data['structured_data'] ?? null,
                'visible_to_roles' => $data['visible_to_roles'],
            ]);

            // Re-embedding on every update, not a diff — simplest correct behavior for
            // v1, and cheap at this knowledge base's size.
            $entry->embeddings()->delete();
            $this->storeEmbeddings($entry, $chunks, $vectors);

            return $entry;
        });
    }

    private function storeEmbeddings(KnowledgeEntry $entry, array $chunks, array $vectors): void
    {
        foreach ($chunks as $i => $chunkText) {
            KnowledgeEmbedding::create([
                'knowledge_entry_id' => $entry->id,
                'chunk_text' => $chunkText,
                'embedding' => $vectors[$i],
            ]);
        }
    }
}
