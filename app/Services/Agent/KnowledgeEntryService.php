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
 * database. If OpenAI fails or rate-limits, the exception propagates straight up to
 * the controller — nothing is saved. We never want a knowledge_entries row sitting
 * there with no (or partial) embeddings, silently unsearchable.
 */
class KnowledgeEntryService
{
    /**
     * Found 2026-08-19: a real uploaded PDF pricelist produced an 11KB single chunk
     * because PDF text extraction doesn't reliably insert blank lines between sections
     * (a table of contents, or a dense price table, can extract as one long unbroken
     * block) — paragraph-splitting alone has no upper bound on chunk size in that case.
     * A chunk that large embeds as one diluted vector spanning dozens of unrelated
     * products, which both pollutes results for unrelated queries and, worse, can
     * outscore the actual small/focused chunk that has the real answer (confirmed: a
     * "does Centura have X" query scored the whole table-of-contents chunk above the
     * specific pricing-table chunk that named X, pushing the right answer below
     * TOP_K). 1500 chars keeps a chunk roughly paragraph/table-section sized without
     * being so small it fragments a single product's SKU/price rows.
     */
    private const MAX_CHUNK_CHARS = 1500;

    public function __construct(private EmbeddingService $embeddings) {}

    /**
     * Paragraph-based chunking — split on blank lines, trim, drop empties — with a
     * size cap: any resulting chunk still over MAX_CHUNK_CHARS (source text with long
     * blank-line-free runs, e.g. an extracted PDF table of contents or price table)
     * gets further split by line, grouping consecutive lines up to the cap so no line
     * (a product/SKU row) is ever split mid-line. No overlap between chunks. If we
     * start seeing answers that miss context split across a paragraph boundary, add a
     * small overlap (repeat the last sentence or two from one chunk into the start of
     * the next) rather than something fancier.
     *
     * @return array<int, string>
     */
    public function chunk(string $content): array
    {
        $paragraphs = preg_split('/\n\s*\n/', trim($content)) ?: [];

        return collect($paragraphs)
            ->map(fn (string $p) => trim($p))
            ->filter(fn (string $p) => $p !== '')
            ->flatMap(fn (string $p) => $this->splitOversized($p))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function splitOversized(string $paragraph): array
    {
        if (mb_strlen($paragraph) <= self::MAX_CHUNK_CHARS) {
            return [$paragraph];
        }

        $lines = explode("\n", $paragraph);
        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            $candidate = $current === '' ? $line : $current . "\n" . $line;

            if (mb_strlen($candidate) > self::MAX_CHUNK_CHARS && $current !== '') {
                $chunks[] = $current;
                $current = $line;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
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
