<?php

namespace Tests\Feature;

use App\Services\GraphMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * getUnreadMessages()/getMessageMime()/markMessageRead() used to silently swallow a
 * failed Graph response (no ->successful() check) — a permission/access error looked
 * identical to "nothing found." Found 2026-08-17 while diagnosing a real 403 against
 * agent@rmflooring.ca. These assert the fix: a non-2xx response now throws instead of
 * returning an empty/default result.
 */
class GraphMailServiceErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token'], 200),
            'graph.microsoft.com/*' => Http::response(['error' => ['code' => 'ErrorAccessDenied', 'message' => 'Access is denied.']], 403),
        ]);
    }

    public function test_get_unread_messages_throws_on_failed_response(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/failed to list messages/');

        app(GraphMailService::class)->getUnreadMessages('agent@rmflooring.ca');
    }

    public function test_get_message_mime_throws_on_failed_response(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/failed to fetch MIME/');

        app(GraphMailService::class)->getMessageMime('agent@rmflooring.ca', 'msg-1');
    }

    public function test_mark_message_read_throws_on_failed_response(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/failed to mark message/');

        app(GraphMailService::class)->markMessageRead('agent@rmflooring.ca', 'msg-1');
    }
}
