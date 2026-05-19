<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductStylePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductStylePhotoController extends Controller
{
    public function store(Request $request, ProductLine $product_line, $styleId)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);

        $request->validate([
            'photo' => ['required', 'image', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ]);

        if ($style->photos()->count() >= 3) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Maximum 3 photos per style.'], 422);
            }
            return back()->with('photo_error', 'Maximum 3 photos per style.');
        }

        $file = $request->file('photo');
        $path = $file->store("product-style-photos/{$style->id}", 'public');

        $isFirst = $style->photos()->count() === 0;

        $photo = $style->photos()->create([
            'file_path'   => $path,
            'is_primary'  => $isFirst,
            'sort_order'  => $style->photos()->max('sort_order') + 1,
            'uploaded_by' => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'id'          => $photo->id,
                'url'         => $photo->url,
                'is_primary'  => (bool) $photo->is_primary,
                'count'       => $style->photos()->count(),
                'delete_url'  => route('admin.product_styles.photos.destroy', [$product_line, $style, $photo]),
                'primary_url' => route('admin.product_styles.photos.primary', [$product_line, $style, $photo]),
            ]);
        }

        $redirectTo = $request->input('_redirect_back');

        return $redirectTo
            ? redirect($redirectTo)->with('success', 'Photo uploaded.')
            : back()->with('editStyle', $style)->with('photo_tab', true)->with('success', 'Photo uploaded.');
    }

    public function destroy(Request $request, ProductLine $product_line, $styleId, ProductStylePhoto $photo)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);

        abort_if($photo->product_style_id !== $style->id, 404);

        Storage::disk('public')->delete($photo->file_path);

        $wasPrimary = $photo->is_primary;
        $photo->delete();

        if ($wasPrimary) {
            $style->photos()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success'        => true,
                'count'          => $style->photos()->count(),
                'new_primary_id' => $wasPrimary ? $style->photos()->where('is_primary', true)->first()?->id : null,
            ]);
        }

        $redirectTo = $request->input('_redirect_back');

        return $redirectTo
            ? redirect($redirectTo)->with('success', 'Photo deleted.')
            : back()->with('editStyle', $style)->with('photo_tab', true)->with('success', 'Photo deleted.');
    }

    public function setPrimary(Request $request, ProductLine $product_line, $styleId, ProductStylePhoto $photo)
    {
        $style = $product_line->productStyles()->findOrFail($styleId);

        abort_if($photo->product_style_id !== $style->id, 404);

        $style->photos()->update(['is_primary' => false]);
        $photo->update(['is_primary' => true]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'primary_id' => $photo->id]);
        }

        $redirectTo = $request->input('_redirect_back');

        return $redirectTo
            ? redirect($redirectTo)->with('success', 'Primary photo updated.')
            : back()->with('editStyle', $style)->with('photo_tab', true)->with('success', 'Primary photo updated.');
    }
}
