<?php
namespace App\Http\Controllers;

use App\Models\MailLog;

class EmailTrackingController extends Controller
{
    public function pixel(string $token)
    {
        $log = MailLog::where('tracking_token', $token)->first();
        if ($log && $log->opened_at === null) {
            $log->update(['opened_at' => now()]);
        }

        // 1×1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($gif, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
