<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">
                    <a href="{{ route('admin.product_lines.index') }}" class="hover:underline">Product Lines</a>
                    <span class="mx-1">›</span>
                    <a href="{{ route('admin.product_lines.show', $product_line) }}" class="hover:underline">{{ $product_line->name }}</a>
                    <span class="mx-1">›</span>
                    Styles
                </div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $style->name }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.product_styles.index', $product_line) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    All Styles
                </a>
                <a href="{{ route('admin.product_styles.edit', [$product_line, $style]) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Edit Style
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Product Line Context Card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4 text-sm">
                <div class="flex-1 flex flex-wrap gap-x-6 gap-y-1">
                    <div>
                        <span class="text-gray-400 dark:text-gray-500">Line: </span>
                        <a href="{{ route('admin.product_lines.show', $product_line) }}"
                           class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $product_line->name }}</a>
                    </div>
                    @if($product_line->productType)
                        <div>
                            <span class="text-gray-400 dark:text-gray-500">Type: </span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $product_line->productType->name }}</span>
                        </div>
                    @endif
                    @if($product_line->vendorRelation)
                        <div>
                            <span class="text-gray-400 dark:text-gray-500">Vendor: </span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $product_line->vendorRelation->company_name }}</span>
                        </div>
                    @endif
                    @if($product_line->manufacturer)
                        <div>
                            <span class="text-gray-400 dark:text-gray-500">Manufacturer: </span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $product_line->manufacturer }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Main Details --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Style Details Card --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Style Details</h3>
                            <div class="flex items-center gap-2">
                                @php
                                    $statusBadge = match($style->status) {
                                        'active'   => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                        'inactive' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                        'dropped'  => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                                        'archived' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                        default    => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusBadge }}">{{ ucfirst($style->status) }}</span>
                                @if($style->shop_visible)
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">Shop Visible</span>
                                @endif
                            </div>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-x-8 gap-y-5 text-sm">
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">SKU</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $style->sku ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Style Number</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $style->style_number ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Color</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $style->color ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Pattern</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $style->pattern ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Thickness</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $style->thickness ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Units Per Box</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $style->units_per !== null ? number_format($style->units_per, 2) : '—' }}
                                    @if($style->use_box_qty)
                                        <span class="ml-1.5 text-xs text-blue-600 dark:text-blue-400">(Box qty rounding on)</span>
                                    @endif
                                </div>
                            </div>
                            @if($style->vendor)
                                <div class="col-span-2">
                                    <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Vendor Override</div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $style->vendor->company_name }}</div>
                                </div>
                            @endif
                            @if($style->description)
                                <div class="col-span-2">
                                    <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Description</div>
                                    <div class="text-gray-700 dark:text-gray-300">{{ $style->description }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Photos --}}
                    @if($style->photos->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Photos</h3>
                            </div>
                            <div class="p-6 flex flex-wrap gap-4">
                                @foreach($style->photos as $photo)
                                    <div class="relative">
                                        <img src="{{ $photo->url }}" alt="Style photo"
                                             class="w-36 h-36 object-cover rounded-lg border-2 {{ $photo->is_primary ? 'border-blue-500' : 'border-gray-200 dark:border-gray-700' }}">
                                        @if($photo->is_primary)
                                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-semibold bg-blue-600 text-white rounded">Primary</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Pricing Sidebar --}}
                <div class="space-y-4">

                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pricing</h3>
                        </div>
                        <div class="p-5 space-y-4 text-sm">
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Cost Price</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $style->cost_price !== null ? '$' . rtrim(rtrim(number_format($style->cost_price, 4), '0'), '.') : '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Sell Price</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $style->sell_price !== null ? '$' . number_format($style->sell_price, 2) : '—' }}
                                </div>
                            </div>
                            @if($style->cost_price && $style->sell_price && $style->sell_price > 0)
                                @php
                                    $margin = (($style->sell_price - $style->cost_price) / $style->sell_price) * 100;
                                    $marginColor = $margin < 20 ? '#dc2626' : ($margin < 38 ? '#d97706' : '#16a34a');
                                @endphp
                                <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Gross Margin</div>
                                    <div class="text-xl font-bold" style="color: {{ $marginColor }}">
                                        {{ number_format($margin, 1) }}%
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Shop Settings --}}
                    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Shop</h3>
                        </div>
                        <div class="p-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Visible on shop</span>
                                @if($style->shop_visible)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Yes</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No</span>
                                @endif
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Show price</span>
                                @if($style->shop_show_price)
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Yes</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No</span>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
