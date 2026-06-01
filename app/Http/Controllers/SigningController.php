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
            return view('signing.already-signed', compact('signingRequest'));
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

        return view('signing.show', compact('signingRequest'));
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

        return view('signing.thank-you', compact('signingRequest'));
    }
}
