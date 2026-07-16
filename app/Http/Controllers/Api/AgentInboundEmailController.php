<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAgentTask;
use App\Models\AgentSetting;
use App\Models\AgentTask;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AgentInboundEmailController extends Controller
{
    /**
     * Receives already-parsed email from the Postfix pipe script (multipart/form-data:
     * from, subject, body, attachments[] as real file uploads) and queues it for
     * Claude tool-use processing.
     */
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['required', 'email'],
            'subject' => ['nullable', 'string', 'max:998'],
            'body' => ['required', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
        ]);

        $settings = AgentSetting::current();

        if (! $settings->isSenderAllowed($validated['from'])) {
            return response()->json(['error' => 'Sender not allowed.'], 403);
        }

        $rateLimitKey = 'agent-inbound:' . strtolower($validated['from']);
        $maxPerHour = max(1, $settings->rate_limit_per_sender_per_hour);
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxPerHour)) {
            return response()->json(['error' => 'Rate limit exceeded.'], 429);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $attachments = [];
        foreach ($request->file('attachments', []) as $file) {
            $attachments[] = [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size' => $file->getSize(),
                'content_base64' => base64_encode(file_get_contents($file->getRealPath())),
            ];
        }

        $requesterUserId = User::where('email', $validated['from'])->value('id');

        $subject = $validated['subject'] ?? '';
        $rawContent = trim($subject . "\n\n" . $validated['body']);

        $task = AgentTask::create([
            'source' => 'email',
            'requester_email' => $validated['from'],
            'requester_user_id' => $requesterUserId,
            'raw_content' => $rawContent,
            'attachments' => $attachments,
            'status' => 'queued',
        ]);

        ProcessAgentTask::dispatch($task->id);

        return response()->json(['success' => true, 'task_id' => $task->id]);
    }
}
