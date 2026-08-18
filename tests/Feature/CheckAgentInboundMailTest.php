<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckAgentInboundMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('AGENT_INBOUND_API_KEY=test-agent-key');
        $_ENV['AGENT_INBOUND_API_KEY'] = 'test-agent-key';
        $_SERVER['AGENT_INBOUND_API_KEY'] = 'test-agent-key';
    }

    private function fakeGraphInbox(array $unread, array $messageDetails, int $webhookStatus = 200): void
    {
        Http::fake(function ($request) use ($unread, $messageDetails, $webhookStatus) {
            $url = $request->url();
            $method = $request->method();

            if (str_contains($url, 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'fake-token'], 200);
            }
            if ($method === 'GET' && str_contains($url, '/messages?')) {
                return Http::response(['value' => $unread], 200);
            }
            foreach ($messageDetails as $id => $detail) {
                if ($method === 'GET' && str_contains($url, "/messages/{$id}")) {
                    return Http::response($detail, 200);
                }
                if ($method === 'PATCH' && str_contains($url, "/messages/{$id}")) {
                    return Http::response([], 200);
                }
            }
            if (str_contains($url, '/api/agent/inbound-email')) {
                return Http::response(
                    $webhookStatus === 200 ? ['success' => true, 'task_id' => 1] : ['error' => 'rejected'],
                    $webhookStatus
                );
            }

            return Http::response(['unexpected_url' => $url], 404);
        });
    }

    public function test_no_unread_messages_does_nothing(): void
    {
        $this->fakeGraphInbox([], []);

        Artisan::call('agent:check-inbound-mail');

        $this->assertStringContainsString('No unread messages', Artisan::output());
    }

    public function test_forwards_message_and_excludes_inline_attachments(): void
    {
        $this->fakeGraphInbox(
            [['id' => 'msg-1', 'subject' => 'Photos', 'receivedDateTime' => now()->toIso8601String()]],
            [
                'msg-1' => [
                    'from' => ['emailAddress' => ['address' => 'foreman@rmflooring.ca']],
                    'subject' => 'Photos for 26-0001',
                    'body' => ['contentType' => 'text', 'content' => 'Attaching a photo.'],
                    'attachments' => [
                        ['@odata.type' => '#microsoft.graph.fileAttachment', 'name' => 'photo.jpg', 'isInline' => false, 'contentBytes' => base64_encode('bytes')],
                        ['@odata.type' => '#microsoft.graph.fileAttachment', 'name' => 'logo.png', 'isInline' => true, 'contentBytes' => base64_encode('logo')],
                    ],
                ],
            ],
        );

        Artisan::call('agent:check-inbound-mail');

        $this->assertStringContainsString('1 of 1 message(s) forwarded', Artisan::output());

        $webhookRequest = collect(Http::recorded())->first(fn ($pair) => str_contains($pair[0]->url(), 'inbound-email'));
        $this->assertNotNull($webhookRequest);

        $body = (string) $webhookRequest[0]->toPsrRequest()->getBody();
        $this->assertStringContainsString('foreman@rmflooring.ca', $body);
        $this->assertStringContainsString('photo.jpg', $body);
        $this->assertStringNotContainsString('logo.png', $body);

        $patchCall = collect(Http::recorded())->first(fn ($pair) => $pair[0]->method() === 'PATCH');
        $this->assertNotNull($patchCall, 'Message should be marked read after a successful forward.');
    }

    public function test_strips_html_body_to_plain_text(): void
    {
        $this->fakeGraphInbox(
            [['id' => 'msg-3', 'subject' => 'Hi', 'receivedDateTime' => now()->toIso8601String()]],
            [
                'msg-3' => [
                    'from' => ['emailAddress' => ['address' => 'foreman@rmflooring.ca']],
                    'subject' => 'Hi',
                    'body' => ['contentType' => 'html', 'content' => '<p>Hello <b>there</b></p>'],
                    'attachments' => [],
                ],
            ],
        );

        Artisan::call('agent:check-inbound-mail');

        $webhookRequest = collect(Http::recorded())->first(fn ($pair) => str_contains($pair[0]->url(), 'inbound-email'));
        $body = (string) $webhookRequest[0]->toPsrRequest()->getBody();
        $this->assertStringContainsString('Hello there', $body);
        $this->assertStringNotContainsString('<p>', $body);
    }

    public function test_html_table_cells_get_separated_not_mashed_together(): void
    {
        $this->fakeGraphInbox(
            [['id' => 'msg-5', 'subject' => 'Job info', 'receivedDateTime' => now()->toIso8601String()]],
            [
                'msg-5' => [
                    'from' => ['emailAddress' => ['address' => 'foreman@rmflooring.ca']],
                    'subject' => 'Job info',
                    'body' => ['contentType' => 'html', 'content' => '<table><tr><td>PM Contact</td><td>Andrew Bou-Antoun</td></tr></table>'],
                    'attachments' => [],
                ],
            ],
        );

        Artisan::call('agent:check-inbound-mail');

        $webhookRequest = collect(Http::recorded())->first(fn ($pair) => str_contains($pair[0]->url(), 'inbound-email'));
        $body = (string) $webhookRequest[0]->toPsrRequest()->getBody();
        $this->assertStringContainsString('PM Contact Andrew Bou-Antoun', $body);
        $this->assertStringNotContainsString('ContactAndrew', $body);
    }

    public function test_rejected_message_is_left_unread_for_retry(): void
    {
        $this->fakeGraphInbox(
            [['id' => 'msg-4', 'subject' => 'Spam', 'receivedDateTime' => now()->toIso8601String()]],
            [
                'msg-4' => [
                    'from' => ['emailAddress' => ['address' => 'blocked@gmail.com']],
                    'subject' => 'Spam',
                    'body' => ['contentType' => 'text', 'content' => 'hi'],
                    'attachments' => [],
                ],
            ],
            webhookStatus: 403,
        );

        Artisan::call('agent:check-inbound-mail');

        $this->assertStringContainsString('0 of 1 message(s) forwarded', Artisan::output());

        $patchCall = collect(Http::recorded())->first(fn ($pair) => $pair[0]->method() === 'PATCH');
        $this->assertNull($patchCall, 'A rejected message must stay unread so it is retried next run.');
    }
}
