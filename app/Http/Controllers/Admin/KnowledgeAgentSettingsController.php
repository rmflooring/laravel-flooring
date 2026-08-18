<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeAgentToolAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class KnowledgeAgentSettingsController extends Controller
{
    // search_knowledge_base isn't in this matrix — visibility for it is controlled
    // per-entry via knowledge_entries.visible_to_roles instead. Only the live-data
    // tools (which have no per-record entry to attach a role list to) go through
    // this matrix.
    private const MATRIX_TOOLS = [
        'search_labour_catalog',
        'search_material_catalog',
        'get_work_order_status',
        'check_inventory',
        'get_customer_estimate',
        'get_schedule_for_crew',
    ];

    public function index(): View
    {
        $roles = Role::pluck('name')->reject(fn ($r) => $r === 'admin')->values();

        $access = KnowledgeAgentToolAccess::all()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('allowed', 'tool_name'));

        return view('admin.settings.knowledge-agent', [
            'roles' => $roles,
            'tools' => self::MATRIX_TOOLS,
            'access' => $access,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $roles = Role::pluck('name')->reject(fn ($r) => $r === 'admin')->values();
        $submitted = $request->input('access', []);

        foreach ($roles as $role) {
            foreach (self::MATRIX_TOOLS as $tool) {
                $allowed = ($submitted[$role][$tool] ?? '0') === '1';

                KnowledgeAgentToolAccess::updateOrCreate(
                    ['role' => $role, 'tool_name' => $tool],
                    ['allowed' => $allowed],
                );
            }
        }

        return back()->with('success', 'Knowledge agent tool access saved.');
    }
}
