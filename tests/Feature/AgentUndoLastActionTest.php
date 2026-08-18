<?php

namespace Tests\Feature;

use App\Models\AgentTask;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\OpportunityNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentUndoLastActionTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_undo_update_opportunity_reverts_previous_values(): void
    {
        $admin = $this->adminUser();
        $opportunity = Opportunity::create(['job_no' => '26-0001', 'requires_rfm' => false]);

        $task = AgentTask::create([
            'source' => 'email', 'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed', 'task_type' => 'update_opportunity',
            'opportunity_id' => $opportunity->id,
            'undo_data' => [
                'type' => 'update_opportunity',
                'opportunity_id' => $opportunity->id,
                'previous_values' => ['requires_rfm' => false],
            ],
        ]);

        $opportunity->update(['requires_rfm' => true]);

        $response = $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo');
        $response->assertRedirect();

        $this->assertFalse($opportunity->fresh()->requires_rfm);
        $this->assertNotNull($task->fresh()->undone_at);
    }

    public function test_undo_attach_images_archives_the_document(): void
    {
        $admin = $this->adminUser();
        $opportunity = Opportunity::create(['job_no' => '26-0002']);
        $document = OpportunityDocument::create([
            'opportunity_id' => $opportunity->id, 'disk' => 'local', 'path' => 'x.jpg',
            'original_name' => 'x.jpg', 'category' => 'media',
        ]);

        $task = AgentTask::create([
            'source' => 'email', 'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed', 'task_type' => 'attach_images',
            'opportunity_id' => $opportunity->id,
            'undo_data' => ['type' => 'attach_images', 'document_ids' => [$document->id]],
        ]);

        $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo')->assertRedirect();

        $this->assertNull(OpportunityDocument::find($document->id));
        $this->assertNotNull(OpportunityDocument::withTrashed()->find($document->id));
    }

    public function test_undo_log_communication_removes_the_note(): void
    {
        $admin = $this->adminUser();
        $opportunity = Opportunity::create(['job_no' => '26-0003']);
        $note = OpportunityNote::create([
            'opportunity_id' => $opportunity->id, 'user_id' => $admin->id,
            'body' => 'test', 'category' => 'other', 'source' => 'agent',
        ]);

        $task = AgentTask::create([
            'source' => 'email', 'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed', 'task_type' => 'log_communication',
            'opportunity_id' => $opportunity->id,
            'undo_data' => ['type' => 'log_communication', 'note_id' => $note->id],
        ]);

        $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo')->assertRedirect();

        $this->assertNull(OpportunityNote::find($note->id));
    }

    public function test_undo_multi_action_task_reverts_undoable_entries_and_reports_skipped(): void
    {
        $admin = $this->adminUser();
        $opportunity = Opportunity::create(['job_no' => '26-0006', 'requires_rfm' => false]);
        $document = OpportunityDocument::create([
            'opportunity_id' => $opportunity->id, 'disk' => 'local', 'path' => 'x.jpg',
            'original_name' => 'x.jpg', 'category' => 'media',
        ]);

        // Simulates a task that created the opportunity (not undoable — no entry for it
        // at all, same as ProcessAgentTask never adding one for create_opportunity),
        // attached a photo, and set requires_rfm — the array shape a multi-action task
        // now produces.
        $task = AgentTask::create([
            'source' => 'email', 'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed', 'task_type' => 'create_opportunity',
            'opportunity_id' => $opportunity->id,
            'undo_data' => [
                ['type' => 'attach_images', 'document_ids' => [$document->id]],
                ['type' => 'update_opportunity', 'opportunity_id' => $opportunity->id, 'previous_values' => ['requires_rfm' => false]],
            ],
        ]);

        $opportunity->update(['requires_rfm' => true]);

        $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo')->assertRedirect();

        $this->assertNull(OpportunityDocument::find($document->id));
        $this->assertFalse($opportunity->fresh()->requires_rfm);
        $this->assertNotNull($task->fresh()->undone_at);
    }

    public function test_undo_is_refused_for_create_opportunity(): void
    {
        $admin = $this->adminUser();
        $opportunity = Opportunity::create(['job_no' => '26-0004']);

        $task = AgentTask::create([
            'source' => 'email', 'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed', 'task_type' => 'create_opportunity',
            'opportunity_id' => $opportunity->id,
        ]);

        $response = $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo');
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertNull($task->fresh()->undone_at);
    }

    public function test_undo_is_refused_when_already_undone(): void
    {
        $admin = $this->adminUser();
        $opportunity = Opportunity::create(['job_no' => '26-0005', 'requires_rfm' => false]);

        $task = AgentTask::create([
            'source' => 'email', 'requester_email' => 'foreman@rmflooring.ca',
            'status' => 'completed', 'task_type' => 'update_opportunity',
            'opportunity_id' => $opportunity->id,
            'undone_at' => now(),
            'undo_data' => [
                'type' => 'update_opportunity',
                'opportunity_id' => $opportunity->id,
                'previous_values' => ['requires_rfm' => false],
            ],
        ]);

        $response = $this->actingAs($admin)->post('/admin/agent/tasks/' . $task->id . '/undo');
        $response->assertSessionHas('error', 'This task has already been undone.');
    }
}
