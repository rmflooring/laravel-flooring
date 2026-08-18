<?php

namespace Tests\Feature;

use App\Models\AgentNotificationSetting;
use App\Models\AgentSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('Admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('Admin');

        return $user;
    }

    public function test_index_renders_current_settings(): void
    {
        $admin = $this->adminUser();

        AgentSetting::current()->update([
            'admin_notification_email' => 'ops@rmflooring.ca',
            'allowed_sender_domains' => ['rmflooring.ca'],
            'allowed_sender_addresses' => ['someone@gmail.com'],
            'rate_limit_per_sender_per_hour' => 25,
        ]);

        $response = $this->actingAs($admin)->get('/admin/settings/agent');

        $response->assertOk();
        $response->assertSee('ops@rmflooring.ca');
        $response->assertSee('rmflooring.ca');
        $response->assertSee('someone@gmail.com');
    }

    public function test_update_persists_settings_and_splits_line_separated_lists(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->put('/admin/settings/agent', [
            'admin_notification_email' => 'ops@rmflooring.ca',
            'rate_limit_per_sender_per_hour' => 15,
            'allowed_sender_domains' => "rmflooring.ca\n\nexample.com ",
            'allowed_sender_addresses' => 'someone@gmail.com',
            'bcc' => [
                'attach_images' => '1',
                'create_opportunity' => '1',
            ],
        ]);

        $response->assertRedirect();

        $settings = AgentSetting::current();
        $this->assertSame('ops@rmflooring.ca', $settings->admin_notification_email);
        $this->assertSame(15, $settings->rate_limit_per_sender_per_hour);
        $this->assertSame(['rmflooring.ca', 'example.com'], $settings->allowed_sender_domains);
        $this->assertSame(['someone@gmail.com'], $settings->allowed_sender_addresses);
    }

    public function test_update_only_enables_bcc_for_checked_task_types(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)->put('/admin/settings/agent', [
            'rate_limit_per_sender_per_hour' => 20,
            'bcc' => [
                'attach_images' => '1',
                'attach_document' => '0',
                'create_opportunity' => '1',
            ],
        ]);

        $this->assertTrue(AgentNotificationSetting::where('task_type', 'attach_images')->value('admin_bcc_enabled'));
        $this->assertTrue(AgentNotificationSetting::where('task_type', 'create_opportunity')->value('admin_bcc_enabled'));
        $this->assertFalse(AgentNotificationSetting::where('task_type', 'attach_document')->value('admin_bcc_enabled'));
        // A task type never submitted in `bcc` at all still gets a row, defaulted off.
        $this->assertFalse(AgentNotificationSetting::where('task_type', 'other')->value('admin_bcc_enabled'));
    }

    public function test_non_admin_cannot_access_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/settings/agent')->assertStatus(403);
        $this->actingAs($user)->put('/admin/settings/agent', [])->assertStatus(403);
    }
}
