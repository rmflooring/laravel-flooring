<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstimateLabourItemController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'labour_type_id' => ['required', 'integer'],
        ]);

        $items = DB::table('labour_items')
            ->join('unit_measures', 'unit_measures.id', '=', 'labour_items.unit_measure_id')
            ->where('labour_items.labour_type_id', (int) $request->labour_type_id)
            ->where('labour_items.status', 'Active')
            ->select([
    'labour_items.id',
    'labour_items.description',
    'unit_measures.code as unit_code',
    'labour_items.sell',
    'labour_items.notes',
])
            ->orderBy('labour_items.description')
            ->get();

        return response()->json($items);
    }
}