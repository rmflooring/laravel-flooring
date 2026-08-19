<?php

namespace App\Services\Agent;

use App\Models\KnowledgeEmbedding;
use App\Models\KnowledgeEntry;
use App\Models\User;

/**
 * Executes the `search_knowledge_base` chat tool. Role-filters entries BEFORE scoring —
 * a restricted entry's chunks are never even loaded/compared, not just excluded from
 * the final result — then ranks by cosine similarity against the query embedding.
 */
class KnowledgeSearchService
{
    /** Bumped from 5 (2026-08-19) alongside the chunk-size cap in KnowledgeEntryService
     *  — smaller, more focused chunks mean more of them per entry, so a slightly wider
     *  net costs little and adds margin against a correct-but-not-top-ranked chunk
     *  falling just outside the cutoff (the exact failure that prompted this). */
    private const TOP_K = 8;

    public function __construct(
        private EmbeddingService $embeddings,
        private KnowledgeAccessGate $gate,
    ) {}

    /**
     * @return array<int, array{knowledge_entry_id: int, title: string, category: string, chunk_text: string, score: float}>
     */
    public function search(User $user, string $query): array
    {
        $roles = $this->gate->roleFilterFor($user);

        $entriesQuery = KnowledgeEntry::query();
        if ($roles !== null) {
            if (empty($roles)) {
                return [];
            }
            $entriesQuery->where(function ($q) use ($roles) {
                foreach ($roles as $role) {
                    $q->orWhereJsonContains('visible_to_roles', $role);
                }
            });
        }

        $entryIds = $entriesQuery->pluck('id');
        if ($entryIds->isEmpty()) {
            return [];
        }

        $chunks = KnowledgeEmbedding::whereIn('knowledge_entry_id', $entryIds)
            ->with('entry')
            ->get();

        if ($chunks->isEmpty()) {
            return [];
        }

        $queryVector = $this->embeddings->embed($query);

        // NOTE: linear in-PHP scan over every chunk. Fine at our current knowledge base
        // size (a handful of entries, low tens of chunks). If the entry count grows
        // large enough that per-query scoring becomes slow, move to a proper vector
        // index (or MariaDB's native VECTOR type once we're on 11.7+) instead of
        // optimizing this loop further.
        $scored = $chunks
            ->map(fn (KnowledgeEmbedding $chunk) => [
                'chunk' => $chunk,
                'score' => $this->cosineSimilarity($queryVector, $chunk->embedding),
            ])
            ->sortByDesc('score')
            ->take(self::TOP_K);

        return $scored
            ->map(fn (array $s) => [
                'knowledge_entry_id' => $s['chunk']->entry->id,
                'title' => $s['chunk']->entry->title,
                'category' => $s['chunk']->entry->category,
                'chunk_text' => $s['chunk']->chunk_text,
                'score' => round($s['score'], 4),
            ])
            ->values()
            ->all();
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $v) {
            $dot += $v * ($b[$i] ?? 0);
            $normA += $v * $v;
        }
        foreach ($b as $v) {
            $normB += $v * $v;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
