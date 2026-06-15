<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
            <div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">
                    <a href="{{ route('admin.product_lines.index') }}" class="hover:underline">Product Lines</a>
                </div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    {{ $product_line->name }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.product_lines.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
                <a href="{{ route('admin.product_styles.index', $product_line) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    View Styles
                </a>
                <a href="{{ route('admin.product_lines.edit', $product_line) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Edit Line
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Details Card --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Product Line Details</h3>
                    @php
                        $statusBadge = match($product_line->status) {
                            'active'   => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                            'inactive' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            'dropped'  => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                            'archived' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                            default    => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusBadge }}">{{ ucfirst($product_line->status) }}</span>
                </div>

                <div class="p-6">
                    <div class="flex gap-6">
                        {{-- Photo --}}
                        @if($product_line->photo_path)
                            <div class="flex-shrink-0">
                                <img src="{{ Storage::disk('public')->url($product_line->photo_path) }}"
                                     alt="{{ $product_line->name }}"
                                     class="w-32 h-32 object-cover rounded-lg border border-gray-200 dark:border-gray-700">
                            </div>
                        @endif

                        <div class="flex-1 grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-5 text-sm">
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Product Type</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->productType->name ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Vendor</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->vendorRelation->company_name ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Manufacturer</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->manufacturer ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Model</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->model ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Collection</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->collection ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Unit of Measure</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $product_line->unit ? $product_line->unit->code . ' — ' . $product_line->unit->label : '—' }}
                                </div>
                            </div>
                            @if($product_line->width || $product_line->length)
                                <div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Width</div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->width ? $product_line->width . '"' : '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Length</div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->length ? $product_line->length . '"' : '—' }}</div>
                                </div>
                            @endif
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Default Cost</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $product_line->default_cost_price ? '$' . rtrim(rtrim(number_format($product_line->default_cost_price, 4), '0'), '.') : '—' }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Default Sell</div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $product_line->default_sell_price ? '$' . number_format($product_line->default_sell_price, 2) : '—' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Shop & Store --}}
                    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Shop Visible</div>
                            @if($product_line->shop_visible)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Yes</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Show Price</div>
                            @if($product_line->shop_show_price)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Yes</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Store Available</div>
                            @if($product_line->store_available)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Yes</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">No</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Store Qty</div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $product_line->store_qty }}</div>
                        </div>
                    </div>

                    @if($product_line->shop_description)
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-sm">
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">Shop Description</div>
                            <div class="text-gray-700 dark:text-gray-300">{{ $product_line->shop_description }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Styles Table --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        Styles
                        <span class="ml-1.5 text-sm font-normal text-gray-500 dark:text-gray-400">({{ $styles->count() }})</span>
                    </h3>
                    <a href="{{ route('admin.product_styles.index', $product_line) }}"
                       class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        Manage Styles →
                    </a>
                </div>

                @if($styles->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                        No styles yet for this product line.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Color</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cost</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sell</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Thickness</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($styles as $style)
                                    @php
                                        $styleBadge = match($style->status) {
                                            'active'   => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                            'inactive' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            'dropped'  => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                                            'archived' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                            default    => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-200">
                                            <a href="{{ route('admin.product_styles.show', [$product_line, $style]) }}"
                                               class="hover:text-blue-600 dark:hover:text-blue-400">{{ $style->name }}</a>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $style->sku ?: '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $style->color ?: '—' }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $style->cost_price !== null ? '$' . rtrim(rtrim(number_format($style->cost_price, 4), '0'), '.') : '—' }}
                                        </td>
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900 dark:text-gray-200">
                                            {{ $style->sell_price !== null ? '$' . number_format($style->sell_price, 2) : '—' }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $style->thickness ?: '—' }}</td>
                                        <td class="px-6 py-3 text-sm">
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $styleBadge }}">{{ ucfirst($style->status) }}</span>
                                        </td>
                                        <td class="px-6 py-3 text-sm text-right">
                                            <a href="{{ route('admin.product_styles.show', [$product_line, $style]) }}"
                                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
