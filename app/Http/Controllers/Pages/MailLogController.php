<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\MailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MailLogController extends Controller
{
    /**
     * Return the most recent successfully sent mail_log entry for a given record.
     * Used by the "Sent" email preview modal throughout the app.
     */
    public function latest(string $type, int $id): JsonResponse
    {
        $entry = MailLog::where('related_type', $type)
            ->where('related_id', $id)
            ->where('status', 'sent')
            ->latest()
            ->first();

        if (! $entry) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'           => true,
            'from'            => $entry->sent_from,
            'to'              => $entry->to,
            'cc'              => $entry->cc,
            'subject'         => $entry->subject,
            'body'            => $entry->body,
            'attachment_name' => $entry->attachment_name,
            'pdf_url'         => $entry->pdf_url,
            'sent_at'         => $entry->created_at->format('M j, Y \a\t g:i A'),
            'track'           => $entry->track,
        ]);
    }

    public function servePdf(string $type, int $id): Response
    {
        $path = "mail-attachments/{$type}/{$id}.pdf";

        abort_if(! Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $type . '-' . $id . '.pdf"',
        ]);
    }
}
