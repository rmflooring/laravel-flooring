<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QboConnection;
use App\Models\QboSyncLog;
use App\Models\Setting;
use App\Services\QuickBooksService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QuickBooksController extends Controller
{
    public function __construct(private QuickBooksService $qbo) {}

    /**
     * Admin settings page — show connection status + recent sync log.
     */
    public function index()
    {
        $connection    = QboConnection::with('connectedBy')->first();
        $recentLogs    = QboSyncLog::orderByDesc('created_at')->limit(25)->get();
        $apAccountId   = Setting::get('qbo_ap_account_id', '');

        return view('admin.settings.quickbooks', compact('connection', 'recentLogs', 'apAccountId'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate(['qbo_ap_account_id' => 'required|string|max:50']);
        Setting::set('qbo_ap_account_id', trim($request->qbo_ap_account_id));

        return back()->with('success', 'QuickBooks settings saved.');
    }

    /**
     * Redirect the admin to Intuit's OAuth authorization page.
     */
    public function connect(Request $request)
    {
        $state = Str::random(32);
        session(['qbo_oauth_state' => $state]);

        return redirect($this->qbo->getAuthorizationUrl($state));
    }

    /**
     * Handle the OAuth callback from Intuit.
     */
    public function callback(Request $request)
    {
        // Validate state to prevent CSRF
        if ($request->input('state') !== session('qbo_oauth_state')) {
            return redirect()->route('admin.settings.quickbooks')
                ->with('error', 'Invalid OAuth state. Please try connecting again.');
        }

        if ($request->has('error')) {
            return redirect()->route('admin.settings.quickbooks')
                ->with('error', 'QuickBooks authorization was denied: ' . $request->input('error_description'));
        }

        try {
            $this->qbo->handleCallback(
                code:    $request->input('code'),
                realmId: $request->input('realmId'),
                userId:  auth()->id(),
            );

            return redirect()->route('admin.settings.quickbooks')
                ->with('success', 'QuickBooks Online connected successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.quickbooks')
                ->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect from QuickBooks Online.
     */
    public function disconnect(Request $request)
    {
        try {
            $this->qbo->disconnect();

            return redirect()->route('admin.settings.quickbooks')
                ->with('success', 'QuickBooks Online disconnected.');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.quickbooks')
                ->with('error', 'Disconnect failed: ' . $e->getMessage());
        }
    }
}
