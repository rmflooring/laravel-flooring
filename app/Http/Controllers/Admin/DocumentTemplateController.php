<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;

class DocumentTemplateController extends Controller
{
    public function index()
    {
        $templates = DocumentTemplate::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.document-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.document-templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'body'        => ['required', 'string'],
            'needs_sale'  => ['boolean'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['needs_sale'] = $request->boolean('needs_sale');
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        DocumentTemplate::create($data);

        return redirect()
            ->route('admin.document-templates.index')
            ->with('success', 'Template "' . $data['name'] . '" created.');
    }

    public function edit(DocumentTemplate $documentTemplate)
    {
        $usageCount = $documentTemplate->generatedDocuments()->count();

        return view('admin.document-templates.edit', compact('documentTemplate', 'usageCount'));
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'body'        => ['required', 'string'],
            'needs_sale'  => ['boolean'],
            'is_active'   => ['boolean'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $data['needs_sale'] = $request->boolean('needs_sale');
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $documentTemplate->update($data);

        return redirect()
            ->route('admin.document-templates.index')
            ->with('success', 'Template "' . $documentTemplate->name . '" updated.');
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        $usageCount = $documentTemplate->generatedDocuments()->count();

        if ($usageCount > 0) {
            return back()->with('error', 'Cannot delete "' . $documentTemplate->name . '" — it has been used to generate ' . $usageCount . ' document(s). Deactivate it instead.');
        }

        $name = $documentTemplate->name;
        $documentTemplate->delete();

        return redirect()
            ->route('admin.document-templates.index')
            ->with('success', 'Template "' . $name . '" deleted.');
    }
}
