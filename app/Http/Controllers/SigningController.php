<?php

namespace App\Http\Controllers;

use App\Mail\DocumentSignedAdminMail;
use App\Mail\DocumentSignedClientMail;
use App\Models\DocumentSigningRequest;
use App\Services\DocumentSigningRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SigningController extends Controller
{
    public function show(string $uuid)
    {
        $signingRequest = DocumentSigningRequest::where('uuid', $uuid)->firstOrFail();

        if ($signingRequest->isSigned() || $signingRequest->isCancelled()) {
            // Part of a batch? Point them at whatever's still left to sign
            $nextRequest = $signingRequest->nextInBatch();

            return view('signing.already-signed', compact('signingRequest', 'nextRequest'));
        }

        if ($signingRequest->isExpired() || $signingRequest->expires_at->isPast()) {
            return view('signing.expired', compact('signingRequest'));
        }

        if (! $signingRequest->viewed_at) {
            $log               = $signingRequest->audit_log ?? [];
            $log['viewed_at']  = now()->toIso8601String();
            $log['user_agent'] = request()->userAgent();
            $signingRequest->update([
                'viewed_at' => now(),
                'audit_log' => $log,
            ]);
        }

        // "Document 2 of 3" when several documents were sent together
        $batchMembers = $signingRequest->batchMembers();
        $batchIndex   = $batchMembers->search(fn ($r) => $r->id === $signingRequest->id) + 1;
        $batchCount   = $batchMembers->count();

        return view('signing.show', compact('signingRequest', 'batchIndex', 'batchCount'));
    }

    public function document(string $uuid)
    {
        $signingRequest = DocumentSigningRequest::where('uuid', $uuid)->firstOrFail();

        abort_unless(
            $signingRequest->isPending() && $signingRequest->expires_at->isFuture(),
            410,
            'This document is no longer available.'
        );
        abort_unless(
            $signingRequest->pending_pdf_path &&
            Storage::disk('local')->exists($signingRequest->pending_pdf_path),
            404
        );

        return response(Storage::disk('local')->get($signingRequest->pending_pdf_path), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="document.pdf"',
        ]);
    }

    public function sign(string $uuid, Request $request)
    {
        $signingRequest = DocumentSigningRequest::where('uuid', $uuid)->firstOrFail();

        if (! $signingRequest->isViewable()) {
            return redirect()->route('sign.show', $uuid);
        }

        $request->validate([
            'signature_data' => ['required', 'string'],
            'signature_type' => ['required', 'in:drawn,typed'],
            'agreed'         => ['required', 'accepted'],
        ]);

        $service    = app(DocumentSigningRequestService::class);
        $signedPath = $service->stampSignature(
            $signingRequest,
            $request->signature_data,
            $request->signature_type,
        );

        $log                    = $signingRequest->audit_log ?? [];
        $log['signed_at']       = now()->toIso8601String();
        $log['signature_type']  = $request->signature_type;
        $log['user_agent_sign'] = $request->userAgent();

        $signingRequest->update([
            'status'          => 'signed',
            'signed_at'       => now(),
            'signature_type'  => $request->signature_type,
            'signed_pdf_path' => $signedPath,
            'audit_log'       => $log,
        ]);

        (new DocumentSignedClientMail($signingRequest))->send();
        (new DocumentSignedAdminMail($signingRequest))->send();

        // Several documents sent together — send them on to the next one
        $nextRequest    = $signingRequest->nextInBatch();
        $remainingCount = $signingRequest->isBatched()
            ? DocumentSigningRequest::where('batch_uuid', $signingRequest->batch_uuid)
                ->where('status', 'pending')->where('expires_at', '>', now())->count()
            : 0;

        return view('signing.thank-you', compact('signingRequest', 'nextRequest', 'remainingCount'));
    }
}
