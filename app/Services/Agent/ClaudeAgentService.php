<?php

namespace App\Services\Agent;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over the Claude Messages API (tool-use). No Anthropic PHP SDK
 * exists, so this follows the same raw-HTTP convention GraphMailService uses
 * for Microsoft Graph.
 */
class ClaudeAgentService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const MODEL = 'claude-opus-4-8';

    private const MAX_TOKENS = 4096;

    /**
     * @param  array  $messages  Messages API `messages` array (role/content pairs)
     * @param  array  $tools     Tool schema array
     * @return array  Decoded Messages API response
     */
    public function sendWithTools(array $messages, array $tools, string $system): array
    {
        $key = config('services.anthropic.key');
        if (! $key) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post(self::API_URL, [
            'model' => self::MODEL,
            'max_tokens' => self::MAX_TOKENS,
            'system' => $system,
            'messages' => $messages,
            'tools' => $tools,
            'thinking' => ['type' => 'adaptive'],
            'output_config' => ['effort' => 'medium'],
        ]);

        if (! $response->successful()) {
            Log::error('[Agent] Claude API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Claude API request failed: ' . $response->status());
        }

        return $response->json();
    }
}
