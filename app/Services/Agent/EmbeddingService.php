<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over OpenAI's embeddings API — used here because Claude's own Messages
 * API has no embeddings endpoint, and this app already has OpenAI billing/keys set up
 * elsewhere. Same raw Http::post() convention as ClaudeAgentService (no SDK).
 */
class EmbeddingService
{
    private const API_URL = 'https://api.openai.com/v1/embeddings';

    private const MODEL = 'text-embedding-3-small';

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
        $key = config('services.openai.key');
        if (! $key) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
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
            Log::error('[Agent] OpenAI embeddings request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Embedding request failed: HTTP ' . $response->status());
        }

        $data = $response->json('data', []);
        if (! is_array($data) || count($data) !== count($texts)) {
            Log::error('[Agent] OpenAI embeddings response shape mismatch', [
                'expected' => count($texts),
                'got' => is_array($data) ? count($data) : 0,
            ]);
            throw new \RuntimeException('Embedding response did not match the number of inputs sent.');
        }

        // Sort by the response's own `index` (defensive — OpenAI returns results in
        // input order in practice, but this guarantees it rather than assuming it).
        usort($data, fn (array $a, array $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

        return array_map(function (array $d) {
            if (! isset($d['embedding']) || ! is_array($d['embedding'])) {
                throw new \RuntimeException('Embedding response was missing an embedding vector.');
            }

            return $d['embedding'];
        }, $data);
    }
}
