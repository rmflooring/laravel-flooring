<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class MailSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.mail', [
            'mailFromAddress' => Setting::get('mail_from_address', config('services.microsoft.mail_from_address')),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'mail_from_address' => ['required', 'email', 'max:255'],
        ]);

        Setting::set('mail_from_address', $request->input('mail_from_address'));

        return back()->with('success', 'Mail settings saved.');
    }
}
