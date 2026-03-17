<x-app-layout>
<x-slot name="header">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Product Catalogue
        </h2>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.product_lines.index') }}"
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                Manage Lines
            </a>
            <a href="{{ route('admin.product_types.index') }}"
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                Manage Types
            </a>
            <a href="{{ route('admin.product_lines.create') }}"
               class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Product Line
            </a>
        </div>
    </div>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        {{-- Stats Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

            <a href="{{ route('admin.product_types.index') }}"
               class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500 transition-all">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-2.5 bg-purple-100 dark:bg-purple-900/40 rounded-xl">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['types'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Product Types</div>
            </a>

            <a href="{{ route('admin.product_lines.index') }}"
               class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-blue-300 dark:hover:border-blue-500 transition-all">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-2.5 bg-blue-100 dark:bg-blue-900/40 rounded-xl">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                        </svg>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                    </svg>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['lines'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Product Lines</div>
                <div class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_lines'] }} active</div>
            </a>

            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-2.5 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['styles'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Product Styles</div>
                <div class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['active_styles'] }} active</div>
            </div>

            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 shadow-sm text-white">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-2.5 bg-white/20 rounded-xl">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-lg font-semibold">Quick Search</div>
                <div class="text-sm text-blue-100 mt-0.5">Search lines or styles below</div>
            </div>

        </div>

        {{-- Search Panel --}}
        <form method="GET" action="{{ route('admin.products.index') }}"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-sm">

            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Search Catalogue</h3>

            {{-- Mode toggle --}}
            <div class="flex gap-2 mb-5">
                <button type="button"
                        onclick="setMode('styles')"
                        id="btn-styles"
                        class="mode-btn px-5 py-2 rounded-full text-sm font-medium border transition-all
                               {{ $mode === 'styles' ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600' }}">
                    Search Styles
                </button>
                <button type="button"
                        onclick="setMode('lines')"
                        id="btn-lines"
                        class="mode-btn px-5 py-2 rounded-full text-sm font-medium border transition-all
                               {{ $mode === 'lines' ? 'bg-blue-700 text-white border-blue-700' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-400 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600' }}">
                    Search Lines
                </button>
            </div>
            <input type="hidden" name="mode" id="mode-input" value="{{ $mode }}">

            {{-- Search box --}}
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="search"
                           id="search-input"
                           value="{{ $search }}"
                           placeholder="{{ $mode === 'lines' ? 'Search by name, manufacturer, model, collection...' : 'Search by style name, colour, SKU, style number, pattern...' }}"
                           autofocus
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-blue-300 focus:border-blue-500 text-sm" />
                </div>
                <button type="submit"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-xl hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 whitespace-nowrap">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('admin.products.index') }}"
                       class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 whitespace-nowrap">
                        Clear
                    </a>
                @endif
            </div>

            @if($mode === 'styles')
                <p class="mt-2 text-xs text-gray-400">Searches styles across all product lines.</p>
            @else
                <p class="mt-2 text-xs text-gray-400">Searches product lines by name, manufacturer, model, collection, and vendor.</p>
            @endif
        </form>

        {{-- Results --}}
        @if($search !== '')

            {{-- Style Results --}}
            @if($mode === 'styles')
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Style Results
                            <span class="ml-2 text-sm font-normal text-gray-500">({{ $styleResults->total() }} found)</span>
                        </h3>
                    </div>

                    @if($styleResults->isEmpty())
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-10 text-center text-gray-500 dark:text-gray-400">
                            No styles found matching <strong class="text-gray-700 dark:text-gray-200">"{{ $search }}"</strong>.
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Style Name</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Product Line</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Colour</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Style #</th>
                                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sell</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                            <th class="px-5 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach($styleResults as $style)
                                            @php
                                                $badgeClass = match($style->status) {
                                                    'active'  => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                                    'dropped' => 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100',
                                                    default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                };
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $style->name }}</td>
                                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                                    <a href="{{ route('admin.product_styles.index', $style->productLine) }}"
                                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                                        {{ $style->productLine->name ?? '—' }}
                                                    </a>
                                                </td>
                                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $style->productLine->productType->name ?? '—' }}</td>
                                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $style->color ?: '—' }}</td>
                                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $style->sku ?: '—' }}</td>
                                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $style->style_number ?: '—' }}</td>
                                                <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                                    {{ $style->sell_price !== null ? '$' . number_format($style->sell_price, 2) : '—' }}
                                                </td>
                                                <td class="px-5 py-3">
                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeClass }}">
                                                        {{ ucfirst($style->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-right">
                                                    <a href="{{ route('admin.product_styles.edit', [$style->productLine, $style]) }}"
                                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs font-medium">
                                                        Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($styleResults->hasPages())
                                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                    {{ $styleResults->links() }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            {{-- Line Results --}}
            @if($mode === 'lines')
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Product Line Results
                            <span class="ml-2 text-sm font-normal text-gray-500">({{ $lineResults->total() }} found)</span>
                        </h3>
                    </div>

                    @if($lineResults->isEmpty())
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-10 text-center text-gray-500 dark:text-gray-400">
                            No product lines found matching <strong class="text-gray-700 dark:text-gray-200">"{{ $search }}"</strong>.
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Manufacturer</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Model</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Collection</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vendor</th>
                                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                            <th class="px-5 py-3"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach($lineResults as $line)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                <td class="px-5 py-3 font-medium">
                                                    <a href="{{ route('admin.product_styles.index', $line) }}"
                                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                                        {{ $line->name }}
                                                    </a>
                                                </td>
                                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $line->productType->name ?? '—' }}</td>
                                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $line->manufacturer ?: '—' }}</td>
                                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $line->model ?: '—' }}</td>
                                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300">{{ $line->collection ?: '—' }}</td>
                                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400">{{ $line->vendorRelation->company_name ?? '—' }}</td>
                                                <td class="px-5 py-3">
                                                    @php
                                                        $lbadge = match($line->status) {
                                                            'active'   => 'bg-green-100 text-green-800',
                                                            'inactive' => 'bg-gray-100 text-gray-700',
                                                            'archived' => 'bg-red-100 text-red-700',
                                                            default    => 'bg-gray-100 text-gray-700',
                                                        };
                                                    @endphp
                                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $lbadge }}">
                                                        {{ ucfirst($line->status) }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3 text-right">
                                                    <a href="{{ route('admin.product_styles.index', $line) }}"
                                                       class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium mr-3">
                                                        View Styles
                                                    </a>
                                                    <a href="{{ route('admin.product_lines.edit', $line) }}"
                                                       class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 text-xs font-medium">
                                                        Edit
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($lineResults->hasPages())
                                <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
                                    {{ $lineResults->links() }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

        @endif

    </div>
</div>

<script>
var ACTIVE_BTN   = 'px-5 py-2 rounded-full text-sm font-medium border transition-all bg-blue-700 text-white border-blue-700';
var INACTIVE_BTN = 'px-5 py-2 rounded-full text-sm font-medium border transition-all bg-white text-gray-600 border-gray-300 hover:border-blue-400';

function setMode(mode) {
    document.getElementById('mode-input').value = mode;

    var btnStyles   = document.getElementById('btn-styles');
    var btnLines    = document.getElementById('btn-lines');
    var searchInput = document.getElementById('search-input');

    if (mode === 'styles') {
        btnStyles.className = ACTIVE_BTN;
        btnLines.className  = INACTIVE_BTN;
        searchInput.placeholder = 'Search by style name, colour, SKU, style number, pattern...';
    } else {
        btnLines.className  = ACTIVE_BTN;
        btnStyles.className = INACTIVE_BTN;
        searchInput.placeholder = 'Search by name, manufacturer, model, collection...';
    }
}
</script>

</x-app-layout>
