<?php

namespace App\Http\Controllers\Pages\Settings\Integrations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MicrosoftCalendar;

class MicrosoftIntegrationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $account = $user->microsoftAccount;

        $calendars = collect();

        if ($account) {
            $calendars = MicrosoftCalendar::where('microsoft_account_id', $account->id)
                ->orderByDesc('is_primary')
                ->orderBy('name')
                ->get();
        }

        return view('pages.settings.integrations.microsoft.index', [
            'account' => $account,
            'calendars' => $calendars,
        ]);
    }

    public function updateCalendar(Request $request, MicrosoftCalendar $calendar)
    {
        // Security: ensure this calendar belongs to the logged-in user
        $account = $request->user()->microsoftAccount;

        if (!$account || $calendar->microsoft_account_id !== $account->id) {
            abort(403);
        }

        $isEnabled = (bool) $request->input('is_enabled');

$calendar->is_enabled = $isEnabled;
$calendar->save();

        return response()->json([
            'success' => true,
            'is_enabled' => $calendar->is_enabled,
        ]);
    }
}
