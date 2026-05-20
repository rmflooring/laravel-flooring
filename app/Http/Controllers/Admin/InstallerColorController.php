<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Installer;
use App\Models\MicrosoftAccount;
use App\Services\GraphCalendarService;
use Illuminate\Http\Request;

class InstallerColorController extends Controller
{
    public function index()
    {
        $installers  = Installer::orderBy('company_name')->get(['id', 'company_name', 'calendar_color']);
        $colorMap    = Installer::CALENDAR_COLORS;

        return view('admin.settings.installer-colors', compact('installers', 'colorMap'));
    }

    public function update(Request $request)
    {
        $colors = $request->input('colors', []);

        foreach ($colors as $id => $color) {
            Installer::where('id', (int) $id)->update([
                'calendar_color' => ($color && array_key_exists($color, Installer::CALENDAR_COLORS)) ? $color : null,
            ]);
        }

        // Push to all connected MS365 accounts
        $this->pushToAllAccounts();

        return back()->with('success', 'Installer calendar colors saved and pushed to all connected calendars.');
    }

    public function push()
    {
        $this->pushToAllAccounts();

        return back()->with('success', 'Installer calendar colors pushed to all connected calendars.');
    }

    private function pushToAllAccounts(): void
    {
        $installers = Installer::orderBy('company_name')->get(['id', 'company_name', 'calendar_color']);
        $accounts   = MicrosoftAccount::where('is_connected', true)->get();
        $service    = new GraphCalendarService();

        foreach ($accounts as $account) {
            try {
                $service->pushInstallerCategories($account, $installers);
            } catch (\Throwable $e) {
                // Best-effort — don't let one failed account block the rest
                \Illuminate\Support\Facades\Log::warning('[InstallerColors] Push failed for account', [
                    'account_id' => $account->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }
    }
}
