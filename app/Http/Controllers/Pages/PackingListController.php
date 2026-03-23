<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\PackingList;
use App\Models\PickTicket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PackingListController extends Controller
{
    public function store(Request $request, PickTicket $pickTicket): RedirectResponse
    {
        if (! in_array($pickTicket->status, ['delivered', 'partially_delivered'])) {
            return back()->with('error', 'Packing lists can only be created for delivered pick tickets.');
        }

        // Only allow one packing list per pick ticket
        $existing = PackingList::where('pick_ticket_id', $pickTicket->id)->first();
        if ($existing) {
            return redirect()->route('pages.warehouse.packing-lists.show', $existing)
                ->with('info', 'A packing list already exists for this pick ticket.');
        }

        $packingList = PackingList::create([
            'pick_ticket_id' => $pickTicket->id,
            'sale_id'        => $pickTicket->sale_id,
            'notes'          => $request->input('notes'),
        ]);

        return redirect()->route('pages.warehouse.packing-lists.show', $packingList)
            ->with('success', 'Packing list ' . $packingList->pl_number . ' created.');
    }

    public function show(PackingList $packingList)
    {
        $packingList->load([
            'pickTicket.items.saleItem.room',
            'pickTicket.sale',
            'pickTicket.workOrder.installer',
            'pickTicket.creator',
            'sale',
            'creator',
        ]);

        return view('pages.warehouse.packing-lists.show', compact('packingList'));
    }

    public function pdf(PackingList $packingList)
    {
        $packingList->load([
            'pickTicket.items.saleItem.room',
            'pickTicket.sale',
            'pickTicket.workOrder.installer',
            'sale',
            'creator',
        ]);

        $pdf = Pdf::loadView('pdf.packing-list', compact('packingList'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('packing-list-' . $packingList->pl_number . '.pdf');
    }
}
