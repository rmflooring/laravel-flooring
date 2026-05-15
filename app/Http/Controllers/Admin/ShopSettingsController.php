<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopSettingsController extends Controller
{
    public function index()
    {
        $notifyEmail = Setting::get('shop_quote_notify_email', '');

        return view('admin.settings.shop', compact('notifyEmail'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'shop_quote_notify_email' => ['nullable', 'email', 'max:255'],
        ]);

        Setting::set('shop_quote_notify_email', $request->input('shop_quote_notify_email', ''));

        return back()->with('success', 'Shop settings saved.');
    }
}
