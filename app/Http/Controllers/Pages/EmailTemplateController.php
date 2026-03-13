<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $user      = auth()->user();
        $templates = EmailTemplate::where('user_id', $user->id)
            ->get()
            ->keyBy('type');

        return view('pages.settings.email-templates', compact('templates'));
    }

    public function save(Request $request, string $type)
    {
        if (! array_key_exists($type, EmailTemplate::USER_TYPES)) {
            abort(404);
        }

        $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string'],
        ]);

        EmailTemplate::updateOrCreate(
            ['user_id' => auth()->id(), 'type' => $type],
            ['subject' => $request->input('subject'), 'body' => $request->input('body')],
        );

        return back()->with('success', EmailTemplate::USER_TYPES[$type] . ' template saved.');
    }

    public function reset(string $type)
    {
        if (! array_key_exists($type, EmailTemplate::USER_TYPES)) {
            abort(404);
        }

        EmailTemplate::where('user_id', auth()->id())
            ->where('type', $type)
            ->delete();

        return back()->with('success', EmailTemplate::USER_TYPES[$type] . ' template reset to default.');
    }
}
