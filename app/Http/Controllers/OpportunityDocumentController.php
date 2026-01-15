<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\OpportunityDocumentLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $documentsQuery->where('category', 'documents');
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

    return view('pages.opportunities.documents.index', [
        'opportunity'   => $opportunity,
        'documents'     => $documents,
        'labels'        => $labels,
        'type'          => $type,
        'labelId'       => $labelId,
        'labelText'     => $labelText,
        'showArchived'  => $showArchived,
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

        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType() ?? '';

            // Category values (plural)
            $category = (str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/'))
                ? 'media'
                : 'documents';

            // Save in public disk so it’s accessible via /storage/...
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
                'label_id'       => $labelId,
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

		return redirect()
			->route('pages.opportunities.documents.index', $opportunity->id)
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

		// Only archived docs that belong to this opportunity
		$docs = OpportunityDocument::withTrashed()
			->where('opportunity_id', $opportunity->id)
			->whereIn('id', $ids)
			->whereNotNull('deleted_at') // archived only
			->get();

		if ($docs->isEmpty()) {
			return redirect()
				->route('pages.opportunities.documents.index', $opportunity->id)
				->with('error', 'No archived files found to delete.');
		}

		$count = 0;

		foreach ($docs as $doc) {
			// delete physical file first
			if ($doc->disk && $doc->path) {
				Storage::disk($doc->disk)->delete($doc->path);
			}

			$doc->forceDelete();
		}

		return redirect()
			->route('pages.opportunities.documents.index', $opportunity->id)
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

    public function forceDestroy(Opportunity $opportunity, OpportunityDocument $document)
    {
        $this->assertBelongsToOpportunity($opportunity, $document);

        // Remove the physical file too:
        if ($document->disk && $document->path) {
            Storage::disk($document->disk)->delete($document->path);
        }

        $document->forceDelete();

        return back()->with('success', 'Document permanently deleted.');
    }

    private function assertBelongsToOpportunity(Opportunity $opportunity, OpportunityDocument $document): void
    {
        if ((int) $document->opportunity_id !== (int) $opportunity->id) {
            abort(404);
        }
    }
}
