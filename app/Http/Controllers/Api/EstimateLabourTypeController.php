<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabourType;
use Illuminate\Http\Request;

class EstimateLabourTypeController extends Controller
{
    public function index(Request $request)
    {
        // Optional: support simple search later
        $q = trim((string) $request->query('q', ''));

        $query = LabourType::query()->select('id', 'name');

        if ($q !== '') {
            $query->where('name', 'like', "%{$q}%");
        }

        return response()->json(
            $query->orderBy('name')->get()
        );
    }
}