<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityDocument;
use App\Models\Rfm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RfmController extends Controller
{
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
            $path = $file->store("opportunities/{$opportunity->id}", 'public');

            OpportunityDocument::create([
                'opportunity_id' => $opportunity->id,
                'disk'           => 'public',
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

        return redirect()
            ->route('mobile.opportunity.photos', $opportunity)
            ->with('success', $count . ' photo' . ($count !== 1 ? 's' : '') . ' uploaded.');
    }
}
