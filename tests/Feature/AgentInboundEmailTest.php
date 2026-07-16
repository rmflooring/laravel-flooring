<?php

namespace Tests\Feature;

use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentInboundEmailTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-agent-key';

    protected function setUp(): void
    {
        parent::setUp();

        putenv('AGENT_INBOUND_API_KEY=' . self::API_KEY);
        $_ENV['AGENT_INBOUND_API_KEY'] = self::API_KEY;
        $_SERVER['AGENT_INBOUND_API_KEY'] = self::API_KEY;
        config(['services.anthropic.key' => 'test-anthropic-key']);

        AgentSetting::create([
            'allowed_sender_domains' => ['rmflooring.ca'],
            'allowed_sender_addresses' => [],
            'rate_limit_per_sender_per_hour' => 20,
        ]);

        // Graph mail calls (token + sendMail) — always succeed, not under test here.
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token'], 200),
            'graph.microsoft.com/*' => Http::response([], 202),
        ]);
    }

    private function fakeClaudeToolUse(string $toolName, array $input): void
    {
        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token'], 200),
            'graph.microsoft.com/*' => Http::response([], 202),
            'api.anthropic.com/*' => Http::response([
                'id' => 'msg_test',
                'stop_reason' => 'tool_use',
                'content' => [
                    ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => $toolName, 'input' => $input],
                ],
            ], 200),
        ]);
    }

    public function test_happy_path_attaches_images_to_resolved_opportunity(): void
    {
        $opportunity = Opportunity::create(['job_no' => '26-0001']);

        $this->fakeClaudeToolUse('attach_images', [
            'opportunity_id' => $opportunity->id,
            'category' => 'before',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'foreman@rmflooring.ca',
                'subject' => 'Photos for job 26-0001',
                'body' => 'Attaching a few photos from site.',
                'attachments' => [
                    UploadedFile::fake()->image('subfloor.jpg', 100, 100),
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $task = AgentTask::first();
        $this->assertNotNull($task);
        $this->assertSame($opportunity->id, $task->opportunity_id);
        $this->assertSame('completed', $task->status);

        $this->assertSame(1, OpportunityDocument::where('opportunity_id', $opportunity->id)->count());
        $doc = OpportunityDocument::where('opportunity_id', $opportunity->id)->first();
        $this->assertSame('media', $doc->category);
        $this->assertSame('before', $doc->label_text);
    }

    public function test_happy_path_attaches_document_to_resolved_opportunity(): void
    {
        $opportunity = Opportunity::create(['job_no' => '26-0002']);

        $this->fakeClaudeToolUse('attach_document', [
            'opportunity_id' => $opportunity->id,
            'attachment_index' => 0,
            'document_type' => 'scope_of_work',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'foreman@rmflooring.ca',
                'subject' => 'Scope of work for job 26-0002',
                'body' => 'Attaching the signed scope of work.',
                'attachments' => [
                    UploadedFile::fake()->create('scope.pdf', 100, 'application/pdf'),
                ],
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $task = AgentTask::first();
        $this->assertNotNull($task);
        $this->assertSame($opportunity->id, $task->opportunity_id);
        $this->assertSame('completed', $task->status);

        $this->assertSame(1, OpportunityDocument::where('opportunity_id', $opportunity->id)->count());
        $doc = OpportunityDocument::where('opportunity_id', $opportunity->id)->first();
        $this->assertSame('document', $doc->category);
        $this->assertSame('scope_of_work', $doc->label_text);
    }

    public function test_sender_not_in_allowlist_is_rejected_without_creating_task(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'someone@gmail.com',
                'body' => 'Hello',
            ]);

        $response->assertStatus(403);
        $this->assertSame(0, AgentTask::count());
    }

    public function test_no_job_number_in_email_triggers_clarification(): void
    {
        $this->fakeClaudeToolUse('request_clarification', [
            'question' => 'Which job are these photos for?',
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'foreman@rmflooring.ca',
                'subject' => 'Some photos',
                'body' => 'Here are some photos, not sure which job.',
            ]);

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertNull($task->opportunity_id);
        $this->assertSame('pending_clarification', $task->status);
        $this->assertSame(1, $task->messages()->count());
    }

    public function test_rate_limit_exceeded_is_rejected(): void
    {
        $settings = AgentSetting::current();
        $settings->rate_limit_per_sender_per_hour = 1;
        $settings->save();

        $this->fakeClaudeToolUse('no_actionable_intent', []);

        $headers = ['Authorization' => 'Bearer ' . self::API_KEY];
        $payload = ['from' => 'foreman@rmflooring.ca', 'body' => 'hi'];

        $this->withHeaders($headers)->post('/api/agent/inbound-email', $payload)->assertOk();

        $second = $this->withHeaders($headers)->post('/api/agent/inbound-email', $payload);
        $second->assertStatus(429);

        $this->assertSame(1, AgentTask::count());
    }
}
