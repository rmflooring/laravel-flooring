<?php

namespace Tests\Feature;

use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Employee;
use App\Models\OpportunityNote;
use App\Models\Rfm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentLogCommunicationCheckStatusTest extends TestCase
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
            // Trailing "done" turn — see AgentInboundEmailTest's identical helper for why.
            'api.anthropic.com/*' => Http::sequence([
                Http::response([
                    'id' => 'msg_test',
                    'stop_reason' => 'tool_use',
                    'content' => [
                        ['type' => 'tool_use', 'id' => 'toolu_1', 'name' => $toolName, 'input' => $input],
                    ],
                ], 200),
                Http::response([
                    'id' => 'msg_test_done',
                    'stop_reason' => 'end_turn',
                    'content' => [['type' => 'text', 'text' => '']],
                ], 200),
            ]),
        ]);
    }

    private function postAgentEmail(string $body, string $from = 'foreman@rmflooring.ca'): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => $from,
                'subject' => 'Re: your job',
                'body' => $body,
            ]);
    }

    public function test_log_communication_writes_a_categorized_note(): void
    {
        User::factory()->create(['email' => 'foreman@rmflooring.ca']);
        $opportunity = Opportunity::create(['job_no' => '26-0001']);

        $this->fakeClaudeToolUse('log_communication', [
            'opportunity_id' => $opportunity->id,
            'summary' => 'Adjuster confirmed the claim is approved.',
            'from' => 'Adjuster Jane Smith',
            'category' => 'insurance_communication',
        ]);

        $response = $this->postAgentEmail('Job 26-0001 — spoke with the adjuster, claim is approved.');
        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);
        $this->assertSame('log_communication', $task->task_type);

        $note = OpportunityNote::where('opportunity_id', $opportunity->id)->first();
        $this->assertNotNull($note);
        $this->assertSame('insurance_communication', $note->category);
        $this->assertSame('agent', $note->source);
        $this->assertStringContainsString('Adjuster Jane Smith', $note->body);
        $this->assertStringContainsString('claim is approved', $note->body);
    }

    public function test_log_communication_fails_gracefully_when_sender_has_no_fm_user_account(): void
    {
        // No User row for this address — requester_user_id will be null.
        $opportunity = Opportunity::create(['job_no' => '26-0002']);

        $this->fakeClaudeToolUse('log_communication', [
            'opportunity_id' => $opportunity->id,
            'summary' => 'test',
            'category' => 'other',
        ]);

        $response = $this->postAgentEmail('Job 26-0002 update.');
        $response->assertOk();

        $task = AgentTask::first();
        // Validation failure is not fatal — it's surfaced as a tool error, and since no
        // other tool call follows, the task ends up pending clarification (no completed
        // terminal was reached) rather than the job crashing.
        $this->assertNotSame('completed', $task->status);
        $this->assertSame(0, OpportunityNote::where('opportunity_id', $opportunity->id)->count());
    }

    public function test_check_status_summarizes_opportunity_and_completes_task(): void
    {
        User::factory()->create(['email' => 'foreman@rmflooring.ca']);
        $opportunity = Opportunity::create([
            'job_no' => '26-0003',
            'status' => 'In Progress',
            'requires_rfm' => true,
        ]);
        $estimator = Employee::create([
            'employee_number' => 'EMP-TEST-1',
            'first_name' => 'Test',
            'last_name' => 'Estimator',
        ]);
        Rfm::create([
            'opportunity_id' => $opportunity->id,
            'estimator_id' => $estimator->id,
            'status' => 'confirmed',
            'scheduled_at' => now()->addDays(2),
        ]);

        $this->fakeClaudeToolUse('check_status', ['opportunity_id' => $opportunity->id]);

        $response = $this->postAgentEmail('Job 26-0003 — any update on this?');
        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);
        $this->assertSame('check_status', $task->task_type);
        $this->assertStringContainsString('26-0003', $task->extracted_intent);
        $this->assertStringContainsString('In Progress', $task->extracted_intent);
        $this->assertStringContainsString('Confirmed', $task->extracted_intent);
    }
}
