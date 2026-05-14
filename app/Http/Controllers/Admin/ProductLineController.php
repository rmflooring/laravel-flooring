<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\ProductType;
use App\Models\UnitMeasure;
use App\Models\Vendor;
use App\Services\ShopCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductLineController extends Controller
{
    /**
     * Display a listing of the product lines.
     */
public function index(Request $request)
{
    $query = ProductLine::query()
        ->with(['productType', 'vendorRelation']); // keep your view working

    // Search
    if ($request->filled('search')) {
        $search = trim((string) $request->search);

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('manufacturer', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%")
              ->orWhere('collection', 'like', "%{$search}%")
              ->orWhereHas('productType', function ($pt) use ($search) {
                  $pt->where('name', 'like', "%{$search}%");
              })
              ->orWhereHas('vendorRelation', function ($v) use ($search) {
                  $v->where('company_name', 'like', "%{$search}%");
              })
              ->orWhereHas('productStyles', function ($s) use ($search) {
                  $s->where('name', 'like', "%{$search}%")
                    ->orWhere('color', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('style_number', 'like', "%{$search}%")
                    ->orWhere('pattern', 'like', "%{$search}%");
              });
        });
    }

    // Filters — hide archived by default unless explicitly requested
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    } else {
        $query->where('status', '<>', 'archived');
    }

    if ($request->filled('product_type_id')) {
        $query->where('product_type_id', $request->product_type_id);
    }

    if ($request->filled('vendor_id')) {
        $query->where('vendor_id', $request->vendor_id);
    }

    // Per page
    $perPage = (int) $request->get('per_page', 15);
    if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
        $perPage = 15;
    }

    $lines = $query
        ->withCount(['estimateItems', 'saleItems'])
        ->orderBy('id', 'desc')
        ->paginate($perPage)
        ->withQueryString(); // critical: keeps filters while paging

    // Dropdown data for filters
    $productTypes = ProductType::orderBy('name')->get(['id', 'name']);
    $vendors      = Vendor::orderBy('company_name')->get(['id', 'company_name']);

    return view('admin.product_lines.index', compact('lines', 'productTypes', 'vendors', 'perPage'));
}

    /**
     * Show the form for creating a new product line.
     */
    public function create()
{
    $types   = ProductType::where('status', 'active')->orderBy('name')->with('soldByUnit')->get();
    $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);
    $units   = UnitMeasure::where('status', 'active')->orderBy('label')->get(['id', 'code', 'label']);

    return view('admin.product_lines.create', compact('types', 'vendors', 'units'));
}

    /**
     * Store a newly created product line in storage.
     */
    public function store(Request $request)
{
    $validated = $request->validate([
        'product_type_id' => 'required|exists:product_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive,dropped',
        'vendor_id' => 'nullable|exists:vendors,id',
        'manufacturer' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'collection' => 'nullable|string|max:255',
        'default_cost_price' => 'nullable|numeric|min:0',
        'default_sell_price' => 'nullable|numeric|min:0',
        'unit_id' => 'nullable|exists:unit_measures,id',
        'width' => 'nullable|numeric|min:0',
        'length' => 'nullable|numeric|min:0',
        'shop_visible'    => 'boolean',
        'shop_description' => 'nullable|string',
        'shop_show_price' => 'boolean',
        'photo'           => 'nullable|image|max:5120',
    ]);

    $validated['shop_visible']    = $request->boolean('shop_visible');
    $validated['shop_show_price'] = $request->boolean('shop_show_price');
    unset($validated['photo']);

    $line = ProductLine::create([
        ...$validated,
        'created_by' => Auth::id(),
    ]);

    if ($request->hasFile('photo')) {
        $path = $request->file('photo')->store("product-lines/{$line->id}", 'public');
        $line->update(['photo_path' => $path]);
    }

    app(ShopCacheService::class)->bustProductLine($line->id, $line->product_type_id);

    return redirect()->route('admin.product_lines.index')
        ->with('success', 'Product line created successfully.');
}

    /**
     * Show the form for editing the specified product line.
     */
