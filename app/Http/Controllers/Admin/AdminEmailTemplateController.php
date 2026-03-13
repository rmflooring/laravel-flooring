<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class AdminEmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::whereNull('user_id')
            ->get()
            ->keyBy('type');

        return view('admin.settings.email-templates', compact('templates'));
    }

    public function save(Request $request, string $type)
    {
        if (! array_key_exists($type, EmailTemplate::SYSTEM_TYPES)) {
            abort(404);
        }

        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        EmailTemplate::updateOrCreate(
            ['user_id' => null, 'type' => $type],
            ['subject' => $request->input('subject'), 'body' => $request->input('body')],
        );

        return back()->with('success', EmailTemplate::SYSTEM_TYPES[$type] . ' template saved.');
    }

    public function reset(string $type)
    {
        if (! array_key_exists($type, EmailTemplate::SYSTEM_TYPES)) {
            abort(404);
        }

        EmailTemplate::whereNull('user_id')
            ->where('type', $type)
            ->delete();

        return back()->with('success', EmailTemplate::SYSTEM_TYPES[$type] . ' template reset to default.');
    }
}
