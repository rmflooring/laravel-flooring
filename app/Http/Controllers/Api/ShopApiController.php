<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\ProductLine;
use App\Models\ProductStyle;
use App\Models\ProductType;
use App\Models\Setting;
use App\Services\EmailTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                'unit',
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
                'unit_code'        => $line->unit?->code,
                'unit_label'       => $line->unit?->label,
                'shop_description' => $line->shop_description,
                'shop_show_price'  => (bool) $line->shop_show_price,
                'photo_url'        => $line->photo_path ? url(Storage::url($line->photo_path)) : null,
                'styles_preview'   => $line->productStyles->map(fn ($s) => [
                    'id'               => $s->id,
                    'name'             => $s->name,
                    'color'            => $s->color,
                    'primary_photo_url' => $s->photos->first() ? url(Storage::url($s->photos->first()->file_path)) : null,
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
                'unit',
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
            'unit_code'        => $line->unit?->code,
            'unit_label'       => $line->unit?->label,
            'shop_description' => $line->shop_description,
            'shop_show_price'  => (bool) $line->shop_show_price,
            'photo_url'        => $line->photo_path ? url(Storage::url($line->photo_path)) : null,
            'styles'           => $line->productStyles->map(function ($style) {
                return [
                    'id'             => $style->id,
                    'name'           => $style->name,
                    'sku'            => $style->sku,
                    'style_number'   => $style->style_number,
                    'color'          => $style->color,
                    'pattern'        => $style->pattern,
                    'description'    => $style->description,
                    'thickness'      => $style->thickness,
                    'sell_price'     => $style->sell_price !== null ? (float) $style->sell_price : null,
                    'units_per'      => $style->units_per !== null ? (float) $style->units_per : null,
                    'use_box_qty'    => (bool) $style->use_box_qty,
                    'shop_show_price' => (bool) $style->shop_show_price,
                    'photos'       => $style->photos->map(fn ($p) => [
                        'id'         => $p->id,
                        'url'        => url(Storage::url($p->file_path)),
                        'is_primary' => (bool) $p->is_primary,
                        'sort_order' => $p->sort_order,
                    ]),
                ];
            }),
        ];

        return response()->json($data);
    }

    public function productFeed(): JsonResponse
    {
        $lines = ProductLine::where('status', 'active')
            ->where('shop_visible', true)
            ->with([
                'productType:id,name',
                'unit:id,code,label',
                'productStyles' => function ($q) {
                    $q->where('status', 'active')
                      ->where('shop_visible', true)
                      ->whereNotNull('sell_price')
                      ->with(['photos' => fn ($pq) => $pq->where('is_primary', true)->limit(1)])
                      ->orderBy('name');
                },
            ])
            ->get();

        $items = $lines->flatMap(function ($line) {
            return $line->productStyles
                ->filter(fn ($style) => $line->shop_show_price || $style->shop_show_price)
                ->map(function ($style) use ($line) {
                    $photo = $style->photos->first();
                    $imageUrl = $photo
                        ? url(Storage::url($photo->file_path))
                        : ($line->photo_path ? url(Storage::url($line->photo_path)) : null);

                    return [
                        'style_id'          => $style->id,
                        'line_id'           => $line->id,
                        'line_name'         => $line->name,
                        'style_name'        => $style->name,
                        'product_type_name' => $line->productType?->name,
                        'description'       => $line->shop_description,
                        'manufacturer'      => $line->manufacturer,
                        'sku'               => $style->sku ?: $style->style_number,
                        'sell_price'        => (float) $style->sell_price,
                        'units_per'         => $style->units_per !== null ? (float) $style->units_per : null,
                        'use_box_qty'       => (bool) $style->use_box_qty,
                        'unit_code'         => $line->unit?->code,
                        'unit_label'        => $line->unit?->label,
                        'store_available'   => (bool) $line->store_available,
                        'store_qty'         => (int) ($line->store_qty ?? 1),
                        'image_url'         => $imageUrl,
                    ];
                });
        })->values();

        return response()->json($items);
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
            'color_reference'   => 'nullable|string|max:500',
        ]);

        // Create or find customer, then open an opportunity
        try {
            $customer = Customer::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name'  => trim("{$validated['first_name']} {$validated['last_name']}"),
                    'phone' => $validated['phone'],
                    'notes' => $this->buildCustomerNotes($validated),
                ]
            );

            Opportunity::create([
                'parent_customer_id'   => $customer->id,
                'job_site_customer_id' => $customer->id,
                'status'               => 'New',
                'requires_rfm'         => true,
                'is_active'            => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Shop quote: failed to create customer/opportunity', ['error' => $e->getMessage(), 'data' => $validated]);
        }

        // Internal notification
        $notifyEmail = Setting::get('shop_quote_notify_email', config('mail.from.address', 'reception@rmflooring.ca'));

        Mail::raw($this->buildQuoteEmail($validated), function ($msg) use ($validated, $notifyEmail) {
            $msg->to($notifyEmail)
                ->replyTo($validated['email'], trim("{$validated['first_name']} {$validated['last_name']}"))
                ->subject('New Quote Request — shop.rmflooring.ca');
        });

        // Confirmation email to the customer
        try {
            $this->sendQuoteConfirmation($validated);
        } catch (\Throwable $e) {
            Log::error('Shop quote: failed to send confirmation email', ['error' => $e->getMessage(), 'email' => $validated['email']]);
        }

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

    private function sendQuoteConfirmation(array $data): void
    {
        $service  = app(EmailTemplateService::class);
        $template = $service->getTemplate(null, 'shop_quote_confirmation');

        $vars = [
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'phone'             => $data['phone'],
            'product_reference' => $data['product_reference'] ?? '',
            'color_reference'   => $data['color_reference'] ?? '',
            'sq_footage'        => $data['sq_footage'] ?? '',
        ];

        $subject = $service->render($template['subject'], $vars);
        $body    = $service->render($template['body'], $vars);

        Mail::raw($body, function ($msg) use ($data, $subject) {
            $msg->to($data['email'], trim("{$data['first_name']} {$data['last_name']}"))
                ->subject($subject);
        });
    }

    private function buildCustomerNotes(array $data): string
    {
        $lines = ['Web quote submitted via shop.rmflooring.ca'];

        if (! empty($data['sq_footage'])) {
            $lines[] = "Sq Footage: {$data['sq_footage']}";
        }
        if (! empty($data['product_reference'])) {
            $lines[] = "Product: {$data['product_reference']}";
        }
        if (! empty($data['color_reference'])) {
            $lines[] = "Colour: {$data['color_reference']}";
        }

        $lines[] = '';
        $lines[] = $data['message'];

        return implode("\n", $lines);
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

        if (!empty($data['color_reference'])) {
            $lines[] = "Colour:      {$data['color_reference']}";
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
