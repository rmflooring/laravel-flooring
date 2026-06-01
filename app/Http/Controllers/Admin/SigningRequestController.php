<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\SignatureReminderMail;
use App\Models\DocumentSigningRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SigningRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'status']);

        $query = DocumentSigningRequest::query()->latest();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('client_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('client_email', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $signingRequests = $query->paginate(25)->withQueryString();

        $counts = [
            'pending'   => DocumentSigningRequest::where('status', 'pending')->count(),
            'signed'    => DocumentSigningRequest::where('status', 'signed')->count(),
            'expired'   => DocumentSigningRequest::where('status', 'expired')->count(),
            'cancelled' => DocumentSigningRequest::where('status', 'cancelled')->count(),
        ];

        return view('admin.signing-requests.index', compact('signingRequests', 'counts', 'filters'));
    }

    public function cancel(DocumentSigningRequest $signingRequest)
    {
        abort_unless($signingRequest->isPending(), 422, 'Only pending requests can be cancelled.');

        $signingRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Signing request cancelled.');
    }

    public function resend(DocumentSigningRequest $signingRequest)
    {
        abort_unless($signingRequest->isPending(), 422, 'Only pending requests can be resent.');

        (new SignatureReminderMail($signingRequest))->send();

        $signingRequest->update([
            'reminder_sent_at' => now(),
            'reminder_count'   => $signingRequest->reminder_count + 1,
        ]);

        return back()->with('success', 'Reminder sent to ' . $signingRequest->client_email . '.');
    }

    public function download(DocumentSigningRequest $signingRequest)
    {
        abort_unless($signingRequest->isSigned(), 404);
        abort_unless(
            $signingRequest->signed_pdf_path &&
            Storage::disk('local')->exists($signingRequest->signed_pdf_path),
            404
        );

        return response(Storage::disk('local')->get($signingRequest->signed_pdf_path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="signed-' . $signingRequest->uuid . '.pdf"',
        ]);
    }
}
