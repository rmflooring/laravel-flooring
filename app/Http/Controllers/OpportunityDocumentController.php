<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\OpportunityDocumentLabel;
use App\Models\Sale;
use App\Services\DocumentTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpportunityDocumentController extends Controller
{

public function index(Opportunity $opportunity, Request $request)
{
    // Filters (optional for now)
    $type         = $request->get('type'); // all | documents | media
    $labelId      = $request->get('label_id'); // managed label
    $labelText    = $request->get('label_text'); // free text
    $showArchived = $request->boolean('show_archived');

    $documentsQuery = $opportunity->documents()
        ->withTrashed()
        ->with('label')
        ->orderByDesc('created_at');

    if (!$showArchived) {
        $documentsQuery->whereNull('deleted_at');
    }

    // type filter
    if ($type === 'documents') {
        $documentsQuery->whereIn('category', ['documents', 'generated_document']);
    } elseif ($type === 'media') {
        $documentsQuery->where('category', 'media');
    }

    // label filter
    if ($labelId) {
        $documentsQuery->where('label_id', $labelId);
    }

    if ($labelText) {
        $documentsQuery->where('label_text', $labelText);
    }

    $documents = $documentsQuery->paginate(25)->withQueryString();

    $labels = OpportunityDocumentLabel::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name']);

    $activeTemplates = DocumentTemplate::where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'description', 'needs_sale']);

    $opportunitySales = $opportunity->sales()
        ->orderByDesc('id')
        ->get(['id', 'sale_number', 'job_name']);

    return view('pages.opportunities.documents.index', [
        'opportunity'      => $opportunity,
        'documents'        => $documents,
        'labels'           => $labels,
        'type'             => $type,
        'labelId'          => $labelId,
        'labelText'        => $labelText,
        'showArchived'     => $showArchived,
        'activeTemplates'  => $activeTemplates,
        'opportunitySales' => $opportunitySales,
    ]);
}


    public function store(Opportunity $opportunity, Request $request)
{
    $request->validate([
        'files'   => ['required', 'array', 'min:1'],
        'files.*' => ['file', 'max:512000'], // 500MB per file (KB)
        'label_id' => ['nullable', 'integer', 'exists:opportunity_document_labels,id'],
        'description' => ['nullable', 'string'],
    ]);

    try {
        $userId = Auth::id();

        // ✅ apply to every uploaded file in this batch
        $labelId     = $request->input('label_id');
        $description = $request->input('description');

        // Auto-detect the "Photos" label for image uploads when no label chosen
        $photosLabelId = $labelId ?? (OpportunityDocumentLabel::where('name', 'Photos')->where('is_active', true)->value('id'));

        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType() ?? '';

            // Category values (plural)
            $category = (str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/'))
                ? 'media'
                : 'documents';

            // Auto-apply "Photos" label to images when no label was manually selected
            $resolvedLabelId = (str_starts_with($mime, 'image/') && ! $labelId)
                ? $photosLabelId
                : $labelId;

            // Save in public disk so it's accessible via /storage/...
            $disk = 'public';
            $dir  = "opportunities/{$opportunity->id}";

            $path = $file->store($dir, $disk);

            \Log::info('[docs] stored file', [
                'opportunity_id' => $opportunity->id,
                'path'           => $path,
                'mime'           => $mime,
                'original'       => $file->getClientOriginalName(),
            ]);

            OpportunityDocument::create([
                'opportunity_id' => $opportunity->id,
                'disk'           => $disk,
                'path'           => $path,
                'original_name'  => $file->getClientOriginalName(),
                'stored_name'    => basename($path),
                'mime_type'      => $mime,
                'extension'      => $file->getClientOriginalExtension(),
                'size_bytes'     => $file->getSize(),
                'category'       => $category,

                // ✅ NEW
                'label_id'       => $resolvedLabelId,
                'description'    => $description,

                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);
        }

        return back()->with('success', 'Files uploaded successfully.');
    } catch (\Throwable $e) {
        \Log::error('[docs] upload failed', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]);

        return back()->with('error', 'Upload failed: ' . $e->getMessage());
    }
}


    public function update(Opportunity $opportunity, OpportunityDocument $document, Request $request)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);

        $data = $request->validate([
            'description'       => ['nullable', 'string'],
            'label_id'          => ['nullable', 'integer', 'exists:opportunity_document_labels,id'],
            'label_text'        => ['nullable', 'string', 'max:255'],
            'category_override' => ['nullable', 'string', 'max:50'],
        ]);

        $data['updated_by'] = Auth::id();

        $document->update($data);

        return back()->with('success', 'Document updated.');
    }

    public function destroy(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);

        // Soft delete = archive for all users
        $document->delete();

        return back()->with('success', 'Document archived.');
    }
	
	public function bulkDestroy(Opportunity $opportunity, Request $request)
	{
		$ids = $request->input('ids', []);

		if (!is_array($ids) || count($ids) < 1) {
			return redirect()
				->route('pages.opportunities.documents.index', $opportunity->id)
				->with('error', 'No files selected.');
		}

		// Only documents that belong to this opportunity, and are not already archived
		$docs = OpportunityDocument::where('opportunity_id', $opportunity->id)
			->whereIn('id', $ids)
			->whereNull('deleted_at')
			->get();

		$count = 0;

		foreach ($docs as $doc) {
			$doc->delete(); // soft delete (archive)
			$count++;
		}

		return $this->redirectAfterBulk($opportunity, $request)
			->with('success', 'Selected files archived.');
	}

	
	public function bulkRestore(Opportunity $opportunity, Request $request)
	{
    $ids = $request->input('ids', []);

    if (!is_array($ids) || count($ids) < 1) {
        return redirect()
            ->route('pages.opportunities.documents.index', $opportunity->id)
            ->with('error', 'No files selected.');
    }

    // Only documents that belong to this opportunity AND are currently archived
    $docs = OpportunityDocument::withTrashed()
        ->where('opportunity_id', $opportunity->id)
        ->whereIn('id', $ids)
        ->whereNotNull('deleted_at')
        ->get();

    $count = 0;

    foreach ($docs as $doc) {
        $doc->restore();
        $count++;
    }

    return redirect()
        ->route('pages.opportunities.documents.index', $opportunity->id)
        ->with('success', 'Selected files restored.');
	}

	public function bulkForceDestroy(Opportunity $opportunity, Request $request)
	{
		$ids = $request->input('ids', []);

		if (!is_array($ids) || count($ids) < 1) {
			return redirect()
				->route('pages.opportunities.documents.index', $opportunity->id)
				->with('error', 'No files selected.');
		}

		// From the media gallery, active files can be force-deleted directly.
		// From the documents page, only already-archived files are eligible.
		$fromMedia = $request->input('redirect_to') === 'media';

		$query = OpportunityDocument::withTrashed()
			->where('opportunity_id', $opportunity->id)
			->whereIn('id', $ids);

		if (! $fromMedia) {
			$query->whereNotNull('deleted_at');
		}

		$docs = $query->get();

		if ($docs->isEmpty()) {
			return $this->redirectAfterBulk($opportunity, $request)
				->with('error', 'No files found to delete.');
		}

		$count = 0;

		foreach ($docs as $doc) {
			// delete physical file first
			if ($doc->disk && $doc->path) {
				Storage::disk($doc->disk)->delete($doc->path);
			}

			$doc->forceDelete();
		}

		return $this->redirectAfterBulk($opportunity, $request)
			->with('success', "{$docs->count()} archived file(s) permanently deleted.");
	}
	
    public function restore(Opportunity $opportunity, $document)
	{
		$doc = OpportunityDocument::withTrashed()
			->where('opportunity_id', $opportunity->id)
			->where('id', $document)
			->firstOrFail();

		$doc->restore();

		return back()->with('success', 'Document restored.');
	}

    public function forceDestroy(Opportunity $opportunity, $document)
    {
        $doc = OpportunityDocument::withTrashed()
            ->where('opportunity_id', $opportunity->id)
            ->where('id', $document)
            ->firstOrFail();

        // Remove the physical file too:
        if ($doc->disk && $doc->path) {
            Storage::disk($doc->disk)->delete($doc->path);
        }

        $doc->forceDelete();

        return back()->with('success', 'Document permanently deleted.');
    }

    private function redirectAfterBulk(Opportunity $opportunity, Request $request): \Illuminate\Http\RedirectResponse
    {
        if ($request->input('redirect_to') === 'media') {
            return redirect()->route('pages.opportunities.media.index', $opportunity->id);
        }
        return redirect()->route('pages.opportunities.documents.index', $opportunity->id);
    }

    // ── Document generation ───────────────────────────────────────────────────

    public function generate(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'template_id' => ['required', 'exists:document_templates,id'],
            'sale_id'     => ['nullable', 'exists:sales,id'],
        ]);

        $template = DocumentTemplate::findOrFail($request->template_id);

        $sale = null;
        if ($template->needs_sale) {
            $request->validate(['sale_id' => ['required', 'exists:sales,id']]);
            $sale = Sale::findOrFail($request->sale_id);
            abort_if((int) $sale->opportunity_id !== (int) $opportunity->id, 403);
        }

        $service = new DocumentTemplateService();
        $body    = $service->render($template, $opportunity, $sale);

        $pdf = Pdf::loadView('pdf.document-template', [
            'template'    => $template,
            'body'        => $body,
            'opportunity' => $opportunity,
            'sale'        => $sale,
        ])->setPaper('letter', 'portrait');

        $pdfContent = $pdf->output();

        $slug      = Str::slug($template->name);
        $timestamp = now()->format('Ymd_His');
        $filename  = "doc_{$slug}_{$timestamp}.pdf";
        $path      = "opportunities/{$opportunity->id}/{$filename}";

        Storage::disk('public')->put($path, $pdfContent);

        $doc = OpportunityDocument::create([
            'opportunity_id' => $opportunity->id,
            'template_id'    => $template->id,
            'disk'           => 'public',
            'path'           => $path,
            'original_name'  => $template->name . '.pdf',
            'stored_name'    => $filename,
            'mime_type'      => 'application/pdf',
            'extension'      => 'pdf',
            'size_bytes'     => strlen($pdfContent),
            'category'       => 'generated_document',
            'created_by'     => Auth::id(),
            'updated_by'     => Auth::id(),
        ]);

        return redirect()
            ->route('pages.opportunities.documents.index', $opportunity)
            ->with('success', '"' . $template->name . '" generated successfully.')
            ->with('generated_doc_id', $doc->id);
    }

    public function reprint(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);
        abort_if($document->category !== 'generated_document', 404);
        abort_if(! Storage::disk($document->disk)->exists($document->path), 404);

        $file = Storage::disk($document->disk)->get($document->path);

        return response($file, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertBelongsToOpportunity(Opportunity $opportunity, OpportunityDocument $document): void
    {
        if ((int) $document->opportunity_id !== (int) $opportunity->id) {
            abort(404);
        }
    }
}
