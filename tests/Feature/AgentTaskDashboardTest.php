<?php

namespace Tests\Feature;

use App\Jobs\ProcessAgentTask;
use App\Models\AgentMessage;
use App\Models\AgentTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentTaskDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_index_lists_tasks_and_filters_by_status(): void
    {
        $admin = $this->adminUser();

        AgentTask::create(['source' => 'email', 'requester_email' => 'a@rmflooring.ca', 'status' => 'completed']);
        AgentTask::create(['source' => 'email', 'requester_email' => 'b@rmflooring.ca', 'status' => 'pending_clarification']);

        $response = $this->actingAs($admin)->get('/admin/agent/tasks');
        $response->assertOk();
        $response->assertSee('a@rmflooring.ca');
        $response->assertSee('b@rmflooring.ca');

        $filtered = $this->actingAs($admin)->get('/admin/agent/tasks?status=completed');
        $filtered->assertOk();
        $filtered->assertSee('a@rmflooring.ca');
        $filtered->assertDontSee('b@rmflooring.ca');
    }

    public function test_show_renders_task_and_message_thread(): void
    {
        $admin = $this->adminUser();

        $task = AgentTask::create([
            'source' => 'email',
            'requester_email' => 'foreman@rmflooring.ca',
            'raw_content' => 'Attach photos for 123 Main St',
            'status' => 'pending_clarification',
        ]);
        AgentMessage::create(['task_id' => $task->id, 'sender' => 'agent', 'body' => 'Which job is this for?']);

        $response = $this->actingAs($admin)->get('/admin/agent/tasks/' . $task->id);

        $response->assertOk();
        $response->assertSee('Which job is this for?');
        $response->assertSee('Send Reply');
    }

    public function test_reply_on_pending_clarification_task_creates_message_and_dispatches_job(): void
    {
        Bus::fake();
        $admin = $this->adminUser();

        $task = AgentTask::create([
            'source' => 'email',
            'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'pending_clarification',
        ]);
        AgentMessage::create(['task_id' => $task->id, 'sender' => 'agent', 'body' => 'Which job is this for?']);

        $response = $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/reply', [
            'body' => 'It is job 26-0001.',
        ]);

        $response->assertRedirect();
        $task->refresh();

        $this->assertSame('queued', $task->status);
        $this->assertSame(2, $task->messages()->count());
        $this->assertSame('user', $task->messages()->latest('id')->first()->sender);

        Bus::assertDispatched(ProcessAgentTask::class, fn (ProcessAgentTask $job) => $job->taskId === $task->id);
    }

    public function test_reply_is_rejected_when_task_is_not_pending_clarification(): void
    {
        Bus::fake();
        $admin = $this->adminUser();

        $task = AgentTask::create([
            'source' => 'email',
            'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/reply', [
            'body' => 'too late',
        ]);

        $response->assertRedirect();
        $this->assertSame(0, $task->messages()->count());
        Bus::assertNotDispatched(ProcessAgentTask::class);
    }

    public function test_undo_stub_does_not_error_on_completed_task(): void
    {
        $admin = $this->adminUser();

        $task = AgentTask::create([
            'source' => 'email',
            'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/agent/tasks');

        $response->assertStatus(403);
    }
}
