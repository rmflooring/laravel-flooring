<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurchaseOrderDocumentController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt'],
        ]);

        $disk   = 'public';
        $folder = 'purchase-orders/' . Str::slug($purchaseOrder->po_number);

        foreach ($request->file('files') as $file) {
            $ext      = $file->getClientOriginalExtension();
            $stored   = Str::uuid() . '.' . $ext;
            $path     = $folder . '/' . $stored;

            Storage::disk($disk)->putFileAs($folder, $file, $stored);

            PurchaseOrderDocument::create([
                'purchase_order_id' => $purchaseOrder->id,
                'disk'              => $disk,
                'path'              => $path,
                'original_name'     => $file->getClientOriginalName(),
                'mime_type'         => $file->getMimeType(),
                'extension'         => $ext,
                'size_bytes'        => $file->getSize(),
                'uploaded_by'       => auth()->id(),
            ]);
        }

        return back()->with('success', 'Document(s) uploaded successfully.');
    }

    public function destroy(PurchaseOrder $purchaseOrder, PurchaseOrderDocument $document)
    {
        abort_unless($document->purchase_order_id === $purchaseOrder->id, 403);

        Storage::disk($document->disk)->delete($document->path);
        $document->forceDelete();

        return back()->with('success', 'Document removed.');
    }

    public function download(PurchaseOrder $purchaseOrder, PurchaseOrderDocument $document)
    {
        abort_unless($document->purchase_order_id === $purchaseOrder->id, 403);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->response(
            $document->path,
            $document->original_name,
            ['Content-Disposition' => 'inline; filename="' . $document->original_name . '"']
        );
    }
}
