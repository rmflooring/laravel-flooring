<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\Rfm;
use App\Services\DocumentStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RfmController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $query = Rfm::with(['parentCustomer', 'jobSiteCustomer', 'opportunity.projectManager', 'estimator'])
            ->whereNull('deleted_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('parentCustomer', fn ($q) => $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"))
                  ->orWhereHas('opportunity', fn ($q) => $q->where('job_no', 'like', "%{$search}%"));
            });
        }

        if ($status === 'active') {
            $query->whereIn('status', ['pending', 'confirmed']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $rfms = $query->orderByDesc('scheduled_at')->paginate(25)->withQueryString();

        return view('mobile.rfms.index', compact('rfms', 'search', 'status'));
    }

    public function show(Rfm $rfm)
    {
        $rfm->load([
            'opportunity.projectManager',
            'estimator',
            'parentCustomer',
            'jobSiteCustomer',
        ]);

        $opportunity = $rfm->opportunity;

        return view('mobile.rfms.show', compact('rfm', 'opportunity'));
    }

    public function uploadPhotos(Rfm $rfm, Request $request)
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['required', 'image', 'max:20480'],
        ]);

        $opportunity = Opportunity::findOrFail($rfm->opportunity_id);

        $userId = auth()->id();
        $count  = 0;

        foreach ($request->file('files', []) as $file) {
            $mime = $file->getMimeType() ?? '';
            $disk = DocumentStorageService::disk();
            $path = $file->store("opportunities/{$opportunity->storageFolderName()}", $disk);

            OpportunityDocument::create([
                'opportunity_id' => $opportunity->id,
                'disk'           => $disk,
                'path'           => $path,
                'original_name'  => $file->getClientOriginalName(),
                'stored_name'    => basename($path),
                'mime_type'      => $mime,
                'extension'      => $file->getClientOriginalExtension(),
                'size_bytes'     => $file->getSize(),
                'category'       => 'media',
                'created_by'     => $userId,
                'updated_by'     => $userId,
            ]);

            $count++;
        }

        Log::info('[Mobile RFM] Photos uploaded', [
            'rfm_id'         => $rfm->id,
            'opportunity_id' => $opportunity->id,
            'count'          => $count,
            'user_id'        => $userId,
        ]);

        $photosUrl = route('mobile.opportunity.photos', $opportunity);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $photosUrl, 'count' => $count]);
        }

        return redirect()->to($photosUrl)
            ->with('success', $count . ' photo' . ($count !== 1 ? 's' : '') . ' uploaded.');
    }
}
