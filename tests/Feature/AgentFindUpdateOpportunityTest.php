<?php

namespace Tests\Feature;

use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\ProjectManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentFindUpdateOpportunityTest extends TestCase
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

    /** @param array<int, array{tool: string, input: array}> $turns */
    private function fakeClaudeTurns(array $turns): void
    {
        $responses = array_map(fn (array $turn) => Http::response([
            'id' => 'msg_test',
            'stop_reason' => 'tool_use',
            'content' => [
                ['type' => 'tool_use', 'id' => 'toolu_' . uniqid(), 'name' => $turn['tool'], 'input' => $turn['input']],
            ],
        ], 200), $turns);

        Http::fake([
            'login.microsoftonline.com/*' => Http::response(['access_token' => 'fake-token'], 200),
            'graph.microsoft.com/*' => Http::response([], 202),
            'api.anthropic.com/*' => Http::sequence($responses),
        ]);
    }

    private function postAgentEmail(string $body, string $subject = 'Re: your job'): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'foreman@rmflooring.ca',
                'subject' => $subject,
                'body' => $body,
            ]);
    }

    public function test_find_opportunity_resolves_unambiguous_match_and_sets_opportunity_id(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);
        $jobSite = Customer::create([
            'name' => 'Jane Homeowner',
            'address' => '123 Maple Street',
            'city' => 'Coquitlam',
            'claim_number' => 'CLM-99887',
            'parent_id' => $parent->id,
        ]);
        $opportunity = Opportunity::create([
            'parent_customer_id' => $parent->id,
            'job_site_customer_id' => $jobSite->id,
        ]);

        $this->fakeClaudeTurns([
            ['tool' => 'find_opportunity', 'input' => ['claim_number' => 'CLM-99887', 'client_name' => 'Jane Homeowner']],
            ['tool' => 'no_actionable_intent', 'input' => []],
        ]);

        $response = $this->postAgentEmail('Following up on claim CLM-99887 for Jane Homeowner at 123 Maple Street.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame($opportunity->id, $task->opportunity_id);
        $this->assertTrue($task->messages()->where('body', 'like', '%find_opportunity%')->exists());
    }

    public function test_find_opportunity_ambiguous_match_stays_unresolved(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);

        foreach (['123 Maple Street', '125 Maple Street'] as $address) {
            $jobSite = Customer::create([
                'name' => 'John Smith',
                'address' => $address,
                'city' => 'Coquitlam',
                'parent_id' => $parent->id,
            ]);
            Opportunity::create([
                'parent_customer_id' => $parent->id,
                'job_site_customer_id' => $jobSite->id,
            ]);
        }

        $this->fakeClaudeTurns([
            ['tool' => 'find_opportunity', 'input' => ['client_name' => 'John Smith']],
            ['tool' => 'request_clarification', 'input' => ['question' => 'Which John Smith job is this about?']],
        ]);

        $response = $this->postAgentEmail('Question about the John Smith job.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertNull($task->opportunity_id);
        $this->assertSame('pending_clarification', $task->status);
    }

    public function test_update_opportunity_sets_requires_rfm_and_project_manager(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);
        $pm = ProjectManager::create(['customer_id' => $parent->id, 'name' => 'Sam Rivera']);
        $opportunity = Opportunity::create(['job_no' => '26-0010', 'parent_customer_id' => $parent->id]);

        $this->fakeClaudeTurns([
            ['tool' => 'update_opportunity', 'input' => [
                'opportunity_id' => $opportunity->id,
                'requires_rfm' => true,
                'project_manager_name' => 'Sam Rivera',
            ]],
        ]);

        $response = $this->postAgentEmail('For job 26-0010: this needs an RFM, and please set the PM to Sam Rivera.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);
        $this->assertSame('update_opportunity', $task->task_type);

        $opportunity->refresh();
        $this->assertTrue((bool) $opportunity->requires_rfm);
        $this->assertSame($pm->id, $opportunity->project_manager_id);
    }

    public function test_update_opportunity_with_unresolvable_project_manager_name_triggers_clarification(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);
        $opportunity = Opportunity::create(['job_no' => '26-0011', 'parent_customer_id' => $parent->id]);

        $this->fakeClaudeTurns([
            ['tool' => 'update_opportunity', 'input' => [
                'opportunity_id' => $opportunity->id,
                'project_manager_name' => 'Nonexistent Person',
            ]],
            ['tool' => 'request_clarification', 'input' => ['question' => 'Who is the project manager?']],
        ]);

        $response = $this->postAgentEmail('For job 26-0011: please set the PM to Nonexistent Person.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('pending_clarification', $task->status);

        $opportunity->refresh();
        $this->assertNull($opportunity->project_manager_id);
    }

    public function test_update_opportunity_rejects_opportunity_id_not_matching_resolved_task(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);
        $resolved = Opportunity::create(['job_no' => '26-0012', 'parent_customer_id' => $parent->id]);
        $other = Opportunity::create(['job_no' => '26-0013', 'parent_customer_id' => $parent->id]);

        $this->fakeClaudeTurns([
            ['tool' => 'update_opportunity', 'input' => [
                'opportunity_id' => $other->id,
                'requires_rfm' => true,
            ]],
            ['tool' => 'request_clarification', 'input' => ['question' => 'Which job is this about?']],
        ]);

        $response = $this->postAgentEmail('For job 26-0012: this needs an RFM.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame($resolved->id, $task->opportunity_id);
        $this->assertSame('pending_clarification', $task->status);

        $other->refresh();
        $this->assertFalse((bool) $other->requires_rfm);
    }
}
