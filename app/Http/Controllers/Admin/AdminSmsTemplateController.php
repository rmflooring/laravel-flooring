<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class AdminSmsTemplateController extends Controller
{
    public function index()
    {
        $templates = SmsTemplate::all()->keyBy('type');

        return view('admin.settings.sms-templates', compact('templates'));
    }

    public function save(Request $request, string $type)
    {
        if (! array_key_exists($type, SmsTemplate::TYPES)) {
            abort(404);
        }

        $request->validate([
            'body' => ['required', 'string', 'max:1600'],
        ]);

        SmsTemplate::updateOrCreate(
            ['type' => $type],
            ['body' => $request->input('body')],
        );

        return back()->with('success', SmsTemplate::TYPES[$type] . ' template saved.');
    }

    public function reset(string $type)
    {
        if (! array_key_exists($type, SmsTemplate::TYPES)) {
            abort(404);
        }

        SmsTemplate::where('type', $type)->delete();

        return back()->with('success', SmsTemplate::TYPES[$type] . ' template reset to default.');
    }
}
