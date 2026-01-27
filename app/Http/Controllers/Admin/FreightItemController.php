<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FreightItem;
use Illuminate\Http\Request;

class FreightItemController extends Controller
{
    public function index()
    {
        $items = FreightItem::orderBy('description')->get();
        return view('admin.freight_items.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'sell_price'  => ['nullable', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string'],
        ]);

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        FreightItem::create($data);

        return redirect()->back()->with('success', 'Freight item created.');
    }

    public function update(Request $request, FreightItem $freightItem)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'cost_price'  => ['nullable', 'numeric', 'min:0'],
            'sell_price'  => ['nullable', 'numeric', 'min:0'],
            'notes'       => ['nullable', 'string'],
        ]);

        $data['updated_by'] = auth()->id();

        $freightItem->update($data);

        return redirect()->back()->with('success', 'Freight item updated.');
    }

    public function apiIndex()
    {
        return FreightItem::orderBy('description')->get([
            'id',
            'description',
            'sell_price',
        ]);
    }
}
