<?php

namespace App\Http\Controllers\Installer;

use App\Http\Controllers\Controller;
use App\Models\Installer;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $installer = Installer::where('user_id', auth()->id())->first();

        if (! $installer) {
            return view('installer.dashboard', [
                'installer'  => null,
                'today'      => collect(),
                'upcoming'   => collect(),
                'past'       => collect(),
                'showAll'    => false,
            ]);
        }

        $showAll = $request->boolean('show_all');

        $baseQuery = fn() => WorkOrder::where('installer_id', $installer->id)
            ->with(['sale'])
            ->withoutTrashed();

        $today = $baseQuery()
            ->whereDate('scheduled_date', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_time')
            ->get();

        $upcoming = $baseQuery()
            ->whereDate('scheduled_date', '>', today())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        $pastQuery = $baseQuery()
            ->where(function ($q) {
                $q->whereIn('status', ['completed', 'cancelled', 'partial', 'site_not_ready', 'needs_levelling', 'needs_attention'])
                  ->orWhereDate('scheduled_date', '<', today());
            })
            ->orderByDesc('scheduled_date');

        if (! $showAll) {
            $pastQuery->where('scheduled_date', '>=', now()->subDays(30)->toDateString());
        }

        $past = $pastQuery->get();

        return view('installer.dashboard', compact('installer', 'today', 'upcoming', 'past', 'showAll'));
    }
}
