<?php

namespace Tests\Feature;

use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\Customer;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentCreateOpportunityTest extends TestCase
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

    private function postAgentEmail(string $body, string $subject = 'New job referral'): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'foreman@rmflooring.ca',
                'subject' => $subject,
                'body' => $body,
            ]);
    }

    public function test_creates_standalone_customer_and_opportunity(): void
    {
        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Alice Newperson',
                'address' => '456 Oak Avenue',
                'claim_number' => 'CLM-11223',
                'insurance_company' => 'Acme Insurance',
            ]],
        ]);

        $response = $this->postAgentEmail(
            'New water damage claim for Alice Newperson at 456 Oak Avenue, claim CLM-11223 with Acme Insurance.'
        );

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);
        $this->assertSame('create_opportunity', $task->task_type);
        $this->assertNotNull($task->opportunity_id);

        $opportunity = Opportunity::find($task->opportunity_id);
        $this->assertSame($opportunity->parent_customer_id, $opportunity->job_site_customer_id);
        $this->assertSame('New', $opportunity->status);
        $this->assertTrue((bool) $opportunity->requires_rfm);

        $customer = Customer::find($opportunity->job_site_customer_id);
        $this->assertSame('Alice Newperson', $customer->name);
        $this->assertSame('456 Oak Avenue', $customer->address);
        $this->assertSame('CLM-11223', $customer->claim_number);
        $this->assertNull($customer->parent_id);
    }

    public function test_links_new_job_site_customer_to_existing_parent(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);

        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Bob Tenant',
                'parent_customer_name' => 'Acme Property Management',
                'address' => '789 Pine Street',
            ]],
        ]);

        $response = $this->postAgentEmail('New unit for Bob Tenant at 789 Pine Street, managed by Acme Property Management.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);

        $opportunity = Opportunity::find($task->opportunity_id);
        $this->assertSame($parent->id, $opportunity->parent_customer_id);
        $this->assertNotSame($opportunity->parent_customer_id, $opportunity->job_site_customer_id);

        $jobSite = Customer::find($opportunity->job_site_customer_id);
        $this->assertSame('Bob Tenant', $jobSite->name);
        $this->assertSame($parent->id, $jobSite->parent_id);
    }

    public function test_unresolvable_parent_customer_name_triggers_clarification(): void
    {
        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Bob Tenant',
                'parent_customer_name' => 'Nonexistent Management Co',
            ]],
            ['tool' => 'request_clarification', 'input' => ['question' => 'Which parent company is this?']],
        ]);

        $response = $this->postAgentEmail('New unit for Bob Tenant, managed by Nonexistent Management Co.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('pending_clarification', $task->status);
        $this->assertNull($task->opportunity_id);
        $this->assertSame(0, Opportunity::count());
        $this->assertSame(0, Customer::count());
    }

    public function test_duplicate_check_blocks_creation_of_recent_similar_opportunity(): void
    {
        $parent = Customer::create(['name' => 'Acme Property Management']);
        $jobSite = Customer::create([
            'name' => 'Carol Existing',
            'address' => '100 Existing Lane',
            'claim_number' => 'CLM-55443',
            'parent_id' => $parent->id,
        ]);
        Opportunity::create(['parent_customer_id' => $parent->id, 'job_site_customer_id' => $jobSite->id]);

        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Carol Existing',
                'address' => '100 Existing Lane',
                'claim_number' => 'CLM-55443',
            ]],
            ['tool' => 'request_clarification', 'input' => ['question' => 'This looks like it might already exist — is this a new job?']],
        ]);

        $response = $this->postAgentEmail('Claim CLM-55443 for Carol Existing at 100 Existing Lane.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('pending_clarification', $task->status);
        $this->assertNull($task->opportunity_id);
        $this->assertSame(1, Opportunity::count());
        $this->assertSame(2, Customer::count());
    }

    public function test_incomplete_intake_still_creates_but_flags_missing_fields(): void
    {
        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Dana Sparse',
            ]],
        ]);

        $response = $this->postAgentEmail('New job for Dana Sparse, not much detail yet.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->opportunity_id);
        $this->assertStringContainsString('incomplete intake', mb_strtolower($task->extracted_intent));

        $loggedIncomplete = $task->messages()
            ->where('body', 'like', '%incomplete intake%')
            ->exists();
        $this->assertTrue($loggedIncomplete);
    }

    public function test_refuses_to_create_when_opportunity_already_resolved(): void
    {
        $opportunity = Opportunity::create(['job_no' => '26-9030']);

        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => ['client_name' => 'Someone New']],
            ['tool' => 'request_clarification', 'input' => ['question' => 'What would you like me to do for job 26-9030?']],
        ]);

        $response = $this->postAgentEmail('For job 26-9030: also please create a new opportunity for Someone New.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame($opportunity->id, $task->opportunity_id);
        $this->assertSame('pending_clarification', $task->status);
        $this->assertSame(1, Opportunity::count());
    }
}
