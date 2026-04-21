<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelPrinterController extends Controller
{
    public function index(): View
    {
        $format = Setting::get('label_printer_format', 'standard');

        return view('admin.settings.label-printer', compact('format'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'format' => ['required', 'in:standard,zebra'],
        ]);

        Setting::set('label_printer_format', $request->input('format'));

        return redirect()->route('admin.settings.label-printer')->with('success', 'Label printer settings saved.');
    }
}
