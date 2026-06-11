<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BillDocumentController extends Controller
{
    public function store(Request $request, Bill $bill)
    {
        $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt'],
        ]);

        self::saveFiles($request->file('files'), $bill);

        return back()->with('success', 'Document(s) uploaded successfully.');
    }

    public function destroy(Bill $bill, BillDocument $document)
    {
        abort_unless($document->bill_id === $bill->id, 403);

        Storage::disk($document->disk)->delete($document->path);
        $document->forceDelete();

        return back()->with('success', 'Document removed.');
    }

    public function download(Bill $bill, BillDocument $document)
    {
        abort_unless($document->bill_id === $bill->id, 403);
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->response(
            $document->path,
            $document->original_name,
            ['Content-Disposition' => 'inline; filename="' . $document->original_name . '"']
        );
    }

    public static function saveFiles(array $files, Bill $bill): void
    {
        $disk   = 'public';
        $folder = 'bills/' . $bill->id;

        foreach ($files as $file) {
            $ext    = $file->getClientOriginalExtension();
            $stored = Str::uuid() . '.' . $ext;
            $path   = $folder . '/' . $stored;

            Storage::disk($disk)->putFileAs($folder, $file, $stored);

            BillDocument::create([
                'bill_id'       => $bill->id,
                'disk'          => $disk,
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'extension'     => $ext,
                'size_bytes'    => $file->getSize(),
                'uploaded_by'   => auth()->id(),
            ]);
        }
    }
}
