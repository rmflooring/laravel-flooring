<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserCalendarPreferenceController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        $pref = $user->calendarPreference()->first();

        return response()->json([
            'success' => true,
            'data' => $pref ? [
                'show_rfm'           => $pref->show_rfm,
                'show_installations' => $pref->show_installations,
                'show_warehouse'     => $pref->show_warehouse,
                'show_team'          => $pref->show_team,
                'show_availability'  => $pref->show_availability,
            ] : null,
        ]);
    }

    public function upsert(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'show_rfm'           => ['nullable', 'boolean'],
            'show_installations' => ['nullable', 'boolean'],
            'show_warehouse'     => ['nullable', 'boolean'],
            'show_team'          => ['nullable', 'boolean'],
            'show_availability'  => ['nullable', 'boolean'],
        ]);

        $pref = $user->calendarPreference()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json([
            'success' => true,
            'data' => [
                'show_rfm'           => $pref->show_rfm,
                'show_installations' => $pref->show_installations,
                'show_warehouse'     => $pref->show_warehouse,
                'show_team'          => $pref->show_team,
                'show_availability'  => $pref->show_availability,
            ],
        ]);
    }
}
