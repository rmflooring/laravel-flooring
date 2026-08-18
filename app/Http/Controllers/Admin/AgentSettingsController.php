<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentNotificationSetting;
use App\Models\AgentSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentSettingsController extends Controller
{
    public function index(): View
    {
        $settings = AgentSetting::current();

        $bccEnabled = AgentNotificationSetting::pluck('admin_bcc_enabled', 'task_type');

        return view('admin.settings.agent', [
            'settings' => $settings,
            'taskTypes' => AgentTaskController::TASK_TYPES,
            'bccEnabled' => $bccEnabled,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notification_email' => ['nullable', 'email', 'max:255'],
            'rate_limit_per_sender_per_hour' => ['required', 'integer', 'min:1'],
            'allowed_sender_domains' => ['nullable', 'string'],
            'allowed_sender_addresses' => ['nullable', 'string'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['string'],
        ]);

        $settings = AgentSetting::current();
        $settings->update([
            'admin_notification_email' => $validated['admin_notification_email'] ?: null,
            'rate_limit_per_sender_per_hour' => $validated['rate_limit_per_sender_per_hour'],
            'allowed_sender_domains' => $this->linesToArray($validated['allowed_sender_domains'] ?? ''),
            'allowed_sender_addresses' => $this->linesToArray($validated['allowed_sender_addresses'] ?? ''),
        ]);

        $bcc = $request->input('bcc', []);
        foreach (AgentTaskController::TASK_TYPES as $taskType) {
            AgentNotificationSetting::updateOrCreate(
                ['task_type' => $taskType],
                ['admin_bcc_enabled' => ($bcc[$taskType] ?? '0') === '1'],
            );
        }

        return back()->with('success', 'Agent settings saved.');
    }

    private function linesToArray(string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
