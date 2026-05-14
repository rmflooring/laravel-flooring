<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\ProductType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ShopApiController extends Controller
{
    public function productTypes(): JsonResponse
    {
        $types = ProductType::where('status', 'active')
            ->whereHas('productLines', function ($q) {
                $q->where('status', 'active')->where('shop_visible', true);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($types);
    }

    public function productLines(Request $request): JsonResponse
    {
        $query = ProductLine::where('status', 'active')
            ->where('shop_visible', true)
            ->with([
                'productStyles' => function ($q) {
                    $q->where('status', 'active')
                      ->where('shop_visible', true)
                      ->with(['photos' => fn ($pq) => $pq->where('is_primary', true)->limit(1)])
                      ->orderBy('name')
                      ->limit(8)
                      ->select(['id', 'product_line_id', 'name', 'color']);
                },
            ]);

        if ($request->filled('type_id')) {
            $query->where('product_type_id', (int) $request->type_id);
        }

        $lines = $query->orderBy('name')->get()->map(function ($line) {
            return [
                'id'               => $line->id,
                'product_type_id'  => $line->product_type_id,
                'name'             => $line->name,
                'manufacturer'     => $line->manufacturer,
                'model'            => $line->model,
                'collection'       => $line->collection,
                'width'            => $line->width !== null ? (float) $line->width : null,
                'length'           => $line->length !== null ? (float) $line->length : null,
                'shop_description' => $line->shop_description,
                'shop_show_price'  => (bool) $line->shop_show_price,
                'photo_url'        => $line->photo_path ? Storage::url($line->photo_path) : null,
                'styles_preview'   => $line->productStyles->map(fn ($s) => [
                    'id'               => $s->id,
                    'name'             => $s->name,
                    'color'            => $s->color,
                    'primary_photo_url' => $s->photos->first() ? Storage::url($s->photos->first()->file_path) : null,
                ]),
            ];
        });

        return response()->json($lines);
    }

    public function productLineShow(int $id): JsonResponse
    {
        $line = ProductLine::where('status', 'active')
            ->where('shop_visible', true)
            ->with([
                'productStyles' => function ($q) {
                    $q->where('status', 'active')
                      ->where('shop_visible', true)
                      ->with('photos')
                      ->orderBy('name');
                },
            ])
            ->findOrFail($id);

        $data = [
            'id'               => $line->id,
            'product_type_id'  => $line->product_type_id,
            'name'             => $line->name,
            'manufacturer'     => $line->manufacturer,
            'model'            => $line->model,
            'collection'       => $line->collection,
            'width'            => $line->width !== null ? (float) $line->width : null,
            'length'           => $line->length !== null ? (float) $line->length : null,
            'shop_description' => $line->shop_description,
            'shop_show_price'  => (bool) $line->shop_show_price,
            'photo_url'        => $line->photo_path ? Storage::url($line->photo_path) : null,
            'styles'           => $line->productStyles->map(function ($style) {
                return [
                    'id'           => $style->id,
                    'name'         => $style->name,
                    'sku'          => $style->sku,
                    'style_number' => $style->style_number,
                    'color'        => $style->color,
                    'pattern'      => $style->pattern,
                    'description'  => $style->description,
                    'thickness'    => $style->thickness,
                    'sell_price'   => $style->sell_price !== null ? (float) $style->sell_price : null,
                    'photos'       => $style->photos->map(fn ($p) => [
                        'id'         => $p->id,
                        'url'        => Storage::url($p->file_path),
                        'is_primary' => (bool) $p->is_primary,
                        'sort_order' => $p->sort_order,
                    ]),
                ];
            }),
        ];

        return response()->json($data);
    }

    public function quoteRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'required|string|max:50',
            'sq_footage'        => 'nullable|numeric|min:0',
            'message'           => 'required|string|max:5000',
            'product_reference' => 'nullable|string|max:500',
        ]);

        $to = config('mail.from.address', 'reception@rmflooring.ca');

        Mail::raw($this->buildQuoteEmail($validated), function ($msg) use ($validated, $to) {
            $msg->to($to)
                ->replyTo($validated['email'], trim("{$validated['first_name']} {$validated['last_name']}"))
                ->subject('New Quote Request — shop.rmflooring.ca');
        });

        return response()->json(['message' => 'Quote request received. We will be in touch shortly.']);
    }

    public function sampleRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|max:255',
            'mailing_address'  => 'required|string|max:1000',
            'product_style_id' => 'required|integer|exists:product_styles,id',
        ]);

        $style = ProductStyle::with('productLine')->findOrFail($validated['product_style_id']);

        $to = config('mail.from.address', 'reception@rmflooring.ca');

        Mail::raw($this->buildSampleEmail($validated, $style), function ($msg) use ($validated, $to) {
            $msg->to($to)
                ->replyTo($validated['email'], trim("{$validated['first_name']} {$validated['last_name']}"))
                ->subject('New Sample Request — shop.rmflooring.ca');
        });

        return response()->json(['message' => 'Sample request received. We will mail your sample shortly.']);
    }

    private function buildQuoteEmail(array $data): string
    {
        $lines = [
            'New quote request from shop.rmflooring.ca',
            str_repeat('-', 40),
            "Name:        {$data['first_name']} {$data['last_name']}",
            "Email:       {$data['email']}",
            "Phone:       {$data['phone']}",
        ];

        if (!empty($data['sq_footage'])) {
            $lines[] = "Sq Footage:  {$data['sq_footage']}";
        }

        if (!empty($data['product_reference'])) {
            $lines[] = "Product Ref: {$data['product_reference']}";
        }

        $lines[] = '';
        $lines[] = 'Message:';
        $lines[] = $data['message'];

        return implode("\n", $lines);
    }

    private function buildSampleEmail(array $data, ProductStyle $style): string
    {
        $lineName = $style->productLine->name ?? 'Unknown';
        $lines = [
            'New sample request from shop.rmflooring.ca',
            str_repeat('-', 40),
            "Name:            {$data['first_name']} {$data['last_name']}",
            "Email:           {$data['email']}",
            "Mailing Address: {$data['mailing_address']}",
            '',
            "Product Style:   {$style->name} (ID: {$style->id})",
            "SKU:             {$style->sku}",
            "Product Line:    {$lineName}",
        ];

        return implode("\n", $lines);
    }
}
