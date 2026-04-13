<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\FlooringSignOff;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\OpportunityDocumentLabel;
use App\Models\Sale;
use App\Services\DocumentStorageService;
use App\Services\DocumentTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class OpportunityDocumentController extends Controller
{

public function index(Opportunity $opportunity, Request $request)
{
    $type         = $request->get('type'); // all | documents | media
    $labelId      = $request->get('label_id');
    $labelText    = $request->get('label_text');
    $search       = trim($request->get('search', ''));
    $showArchived = $request->boolean('show_archived');

    // Shared base constraints (everything except type filter)
    $base = function ($q) use ($showArchived, $labelId, $labelText, $search) {
        if (!$showArchived) {
            $q->whereNull('deleted_at');
        }
        if ($labelId) {
            $q->where('label_id', $labelId);
        }
        if ($labelText) {
            $q->where('label_text', $labelText);
        }
        if ($search !== '') {
            $q->where(function ($q) use ($search) {
                $q->where('original_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
    };

    // Per-category counts (ignores the active type tab so tabs always show totals)
    $catCounts = $opportunity->documents()
        ->withTrashed()
        ->tap($base)
        ->selectRaw('category, count(*) as cnt')
        ->groupBy('category')
        ->pluck('cnt', 'category');

    $counts = [
        'all'       => $catCounts->sum(),
        'documents' => ($catCounts['documents'] ?? 0) + ($catCounts['generated_document'] ?? 0),
        'media'     => $catCounts['media'] ?? 0,
    ];

    // Main query (applies type filter on top of base)
    $documentsQuery = $opportunity->documents()
        ->withTrashed()
        ->with('label')
        ->tap($base)
        ->orderByDesc('created_at');

    if ($type === 'documents') {
        $documentsQuery->whereIn('category', ['documents', 'generated_document']);
    } elseif ($type === 'media') {
        $documentsQuery->where('category', 'media');
    }

    $documents = $documentsQuery->paginate(25)->withQueryString();

    $labels = OpportunityDocumentLabel::where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name']);

    $activeTemplates = DocumentTemplate::where('is_active', true)
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get(['id', 'name', 'description', 'needs_sale', 'special_flow']);

    $opportunitySales = $opportunity->sales()
        ->orderByDesc('id')
        ->get(['id', 'sale_number', 'job_name']);

    $signOffs = FlooringSignOff::withTrashed()
        ->where('opportunity_id', $opportunity->id)
        ->with('sale:id,sale_number,job_name')
        ->latest()
        ->get();

    return view('pages.opportunities.documents.index', [
        'opportunity'      => $opportunity,
        'documents'        => $documents,
        'labels'           => $labels,
        'type'             => $type,
        'labelId'          => $labelId,
        'labelText'        => $labelText,
        'search'           => $search,
        'showArchived'     => $showArchived,
        'counts'           => $counts,
        'activeTemplates'  => $activeTemplates,
        'opportunitySales' => $opportunitySales,
        'signOffs'         => $signOffs,
    ]);
}


    public function store(Opportunity $opportunity, Request $request)
    {
        $request->validate([
            'files'          => ['required', 'array', 'min:1'],
            'files.*'        => ['file', 'max:512000'], // 500 MB per file
            'label_ids'      => ['nullable', 'array'],
            'label_ids.*'    => ['nullable', 'integer'],   // existence checked in code below
            'descriptions'   => ['nullable', 'array'],
            'descriptions.*' => ['nullable', 'string'],
            'label_id'       => ['nullable', 'integer'],   // existence checked in code below
            'description'    => ['nullable', 'string'],
        ]);

        try {
            $userId = Auth::id();

            $labelIds    = $request->input('label_ids', []);
            $descriptions = $request->input('descriptions', []);
            $globalLabel = $request->input('label_id');
            $globalDesc  = $request->input('description');

            $photosLabelId = OpportunityDocumentLabel::where('name', 'Photos')
                ->where('is_active', true)
                ->value('id');

            $mediaExtensions = ['jpg','jpeg','png','gif','webp','bmp','tiff','tif','heic','heif','avif','svg','mp4','mov','avi','mkv','webm','wmv','m4v','3gp'];

            foreach ($request->file('files', []) as $i => $file) {
                $mime = $file->getMimeType() ?? '';
                $ext  = strtolower($file->getClientOriginalExtension());

                $isMedia = str_starts_with($mime, 'image/')
                    || str_starts_with($mime, 'video/')
                    || in_array($ext, $mediaExtensions);

                $category = $isMedia ? 'media' : 'documents';

                // Per-file label takes priority, then global fallback, then auto-Photos for images/media
                $rawLabelId  = $labelIds[$i] ?? $globalLabel;
                $fileLabelId = ($rawLabelId && is_numeric($rawLabelId)) ? (int) $rawLabelId : null;
                if (! $fileLabelId && $isMedia) {
                    $fileLabelId = $photosLabelId;
                }

                $fileDesc = $descriptions[$i] ?? $globalDesc;

                $disk = DocumentStorageService::disk();
                $path = $file->store("opportunities/{$opportunity->storageFolderName()}", $disk);

                \Log::info('[docs] stored file', [
                    'opportunity_id' => $opportunity->id,
                    'path'           => $path,
                    'mime'           => $mime,
                    'original'       => $file->getClientOriginalName(),
                ]);

                // Generate thumbnail for images (skip SVG and video)
                $thumbnailPath = null;
                $isThumbableImage = str_starts_with($mime, 'image/') && $ext !== 'svg';
                if ($isThumbableImage) {
                    try {
                        $manager = new ImageManager(new Driver());
                        $image   = $manager->decodePath($file->getRealPath());
                        $image->scaleDown(width: 600);
                        $thumbContents = (string) $image->encodeUsingMediaType('image/jpeg', quality: 80);
                        $thumbnailPath = "opportunities/{$opportunity->storageFolderName()}/thumb_" . pathinfo(basename($path), PATHINFO_FILENAME) . '.jpg';
                        Storage::disk($disk)->put($thumbnailPath, $thumbContents);
                    } catch (\Throwable $e) {
                        \Log::warning('[docs] thumbnail generation failed', [
                            'path'    => $path,
                            'message' => $e->getMessage(),
                        ]);
                        $thumbnailPath = null;
                    }
                }

                OpportunityDocument::create([
                    'opportunity_id' => $opportunity->id,
                    'disk'           => $disk,
                    'path'           => $path,
                    'thumbnail_path' => $thumbnailPath,
                    'original_name'  => $file->getClientOriginalName(),
                    'stored_name'    => basename($path),
                    'mime_type'      => $mime,
                    'extension'      => $file->getClientOriginalExtension(),
                    'size_bytes'     => $file->getSize(),
                    'category'       => $category,
                    'label_id'       => $fileLabelId,
                    'description'    => $fileDesc,
                    'created_by'     => $userId,
                    'updated_by'     => $userId,
                ]);
            }

            $count = count($request->file('files', []));

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'count' => $count]);
            }

            return back()->with('success', $count === 1
                ? 'File uploaded successfully.'
                : "{$count} files uploaded successfully.");

        } catch (\Throwable $e) {
            \Log::error('[docs] upload failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Upload failed: ' . $e->getMessage()], 500);
            }

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
			if ($doc->disk && $doc->thumbnail_path) {
				Storage::disk($doc->disk)->delete($doc->thumbnail_path);
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
        if ($doc->disk && $doc->thumbnail_path) {
            Storage::disk($doc->disk)->delete($doc->thumbnail_path);
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

    // ── Document generation — new flow ────────────────────────────────────────

    public function createGenerated(Request $request, Opportunity $opportunity, DocumentTemplate $template)
    {
        abort_if(! $template->is_active, 404);

        // Special flow — redirect to sign-off wizard
        if ($template->special_flow === 'flooring_sign_off') {
            return redirect()->route('pages.opportunities.sign-offs.create', [
                'opportunity' => $opportunity->id,
                'sale_id'     => $request->get('sale_id'),
            ]);
        }

        $sale = null;
        if ($template->needs_sale && $request->get('sale_id')) {
            $sale = Sale::findOrFail($request->get('sale_id'));
            abort_if((int) $sale->opportunity_id !== (int) $opportunity->id, 403);
        }

        $service          = new DocumentTemplateService();
        $fields           = $service->getDefaultFields($template, $opportunity, $sale);
        $opportunitySales = $opportunity->sales()->orderByDesc('id')->get(['id', 'sale_number', 'job_name']);

        return view('pages.opportunities.documents.create-generated', [
            'opportunity'      => $opportunity,
            'template'         => $template,
            'fields'           => $fields,
            'sale'             => $sale,
            'opportunitySales' => $opportunitySales,
            'document'         => null,
            'tagLabels'        => DocumentTemplateService::TAG_LABELS,
        ]);
    }

    public function storeGenerated(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'template_id'   => ['required', 'exists:document_templates,id'],
            'sale_id'       => ['nullable', 'exists:sales,id'],
            'document_name' => ['required', 'string', 'max:255'],
            'fields'        => ['nullable', 'array'],
        ]);

        $template = DocumentTemplate::findOrFail($request->template_id);

        $sale = null;
        if ($request->sale_id) {
            $sale = Sale::findOrFail($request->sale_id);
            abort_if((int) $sale->opportunity_id !== (int) $opportunity->id, 403);
        }

        $fields = $this->sanitizeFields($request->input('fields', []));

        $service      = new DocumentTemplateService();
        $renderedBody = $service->renderFromFields($template, $fields, $sale);

        $doc = OpportunityDocument::create([
            'opportunity_id' => $opportunity->id,
            'template_id'    => $template->id,
            'sale_id'        => $sale?->id,
            'original_name'  => $request->document_name,
            'category'       => 'generated_document',
            'document_fields'=> $fields,
            'rendered_body'  => $renderedBody,
            'created_by'     => Auth::id(),
            'updated_by'     => Auth::id(),
        ]);

        return redirect()
            ->route('pages.opportunities.documents.show-generated', [$opportunity->id, $doc->id])
            ->with('success', '"' . $request->document_name . '" saved successfully.');
    }

    public function showGenerated(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);
        abort_if($document->category !== 'generated_document', 404);
        abort_if(! $document->rendered_body, 404);

        $template = DocumentTemplate::find($document->template_id);

        return view('pages.opportunities.documents.show-generated', compact('opportunity', 'document', 'template'));
    }

    public function editGenerated(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);
        abort_if($document->category !== 'generated_document', 404);
        abort_if(! $document->rendered_body, 404);

        $template = DocumentTemplate::findOrFail($document->template_id);
        $fields   = $document->document_fields ?? [];

        $sale = null;
        if ($document->sale_id) {
            $sale = Sale::find($document->sale_id);
        }

        $opportunitySales = $opportunity->sales()->orderByDesc('id')->get(['id', 'sale_number', 'job_name']);

        return view('pages.opportunities.documents.create-generated', [
            'opportunity'      => $opportunity,
            'template'         => $template,
            'fields'           => $fields,
            'sale'             => $sale,
            'opportunitySales' => $opportunitySales,
            'document'         => $document,
            'tagLabels'        => DocumentTemplateService::TAG_LABELS,
        ]);
    }

    public function updateGenerated(Request $request, Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);
        abort_if($document->category !== 'generated_document', 404);

        $request->validate([
            'sale_id'       => ['nullable', 'exists:sales,id'],
            'document_name' => ['required', 'string', 'max:255'],
            'fields'        => ['nullable', 'array'],
        ]);

        $template = DocumentTemplate::findOrFail($document->template_id);

        $sale = null;
        if ($request->sale_id) {
            $sale = Sale::findOrFail($request->sale_id);
            abort_if((int) $sale->opportunity_id !== (int) $opportunity->id, 403);
        }

        $fields = $this->sanitizeFields($request->input('fields', []));

        $service      = new DocumentTemplateService();
        $renderedBody = $service->renderFromFields($template, $fields, $sale);

        $document->update([
            'sale_id'         => $sale?->id,
            'original_name'   => $request->document_name,
            'document_fields' => $fields,
            'rendered_body'   => $renderedBody,
            'updated_by'      => Auth::id(),
        ]);

        return redirect()
            ->route('pages.opportunities.documents.show-generated', [$opportunity->id, $document->id])
            ->with('success', 'Document updated successfully.');
    }

    public function downloadPdf(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);
        abort_if($document->category !== 'generated_document', 404);
        abort_if(! $document->rendered_body, 404);

        $template = DocumentTemplate::find($document->template_id);
        $sale     = $document->sale_id ? Sale::find($document->sale_id) : null;

        $pdf = Pdf::loadView('pdf.document-template', [
            'template'    => $template,
            'body'        => $document->rendered_body,
            'opportunity' => $opportunity,
            'sale'        => $sale,
        ])->setPaper('letter', 'portrait');

        $filename = Str::slug($document->original_name) . '.pdf';

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    // ── Legacy generate (kept for backward compat with old stored PDFs) ────────

    public function reprint(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);
        abort_if($document->category !== 'generated_document', 404);

        // New-style documents: redirect to show page
        if ($document->rendered_body) {
            return redirect()->route('pages.opportunities.documents.show-generated', [$opportunity->id, $document->id]);
        }

        // Legacy: stream stored PDF file
        abort_if(! Storage::disk($document->disk)->exists($document->path), 404);
        $file = Storage::disk($document->disk)->get($document->path);

        return response($file, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function sanitizeFields(array $fields): array
    {
        $allowed = array_map(
            fn($tag) => trim($tag, '{}'),
            array_merge(\App\Models\DocumentTemplate::OPPORTUNITY_TAGS, \App\Models\DocumentTemplate::SALE_TAGS)
        );

        return array_intersect_key($fields, array_flip($allowed));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertBelongsToOpportunity(Opportunity $opportunity, OpportunityDocument $document): void
    {
        if ((int) $document->opportunity_id !== (int) $opportunity->id) {
            abort(404);
        }
    }
}
