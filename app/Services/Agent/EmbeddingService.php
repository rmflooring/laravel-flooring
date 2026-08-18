<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over Voyage AI's embeddings API — Anthropic's recommended embedding
 * partner, used here because Claude's own Messages API has no embeddings endpoint.
 * Same raw Http::post() convention as ClaudeAgentService (no SDK).
 */
class EmbeddingService
{
    private const API_URL = 'https://api.voyageai.com/v1/embeddings';

    private const MODEL = 'voyage-3-lite';

    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     *
     * Throws on any failure (missing key, non-2xx response, malformed response) —
     * callers must not catch this and silently proceed. KnowledgeEntryService relies
     * on that: an entry is only ever saved if embedding it succeeded.
     */
    public function embedBatch(array $texts): array
    {
        $key = config('services.voyage.key');
        if (! $key) {
            throw new \RuntimeException('VOYAGE_API_KEY is not configured.');
        }

        if (empty($texts)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$key}",
            'content-type' => 'application/json',
        ])->post(self::API_URL, [
            'input' => array_values($texts),
            'model' => self::MODEL,
        ]);

        if (! $response->successful()) {
            Log::error('[Agent] Voyage embeddings request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Embedding request failed: HTTP ' . $response->status());
        }

        $data = $response->json('data', []);
        if (! is_array($data) || count($data) !== count($texts)) {
            Log::error('[Agent] Voyage embeddings response shape mismatch', [
                'expected' => count($texts),
                'got' => is_array($data) ? count($data) : 0,
            ]);
            throw new \RuntimeException('Embedding response did not match the number of inputs sent.');
        }

        return array_map(function (array $d) {
            if (! isset($d['embedding']) || ! is_array($d['embedding'])) {
                throw new \RuntimeException('Embedding response was missing an embedding vector.');
            }

            return $d['embedding'];
        }, $data);
    }
}
