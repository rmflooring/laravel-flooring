<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CalendarTemplate;
use Illuminate\Http\Request;

class AdminCalendarTemplateController extends Controller
{
    public function index()
    {
        $templates = CalendarTemplate::whereIn('type', array_keys(CalendarTemplate::TYPES))
            ->get()
            ->keyBy('type');

        return view('admin.settings.calendar-templates', compact('templates'));
    }

    public function save(Request $request, string $type)
    {
        if (! array_key_exists($type, CalendarTemplate::TYPES)) {
            abort(404);
        }

        $request->validate([
            'title_template' => ['required', 'string', 'max:500'],
            'notes_template' => ['required', 'string'],
        ]);

        CalendarTemplate::updateOrCreate(
            ['type' => $type],
            [
                'title_template' => $request->input('title_template'),
                'notes_template' => $request->input('notes_template'),
            ]
        );

        return back()->with('success', CalendarTemplate::TYPES[$type] . ' template saved.');
    }

    public function reset(string $type)
    {
        if (! array_key_exists($type, CalendarTemplate::TYPES)) {
            abort(404);
        }

        CalendarTemplate::where('type', $type)->delete();

        return back()->with('success', CalendarTemplate::TYPES[$type] . ' template reset to default.');
    }
}