public function edit(ProductLine $product_line)
{
    $types   = ProductType::where('status', 'active')->orderBy('name')->with('soldByUnit')->get();
    $vendors = Vendor::orderBy('company_name')->get(['id', 'company_name']);
    $units   = UnitMeasure::where('status', 'active')->orderBy('label')->get(['id', 'code', 'label']);

    return view('admin.product_lines.edit', compact('product_line', 'types', 'vendors', 'units'));
}

public function update(Request $request, ProductLine $product_line)
{
    $validated = $request->validate([
        'product_type_id' => 'required|exists:product_types,id',
        'name' => 'required|string|max:255',
        'status' => 'required|in:active,inactive,dropped',
        'vendor_id' => 'nullable|exists:vendors,id',
        'manufacturer' => 'nullable|string|max:255',
        'model' => 'nullable|string|max:255',
        'collection' => 'nullable|string|max:255',
        'default_cost_price' => 'nullable|numeric|min:0',
        'default_sell_price' => 'nullable|numeric|min:0',
        'unit_id' => 'nullable|exists:unit_measures,id',
        'width' => 'nullable|numeric|min:0',
        'length' => 'nullable|numeric|min:0',
        'shop_visible'    => 'boolean',
        'shop_description' => 'nullable|string',
        'shop_show_price' => 'boolean',
        'photo'           => 'nullable|image|max:5120',
        'remove_photo'    => 'nullable|boolean',
    ]);

    $validated['shop_visible']    = $request->boolean('shop_visible');
    $validated['shop_show_price'] = $request->boolean('shop_show_price');
    unset($validated['photo'], $validated['remove_photo']);

    if ($request->hasFile('photo')) {
        if ($product_line->photo_path) {
            Storage::disk('public')->delete($product_line->photo_path);
        }
        $validated['photo_path'] = $request->file('photo')->store("product-lines/{$product_line->id}", 'public');
    } elseif ($request->boolean('remove_photo') && $product_line->photo_path) {
        Storage::disk('public')->delete($product_line->photo_path);
        $validated['photo_path'] = null;
    }

    $product_line->update([
        ...$validated,
        'updated_by' => Auth::id(),
    ]);

    app(ShopCacheService::class)->bustProductLine($product_line->id, $product_line->product_type_id);

    return redirect()->route('admin.product_lines.index')
        ->with('success', 'Product line updated successfully.');
}

    public function importForm()
    {
        return view('admin.product_lines.import');
    }

    public function importTemplate()
    {
        $columns = [
            'row_type', 'line_name', 'product_type', 'vendor', 'manufacturer',
            'model', 'collection', 'default_cost_price', 'default_sell_price',
            'unit', 'width', 'length', 'line_status',
            'style_name', 'sku', 'style_number', 'color', 'pattern',
            'description', 'cost_price', 'sell_price', 'thickness', 'units_per',
            'use_box_qty', 'style_status',
        ];

        $sample = [
            ['LINE', 'Hardwood Collection', 'Hardwood', 'Shaw Floors', 'Shaw', 'HW-2024', 'Premium', '3.50', '7.99', 'SF', '3.25', '', 'active', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['STYLE', '', '', '', '', '', '', '', '', '', '', '', '', 'Natural Oak', 'OAK-001', '001', 'Natural', 'Plain', '', '3.50', '7.99', '0.75', '20', '0', 'active'],
            ['STYLE', '', '', '', '', '', '', '', '', '', '', '', '', 'Rustic Brown', 'OAK-002', '002', 'Brown', 'Rustic', '', '3.50', '7.99', '0.75', '20', '0', 'active'],
            ['LINE', 'Luxury Vinyl Plank', 'LVP', 'Mohawk', 'Mohawk', 'LVP-2024', 'Luxury Series', '2.50', '5.99', 'SF', '', '', 'active', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['STYLE', '', '', '', '', '', '', '', '', '', '', '', '', 'Grey Ash', 'GRY-001', 'GA1', 'Grey', '', '', '2.50', '5.99', '', '', '0', 'active'],
        ];

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product-import-template.csv"',
        ];

        $callback = function () use ($columns, $sample) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, $columns);
            foreach ($sample as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        $rawHeader = fgetcsv($handle);

        if (!$rawHeader) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'The CSV file is empty.']);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), $rawHeader);

        if (!in_array('row_type', $header)) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'Missing required column: row_type. Download the template and use it as a starting point.']);
        }

        $allRows = [];
        $rowNum  = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (!array_filter($data)) {
                continue; // skip blank lines
            }
            if (count($data) !== count($header)) {
                $allRows[] = ['num' => $rowNum, 'data' => null, 'parse_error' => "Row {$rowNum} has " . count($data) . " columns but the header has " . count($header) . "."];
                continue;
            }
            $allRows[] = ['num' => $rowNum, 'data' => array_combine($header, array_map('trim', $data))];
        }
        fclose($handle);

        // Build lookup tables (keyed by lowercase name)
        $productTypes   = ProductType::all()->keyBy(fn($t) => strtolower(trim($t->name)));
        $vendors        = Vendor::all()->keyBy(fn($v) => strtolower(trim($v->company_name)));
        $unitsByCode    = UnitMeasure::all()->keyBy(fn($u) => strtolower(trim($u->code)));
        $unitsByLabel   = UnitMeasure::all()->keyBy(fn($u) => strtolower(trim($u->label)));

        $errors          = [];
        $parsed          = [];
        $currentLineIdx  = null;

        foreach ($allRows as $row) {
            if (isset($row['parse_error'])) {
                $errors[] = $row['parse_error'];
                $currentLineIdx = null;
                continue;
            }

            $num  = $row['num'];
            $d    = $row['data'];
            $type = strtoupper($d['row_type'] ?? '');

            if ($type === 'LINE') {
                $lineName = $d['line_name'] ?? '';
                if ($lineName === '') {
                    $errors[] = "Row {$num}: LINE row is missing line_name.";
                    $currentLineIdx = null;
                    continue;
                }

                $ptKey = strtolower($d['product_type'] ?? '');
                if ($ptKey === '' || !isset($productTypes[$ptKey])) {
                    $errors[] = "Row {$num}: product_type '{$d['product_type']}' not found. It must match an existing product type name exactly.";
                    $currentLineIdx = null;
                    continue;
                }
                $productTypeId = $productTypes[$ptKey]->id;

                $vendorId  = null;
                $vendorKey = strtolower($d['vendor'] ?? '');
                if ($vendorKey !== '') {
                    if (!isset($vendors[$vendorKey])) {
                        $errors[] = "Row {$num}: vendor '{$d['vendor']}' not found. It must match an existing vendor company name exactly.";
                        $currentLineIdx = null;
                        continue;
                    }
                    $vendorId = $vendors[$vendorKey]->id;
                }

                // Duplicate check
                $dupQuery = ProductLine::where('name', $lineName)->where('product_type_id', $productTypeId);
                if ($vendorId) {
                    $dupQuery->where('vendor_id', $vendorId);
                }
                if ($dupQuery->exists()) {
                    $label = "'{$lineName}'" . ($d['vendor'] ? " (vendor: {$d['vendor']})" : '');
                    $errors[] = "Row {$num}: Product line {$label} already exists. Remove it from the CSV.";
                    $currentLineIdx = null;
                    continue;
                }

                $unitId  = null;
                $unitKey = strtolower($d['unit'] ?? '');
                if ($unitKey !== '') {
                    if (isset($unitsByCode[$unitKey])) {
                        $unitId = $unitsByCode[$unitKey]->id;
                    } elseif (isset($unitsByLabel[$unitKey])) {
                        $unitId = $unitsByLabel[$unitKey]->id;
                    } else {
                        $errors[] = "Row {$num}: unit '{$d['unit']}' not found. Use the unit code (e.g. SF, SY, EA).";
                        $currentLineIdx = null;
                        continue;
                    }
                }

                $lineStatus = $d['line_status'] ?? 'active';
                if (!in_array($lineStatus, ['active', 'inactive', 'dropped'], true)) {
                    $lineStatus = 'active';
                }

                $parsed[] = [
                    'row_num'            => $num,
                    'product_type_id'    => $productTypeId,
                    'name'               => $lineName,
                    'vendor_id'          => $vendorId,
                    'manufacturer'       => $d['manufacturer'] !== '' ? $d['manufacturer'] : null,
                    'model'              => $d['model'] !== '' ? $d['model'] : null,
                    'collection'         => $d['collection'] !== '' ? $d['collection'] : null,
                    'default_cost_price' => is_numeric($d['default_cost_price']) ? (float) $d['default_cost_price'] : null,
                    'default_sell_price' => is_numeric($d['default_sell_price']) ? (float) $d['default_sell_price'] : null,
                    'unit_id'            => $unitId,
                    'width'              => is_numeric($d['width']) ? (float) $d['width'] : null,
                    'length'             => is_numeric($d['length']) ? (float) $d['length'] : null,
                    'status'             => $lineStatus,
                    'styles'             => [],
                ];
                $currentLineIdx = count($parsed) - 1;

            } elseif ($type === 'STYLE') {
                if ($currentLineIdx === null) {
                    $errors[] = "Row {$num}: STYLE row has no valid preceding LINE row.";
                    continue;
                }

                $styleName = $d['style_name'] ?? '';
                if ($styleName === '') {
                    $errors[] = "Row {$num}: STYLE row is missing style_name.";
                    continue;
                }

                $styleStatus = $d['style_status'] ?? 'active';
                if (!in_array($styleStatus, ['active', 'inactive', 'dropped'], true)) {
                    $styleStatus = 'active';
                }

                $parsed[$currentLineIdx]['styles'][] = [
                    'name'         => $styleName,
                    'sku'          => $d['sku'] !== '' ? $d['sku'] : null,
                    'style_number' => $d['style_number'] !== '' ? $d['style_number'] : null,
                    'color'        => $d['color'] !== '' ? $d['color'] : null,
                    'pattern'      => $d['pattern'] !== '' ? $d['pattern'] : null,
                    'description'  => $d['description'] !== '' ? $d['description'] : null,
                    'cost_price'   => is_numeric($d['cost_price']) ? (float) $d['cost_price'] : null,
                    'sell_price'   => is_numeric($d['sell_price']) ? (float) $d['sell_price'] : null,
                    'thickness'    => is_numeric($d['thickness']) ? (float) $d['thickness'] : null,
                    'units_per'    => is_numeric($d['units_per']) ? (int) $d['units_per'] : null,
                    'use_box_qty'  => in_array(strtolower($d['use_box_qty'] ?? '0'), ['1', 'yes', 'true'], true) ? 1 : 0,
                    'status'       => $styleStatus,
                ];

            } elseif ($type !== '') {
                $errors[] = "Row {$num}: row_type must be LINE or STYLE, got '{$d['row_type']}'.";
            }
        }

        if (empty($parsed) && empty($errors)) {
            $errors[] = 'No LINE rows found in the CSV.';
        }

        if (!empty($errors)) {
            return back()->with('import_errors', $errors);
        }

        DB::transaction(function () use ($parsed) {
            foreach ($parsed as $lineData) {
                $styles = $lineData['styles'];
                unset($lineData['styles'], $lineData['row_num']);

                $line = ProductLine::create([...$lineData, 'created_by' => Auth::id()]);

                foreach ($styles as $styleData) {
                    $line->productStyles()->create([
                        ...$styleData,
                        'vendor_id'  => $line->vendor_id,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        $lineCount  = count($parsed);
        $styleCount = array_sum(array_map(fn($l) => count($l['styles']), $parsed));

        return redirect()->route('admin.product_lines.index')
            ->with('success', "Import successful: {$lineCount} product " . Str::plural('line', $lineCount) . " and {$styleCount} product " . Str::plural('style', $styleCount) . " created.");
    }

    public function destroy(ProductLine $product_line)
    {
        if ($product_line->hasActivity()) {
            return redirect()->route('admin.product_lines.index')
                ->with('error', 'This product line cannot be deleted — it has been used in estimates or sales.');
        }

        $product_line->delete();

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line permanently deleted.');
    }

    public function archive(ProductLine $product_line)
    {
        $product_line->update(['status' => 'archived', 'updated_by' => Auth::id()]);

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line archived.');
    }

    public function unarchive(ProductLine $product_line)
    {
        $product_line->update(['status' => 'inactive', 'updated_by' => Auth::id()]);

        return redirect()->route('admin.product_lines.index')
            ->with('success', 'Product line restored to inactive.');
    }
}
