<x-app-layout>
<x-slot name="header">
    <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Product Styles for: {{ $product_line->name }}
        </h2>

        <div class="flex items-center gap-2">
            {{-- Back to Product Lines --}}
            <a href="{{ route('admin.product_lines.index') }}"
               class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg
                      hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100
                      dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Product Lines
            </a>

            {{-- Add Style Button --}}
            <button type="button" data-modal-target="add-style-modal" data-modal-toggle="add-style-modal"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center
                           dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add New Style
            </button>
        </div>
    </div>
</x-slot>


    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Hidden links for keyboard navigation (always present) -->
            <a href="{{ $prevId ? route('admin.product_styles.index', $prevId) : '#' }}" id="prevLine" class="hidden"></a>
            <a href="{{ $nextId ? route('admin.product_styles.index', $nextId) : '#' }}" id="nextLine" class="hidden"></a>

            {{-- Product Line Info Card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 mb-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div class="md:col-span-2">
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Product Line</div>
                            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $product_line->name }}</div>
                            @if($product_line->productType)
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                                    {{ $product_line->productType->name }}
                                </span>
                            @endif
                        </div>
                        @if($product_line->manufacturer)
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Manufacturer</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $product_line->manufacturer }}</div>
                            </div>
                        @endif
                        @if($product_line->model)
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Model</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $product_line->model }}</div>
                            </div>
                        @endif
                        @if($product_line->collection)
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Collection</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $product_line->collection }}</div>
                            </div>
                        @endif
                        @if($product_line->vendorRelation)
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Vendor</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">{{ $product_line->vendorRelation->company_name }}</div>
                            </div>
                        @endif
                        @if($product_line->default_sell_price)
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Default Sell</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">${{ number_format($product_line->default_sell_price, 2) }}</div>
                            </div>
                        @endif
                        @if($product_line->default_cost_price)
                            <div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Default Cost</div>
                                <div class="font-medium text-gray-800 dark:text-gray-200">${{ number_format($product_line->default_cost_price, 2) }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Status</div>
                            @php
                                $lineBadge = match($product_line->status) {
                                    'active'   => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                    'inactive' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    'archived' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                    default    => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $lineBadge }}">
                                {{ ucfirst($product_line->status) }}
                            </span>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-0.5">Styles</div>
                            <div class="font-medium text-gray-800 dark:text-gray-200">{{ $styles->total() }}</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.product_lines.edit', $product_line) }}"
                       class="flex-shrink-0 inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-700">
                        Edit Line
                    </a>
                </div>
            </div>

            {{-- Search / Filter --}}
            <form method="GET" action="{{ route('admin.product_styles.index', $product_line) }}"
                  class="border border-gray-200 dark:border-gray-700 rounded-2xl p-5 mb-6 bg-white dark:bg-gray-800">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Name, SKU, style number, colour, pattern..."
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-blue-300 focus:border-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Status</label>
                        <select name="status"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-blue-300 focus:border-blue-500">
                            <option value="">All (excl. archived)</option>
                            <option value="active"   @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                            <option value="dropped"  @selected(request('status') === 'dropped')>Dropped</option>
                            <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                        Search
                    </button>
                    <a href="{{ route('admin.product_styles.index', $product_line) }}"
                       class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Reset
                    </a>
                </div>
            </form>

            <!-- Table & Styles -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="p-6">
                    @if ($styles->isEmpty())
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No styles found for this product line.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Style Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Color</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pattern</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cost</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sell</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Units Per</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Thickness</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($styles as $style)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-200">
                                                {{ $style->name }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->sku ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->style_number ?? 'N/A' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->color ?? 'N/A' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->pattern ?? 'N/A' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->cost_price !== null ? '$' . number_format($style->cost_price, 2) : '—' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-200">
                                                {{ $style->sell_price !== null ? '$' . number_format($style->sell_price, 2) : '—' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->units_per !== null ? number_format($style->units_per, 2) : '—' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                {{ $style->thickness ?? '—' }}
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @php
                                                    $badgeClass = match($style->status) {
                                                        'active'   => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                                        'dropped'  => 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100',
                                                        'archived' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                        default    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $badgeClass }}">
                                                    {{ ucfirst($style->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-4">
                                                    @if($style->status !== 'archived')
                                                        <a href="{{ route('admin.product_styles.edit', [$product_line, $style]) }}"
                                                           class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                                            Edit
                                                        </a>
                                                        <form action="{{ route('admin.product_styles.duplicate', [$product_line, $style]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-300">
                                                                Duplicate
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.product_styles.archive', [$product_line, $style]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                    onclick="return confirm('Archive this style?')"
                                                                    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300">
                                                                Archive
                                                            </button>
                                                        </form>
                                                    @else
                                                        {{-- Archived: Restore + Delete (admin only, no activity) --}}
                                                        <form action="{{ route('admin.product_styles.unarchive', [$product_line, $style]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                                Restore
                                                            </button>
                                                        </form>
                                                        @role('admin')
                                                            @php $canDelete = ($style->estimate_items_count + $style->sale_items_count) === 0; @endphp
                                                            @if($canDelete)
                                                                <form action="{{ route('admin.product_styles.destroy', [$product_line, $style]) }}" method="POST">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                            onclick="return confirm('Permanently delete this style? This cannot be undone.')"
                                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            @else
                                                                <span class="text-gray-400 dark:text-gray-500 cursor-not-allowed" title="Used in estimates or sales — cannot delete">
                                                                    In use
                                                                </span>
                                                            @endif
                                                        @endrole
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Pagination --}}
                    @if($styles->hasPages())
                        <div class="mt-4">{{ $styles->links() }}</div>
                    @endif

                    <!-- Navigation info -->
                    <div class="flex justify-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                        Product Line {{ $currentPosition }} of {{ $totalLines }}
                    </div>

                    <!-- Navigation Buttons (always visible) -->
                    <div class="flex justify-between mt-4">
                        <div class="space-x-2">
                            <a href="{{ $firstId ? route('admin.product_styles.index', $firstId) : '#' }}"
                               class="px-4 py-2 rounded text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600
                               {{ !$firstId ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">« First</a>

                            <a href="{{ $prevId ? route('admin.product_styles.index', $prevId) : '#' }}"
                               class="px-4 py-2 rounded text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600
                               {{ !$prevId ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">‹ Back</a>
                        </div>

                        <div class="space-x-2">
                            <a href="{{ $nextId ? route('admin.product_styles.index', $nextId) : '#' }}"
                               class="px-4 py-2 rounded text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600
                               {{ !$nextId ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">Next ›</a>

                            <a href="{{ $lastId ? route('admin.product_styles.index', $lastId) : '#' }}"
                               class="px-4 py-2 rounded text-gray-700 bg-gray-200 hover:bg-gray-300 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600
                               {{ !$lastId ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">Last »</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Style Modal -->
    <div id="add-style-modal" tabindex="-1" aria-hidden="true"
         class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-2xl max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ session('editStyle') ? 'Edit Style' : 'Add New Style' }} for {{ $product_line->name }}
                    </h3>
                    <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="add-style-modal">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>

                <!-- Modal form -->
                <form id="style-form"
                      action="{{ session('editStyle') ? route('admin.product_styles.update', [$product_line, session('editStyle')->id]) : route('admin.product_styles.store', $product_line) }}"
                      method="POST" class="p-4 md:p-5">
                    @csrf
                    @if(session('editStyle'))
                        @method('PUT')
                    @endif
                    <input type="hidden" name="product_line_id" value="{{ $product_line->id }}">
                    <input type="hidden" name="style_id" value="{{ session('editStyle')->id ?? '' }}">

                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <div class="col-span-2">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Style Name</label>
                            <input type="text" name="name" id="name" value="{{ session('editStyle')->name ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="sku" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">SKU</label>
                            <input type="text" name="sku" id="sku" value="{{ session('editStyle')->sku ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="style_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Style Number</label>
                            <input type="text" name="style_number" id="style_number" value="{{ session('editStyle')->style_number ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="color" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Color</label>
                            <input type="text" name="color" id="color" value="{{ session('editStyle')->color ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="cost_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cost Price</label>
                            <input type="number" step="0.0001" name="cost_price" id="cost_price" value="{{ session('editStyle')->cost_price ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="sell_price" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Sell Price</label>
                            <input type="number" step="0.01" name="sell_price" id="sell_price" value="{{ session('editStyle')->sell_price ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Apply GPM:</span>
                                <select id="gpm_selector" class="flex-1 text-xs bg-gray-50 border border-gray-300 text-gray-700 rounded-lg p-1.5 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:focus:ring-blue-500">
                                    <option value="">— select margin —</option>
                                    <option value="0.05">5%</option>
                                    <option value="0.10">10%</option>
                                    <option value="0.15">15%</option>
                                    <option value="0.20">20%</option>
                                    <option value="0.25">25%</option>
                                    <option value="0.30">30%</option>
                                    <option value="0.35">35%</option>
                                    <option value="0.40">40%</option>
                                    <option value="0.45">45%</option>
                                    <option value="0.50">50%</option>
                                    <option value="0.55">55%</option>
                                    <option value="0.60">60%</option>
                                    <option value="0.65">65%</option>
                                    <option value="0.70">70%</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="units_per" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Units Per</label>
                            <input type="number" step="0.01" min="0" name="units_per" id="units_per" value="{{ session('editStyle')->units_per ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="e.g. 20">
                        </div>

                        <div class="col-span-2 sm:col-span-1 flex items-end pb-1">
                            <label class="inline-flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="use_box_qty" id="use_box_qty" value="1"
                                       {{ (session('editStyle')->use_box_qty ?? false) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">Use Box Qty</span>
                            </label>
                            <p class="ml-7 text-xs text-gray-500 dark:text-gray-400 -mt-1">Prompt estimator to round up to full box quantity</p>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="thickness" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Thickness</label>
                            <input type="text" name="thickness" id="thickness" value="{{ session('editStyle')->thickness ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="e.g. 3mm, 12mil">
                        </div>

                        <div class="col-span-2">
                            <label for="pattern" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pattern</label>
                            <input type="text" name="pattern" id="pattern" value="{{ session('editStyle')->pattern ?? '' }}" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                        </div>
                        <div class="col-span-2">
                            <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Description</label>
                            <textarea name="description" id="description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">{{ session('editStyle')->description ?? '' }}</textarea>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="vendor_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Vendor <span class="text-gray-400 font-normal">(for PO auto-select)</span></label>
                            <select name="vendor_id" id="vendor_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">— Same as product line —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}"
                                        {{ (session('editStyle')->vendor_id ?? $product_line->vendor_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-2 sm:col-span-1">
                            <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
                            <select name="status" id="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="active" {{ (session('editStyle')->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ (session('editStyle')->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="dropped" {{ (session('editStyle')->status ?? '') == 'dropped' ? 'selected' : '' }}>Dropped</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <button type="button" data-modal-hide="add-style-modal" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            {{ session('editStyle') ? 'Update Style' : 'Save Style' }}
                        </button>
                    </div>
                </form>

                {{-- Photos section (edit mode only) --}}
                @if(session('editStyle'))
                    @php $editStyle = session('editStyle'); $photos = $editStyle->photos ?? collect(); @endphp
                    <div class="border-t border-gray-200 dark:border-gray-600 p-4 md:p-5" data-photos-section>
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Photos
                                <span class="ml-1 text-xs font-normal text-gray-500">({{ $photos->count() }}/3)</span>
                            </h4>
                        </div>

                        @if(session('photo_error'))
                            <p class="mb-3 text-xs text-red-600 dark:text-red-400">{{ session('photo_error') }}</p>
                        @endif

                        {{-- Existing photos --}}
                        @if($photos->count() > 0)
                            <div class="flex flex-wrap gap-3 mb-4">
                                @foreach($photos->sortBy('sort_order') as $photo)
                                    <div class="relative group w-24 h-24">
                                        <img src="{{ $photo->url }}" alt="Style photo"
                                             class="w-24 h-24 object-cover rounded-lg border-2 {{ $photo->is_primary ? 'border-blue-500' : 'border-gray-200 dark:border-gray-600' }}">
                                        @if($photo->is_primary)
                                            <span class="absolute top-1 left-1 px-1 py-0.5 text-[10px] font-semibold bg-blue-600 text-white rounded">Primary</span>
                                        @endif
                                        <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-black/50 rounded-lg">
                                            @if(!$photo->is_primary)
                                                <form method="POST" action="{{ route('admin.product_styles.photos.primary', [$product_line, $editStyle->id, $photo]) }}">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-white bg-blue-600 hover:bg-blue-700 rounded px-2 py-0.5">
                                                        Set Primary
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('admin.product_styles.photos.destroy', [$product_line, $editStyle->id, $photo]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        onclick="return confirm('Delete this photo?')"
                                                        class="text-xs text-white bg-red-600 hover:bg-red-700 rounded px-2 py-0.5">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Upload form --}}
                        @if($photos->count() < 3)
                            <form method="POST"
                                  action="{{ route('admin.product_styles.photos.store', [$product_line, $editStyle->id]) }}"
                                  enctype="multipart/form-data"
                                  class="flex items-center gap-3">
                                @csrf
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                                       class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-gray-700 dark:file:text-gray-300"
                                       required>
                                <button type="submit"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white bg-blue-700 hover:bg-blue-800 rounded-lg dark:bg-blue-600 dark:hover:bg-blue-700">
                                    Upload
                                </button>
                            </form>
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG or WebP · max 5 MB · up to 3 photos</p>
                        @else
                            <p class="text-xs text-gray-400 dark:text-gray-500">Maximum 3 photos reached. Delete one to upload another.</p>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </div>

    @if(session('editStyle'))
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('add-style-modal');
        if (modal) {
            modal.classList.remove('hidden');
            @if(session('photo_tab'))
            // Scroll to photos section after a photo action
            setTimeout(() => {
                const photos = modal.querySelector('[data-photos-section]');
                if (photos) photos.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
            @endif
        }
    });
    </script>
    @endif

    <!-- GPM sell price calculator -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('gpm_selector').addEventListener('change', function () {
            const margin = parseFloat(this.value);
            const cost = parseFloat(document.getElementById('cost_price').value);
            if (!margin || isNaN(cost) || cost <= 0) {
                this.value = '';
                return;
            }
            const sell = cost / (1 - margin);
            document.getElementById('sell_price').value = sell.toFixed(2);
            this.value = '';
        });
    });
    </script>

    <!-- Keyboard navigation -->
    <script>
    document.addEventListener('keydown', function(e) {
        if (['INPUT','TEXTAREA','SELECT'].includes(document.activeElement.tagName)) return;

        if (e.key === 'ArrowLeft') {
            const prev = document.getElementById('prevLine');
            if (prev && prev.href !== '#') window.location.href = prev.href;
        } else if (e.key === 'ArrowRight') {
            const next = document.getElementById('nextLine');
            if (next && next.href !== '#') window.location.href = next.href;
        }
    });
    </script>

</x-app-layout>
