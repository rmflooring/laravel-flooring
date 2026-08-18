<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KnowledgeEntry;
use App\Services\Agent\KnowledgeEntryService;
use App\Services\Agent\PdfTextExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class KnowledgeEntryController extends Controller
{
    public function index(Request $request): View
    {
        $query = KnowledgeEntry::query()->withCount('embeddings')->latest();

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }
        if ($q = $request->input('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")->orWhere('content', 'like', "%{$q}%");
            });
        }

        $entries = $query->paginate(25)->withQueryString();

        return view('admin.knowledge.index', [
            'entries' => $entries,
            'categories' => KnowledgeEntry::CATEGORIES,
            'category' => $category,
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('admin.knowledge.create', [
            'categories' => KnowledgeEntry::CATEGORIES,
            'roles' => Role::pluck('name'),
        ]);
    }

    public function store(Request $request, KnowledgeEntryService $service): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $service->create($data, $request->user());
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not save this entry: ' . $e->getMessage());
        }

        return redirect()->route('admin.knowledge.index')->with('success', 'Knowledge entry created.');
    }

    public function edit(KnowledgeEntry $knowledge): View
    {
        return view('admin.knowledge.edit', [
            'entry' => $knowledge,
            'categories' => KnowledgeEntry::CATEGORIES,
            'roles' => Role::pluck('name'),
        ]);
    }

    public function update(Request $request, KnowledgeEntry $knowledge, KnowledgeEntryService $service): RedirectResponse
    {
        $data = $this->validated($request);

        try {
            $service->update($knowledge, $data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Could not save this entry: ' . $e->getMessage());
        }

        return redirect()->route('admin.knowledge.index')->with('success', 'Knowledge entry updated.');
    }

    public function destroy(KnowledgeEntry $knowledge): RedirectResponse
    {
        $knowledge->delete();

        return back()->with('success', 'Knowledge entry deleted.');
    }

    /**
     * Extracts text from an uploaded PDF for the create/edit form's "Import from PDF"
     * button. Returns the raw extracted text for the admin to review/edit in the
     * content textarea — nothing is saved or embedded here.
     */
    public function extractPdf(Request $request, PdfTextExtractionService $extractor): JsonResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        try {
            $text = $extractor->extractFromPath($request->file('pdf')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['text' => $text]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:' . implode(',', KnowledgeEntry::CATEGORIES)],
            'content' => ['required', 'string'],
            'structured_data' => ['nullable', 'string'],
            'visible_to_roles' => ['required', 'array', 'min:1'],
            'visible_to_roles.*' => ['string'],
        ]);

        if (! empty($data['structured_data'])) {
            $decoded = json_decode($data['structured_data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'structured_data' => 'Structured data must be valid JSON.',
                ]);
            }
            $data['structured_data'] = $decoded;
        } else {
            $data['structured_data'] = null;
        }

        return $data;
    }
}
