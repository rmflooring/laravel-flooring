<?php

namespace Tests\Feature;

use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\Customer;
use App\Models\OpportunityDocument;
use App\Models\Opportunity;
use App\Models\ProjectManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

        // Trailing "done" turn for tools that no longer end the task on their own (the
        // multi-action redesign) — never consumed when the last turn is a still-terminal
        // tool (request_clarification/no_actionable_intent), since the loop breaks first.
        $responses[] = Http::response([
            'id' => 'msg_test_done',
            'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => '']],
        ], 200);

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
        $this->assertSame('2', $opportunity->sales_person_1); // defaults to Marco Bruni (employee #2)

        $customer = Customer::find($opportunity->job_site_customer_id);
        $this->assertSame('Alice Newperson', $customer->name);
        $this->assertSame('456 Oak Avenue', $customer->address);
        $this->assertSame('CLM-11223', $customer->claim_number);
        $this->assertNull($customer->parent_id);
    }

    public function test_captures_referrer_job_number_when_mentioned(): void
    {
        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Wang & Ryu',
                'address' => '716 Roderick Ave',
                'claim_number' => 'LA25112144CO',
                'job_no' => '00705807',
            ]],
        ]);

        $response = $this->postAgentEmail(
            'New job, please create new opportunity as per below.',
            subject: 'FW: Job #00705807 Wang & Ryu - 716 Roderick Ave',
        );

        $response->assertOk();

        $task = AgentTask::first();
        $opportunity = Opportunity::find($task->opportunity_id);
        $this->assertSame('00705807', $opportunity->job_no);
    }

    public function test_leaves_job_number_blank_when_none_mentioned(): void
    {
        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'No Reference Customer',
            ]],
        ]);

        $response = $this->postAgentEmail('New lead, no reference number given.');
        $response->assertOk();

        $task = AgentTask::first();
        $opportunity = Opportunity::find($task->opportunity_id);
        $this->assertNull($opportunity->job_no);
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

    public function test_links_to_existing_parent_via_shortened_name(): void
    {
        $parent = Customer::create(['name' => 'First OnSite Restoration']);

        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Jane Homeowner',
                'parent_customer_name' => 'First Onsite',
            ]],
        ]);

        $response = $this->postAgentEmail('New job for Jane Homeowner, referred by First Onsite.');

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);

        $opportunity = Opportunity::find($task->opportunity_id);
        $this->assertSame($parent->id, $opportunity->parent_customer_id);
    }

    public function test_links_to_existing_parent_regardless_of_spacing(): void
    {
        $parent = Customer::create(['name' => 'First OnSite Restoration']);

        $this->fakeClaudeTurns([
            ['tool' => 'create_opportunity', 'input' => [
                'client_name' => 'Jane Homeowner',
                'parent_customer_name' => 'FirstOnSite', // no space at all, unlike the "shortened name" test above
            ]],
        ]);

        $response = $this->postAgentEmail('New job for Jane Homeowner, referred by FirstOnSite.');

        $response->assertOk();

        $opportunity = Opportunity::find(AgentTask::first()->opportunity_id);
        $this->assertSame($parent->id, $opportunity->parent_customer_id);
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

    public function test_one_email_creates_opportunity_attaches_photos_and_sets_pm_in_one_task(): void
    {
        $parent = Customer::create(['name' => 'First OnSite Restoration']);
        $pm = ProjectManager::create(['customer_id' => $parent->id, 'name' => 'Tyler Dahlstedt']);

        // Turns 2/3 need the opportunity_id create_opportunity assigns in turn 1, which
        // isn't known until it actually runs (mid-request, thanks to QUEUE_CONNECTION=sync
        // in tests) — a closure fake that reads it back from the DB is simpler than trying
        // to predict the id up front.
        $callCount = 0;
        Http::fake(function ($request) use (&$callCount) {
            $url = $request->url();
            if (str_contains($url, 'login.microsoftonline.com')) {
                return Http::response(['access_token' => 'fake-token'], 200);
            }
            if (str_contains($url, 'graph.microsoft.com')) {
                return Http::response([], 202);
            }
            if (! str_contains($url, 'api.anthropic.com')) {
                return Http::response([], 404);
            }

            $callCount++;
            $opportunityId = AgentTask::first()?->opportunity_id;

            return match ($callCount) {
                1 => Http::response(['id' => 'msg1', 'stop_reason' => 'tool_use', 'content' => [
                    ['type' => 'tool_use', 'id' => 't1', 'name' => 'create_opportunity', 'input' => [
                        'client_name' => 'Binit & Chandani Shah',
                        'address' => '2456 Park Drive',
                        'claim_number' => '001000000985048',
                        'job_no' => '00660694',
                        'parent_customer_name' => 'First OnSite Restoration',
                    ]],
                ]], 200),
                2 => Http::response(['id' => 'msg2', 'stop_reason' => 'tool_use', 'content' => [
                    ['type' => 'tool_use', 'id' => 't2', 'name' => 'attach_images', 'input' => [
                        'opportunity_id' => $opportunityId, 'category' => 'damage',
                    ]],
                ]], 200),
                3 => Http::response(['id' => 'msg3', 'stop_reason' => 'tool_use', 'content' => [
                    ['type' => 'tool_use', 'id' => 't3', 'name' => 'update_opportunity', 'input' => [
                        'opportunity_id' => $opportunityId, 'project_manager_name' => 'Tyler',
                    ]],
                ]], 200),
                default => Http::response(['id' => 'msg_done', 'stop_reason' => 'end_turn', 'content' => [['type' => 'text', 'text' => '']]], 200),
            };
        });

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . self::API_KEY])
            ->post('/api/agent/inbound-email', [
                'from' => 'foreman@rmflooring.ca',
                'subject' => 'FW: Job #00660694 - Binit & Chandani Shah',
                'body' => 'Please create opportunity as per below. The PM is Tyler.',
                'attachments' => [
                    UploadedFile::fake()->image('damage-1.jpg'),
                    UploadedFile::fake()->image('damage-2.jpg'),
                ],
            ]);

        $response->assertOk();

        $task = AgentTask::first();
        $this->assertSame('completed', $task->status);
        $this->assertSame('create_opportunity', $task->task_type); // primary/first action
        $this->assertStringContainsString('Created opportunity', $task->extracted_intent);
        $this->assertStringContainsString('Attached 2 image', $task->extracted_intent);
        $this->assertStringContainsString('Updated opportunity', $task->extracted_intent);

        $opportunity = Opportunity::find($task->opportunity_id);
        $this->assertSame('00660694', $opportunity->job_no);
        $this->assertSame($parent->id, $opportunity->parent_customer_id);
        $this->assertSame($pm->id, $opportunity->project_manager_id);
        $this->assertSame(2, OpportunityDocument::where('opportunity_id', $opportunity->id)->count());

        // undo_data should be a list with entries for the two undoable actions
        // (attach_images, update_opportunity) — create_opportunity never gets one.
        $this->assertCount(2, $task->undo_data);
        $this->assertSame(['attach_images', 'update_opportunity'], array_column($task->undo_data, 'type'));
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
