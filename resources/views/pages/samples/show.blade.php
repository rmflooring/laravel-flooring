<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $statusColors = [
                    'active'       => 'bg-green-100 text-green-800',
                    'checked_out'  => 'bg-blue-100 text-blue-800',
                    'discontinued' => 'bg-gray-100 text-gray-600',
                    'retired'      => 'bg-yellow-100 text-yellow-800',
                    'lost'         => 'bg-red-100 text-red-800',
                ];
                $style = $sample->productStyle;
                $line  = $style->productLine;
            @endphp

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sample->sample_id }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sample->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $sample->status_label }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $style->name }}
                        @if ($line) · {{ $line->manufacturer }} @endif
                        @if ($style->color) · {{ $style->color }} @endif
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Label dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                            </svg>
                            Print Label
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-cloak
                             class="absolute right-0 mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg z-10">
                            <a href="{{ route('pages.samples.label', [$sample, 'format' => '5371']) }}" target="_blank"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-t-lg">
                                Avery 5371 (3.5" × 2")
                            </a>
                            <a href="{{ route('pages.samples.label', [$sample, 'format' => '5388']) }}" target="_blank"
                               class="block px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-b-lg border-t border-gray-100 dark:border-gray-700">
                                Avery 5388 (3" × 5")
                            </a>
                        </div>
                    </div>

                    @can('edit samples')
                    <a href="{{ route('pages.samples.edit', $sample) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                        Edit
                    </a>
                    @endcan

                    <a href="{{ route('pages.samples.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        ← Back
                    </a>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-100 border border-green-200">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100 border border-red-200">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Left column: details + photo --}}
                <div class="md:col-span-2 space-y-6">

                    {{-- Product details --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Product Details</h2>
                        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Product Name</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $style->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Manufacturer</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $line?->manufacturer ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Product Line</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $line?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Colour</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $style->color ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">SKU</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $style->sku ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Style #</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $style->style_number ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Display Price</dt>
                                <dd class="font-semibold text-gray-900 dark:text-white text-base">
                                    @if ($sample->effective_price)
                                        ${{ number_format($sample->effective_price, 2) }}
                                        @if ($sample->display_price && $sample->display_price != $style->sell_price)
                                            <span class="text-xs font-normal text-blue-600">(override)</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">Not set</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Catalog Sell Price</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">
                                    {{ $style->sell_price ? '$' . number_format($style->sell_price, 2) : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Photos --}}
                    @php $photos = $style->photos->sortBy('sort_order'); @endphp
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">
                                Product Photos
                                <span class="ml-1 text-xs font-normal text-gray-400">({{ $photos->count() }}/3)</span>
                            </h2>
                        </div>

                        @if ($photos->isNotEmpty())
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            @foreach ($photos as $photo)
                                <div class="relative group aspect-square rounded-lg overflow-hidden border-2 {{ $photo->is_primary ? 'border-blue-500' : 'border-gray-200 dark:border-gray-600' }} bg-gray-100 dark:bg-gray-700">
                                    <img src="{{ $photo->url }}" alt="Product photo" class="w-full h-full object-cover">
                                    @if ($photo->is_primary)
                                        <span class="absolute top-1 left-1 px-1.5 py-0.5 text-xs bg-blue-600 text-white rounded">Primary</span>
                                    @endif
                                    @can('edit samples')
                                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity bg-black/50">
                                        @if (!$photo->is_primary)
                                            <form method="POST" action="{{ route('admin.product_styles.photos.primary', [$line->id, $style->id, $photo]) }}">
                                                @csrf
                                                <input type="hidden" name="_redirect_back" value="{{ route('pages.samples.show', $sample) }}">
                                                <button type="submit" class="text-xs text-white bg-blue-600 hover:bg-blue-700 rounded px-2 py-1">Set Primary</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.product_styles.photos.destroy', [$line->id, $style->id, $photo]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="_redirect_back" value="{{ route('pages.samples.show', $sample) }}">
                                            <button type="submit" onclick="return confirm('Delete this photo?')"
                                                    class="text-xs text-white bg-red-600 hover:bg-red-700 rounded px-2 py-1">Delete</button>
                                        </form>
                                    </div>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">No photos yet.</p>
                        @endif

                        @can('edit samples')
                        @if ($photos->count() < 3)
                            <form method="POST"
                                  action="{{ route('admin.product_styles.photos.store', [$line->id, $style->id]) }}"
                                  enctype="multipart/form-data"
                                  class="flex items-center gap-3">
                                @csrf
                                <input type="hidden" name="_redirect_back" value="{{ route('pages.samples.show', $sample) }}">
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                                       class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400"
                                       required>
                                <button type="submit"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold text-white bg-blue-700 hover:bg-blue-800 rounded-lg">
                                    Upload
                                </button>
                            </form>
                            <p class="mt-1 text-xs text-gray-400">JPG, PNG or WebP · max 5 MB</p>
                        @else
                            <p class="text-xs text-gray-400">Maximum 3 photos. Delete one to upload another.</p>
                        @endif
                        @endcan
                    </div>

                    {{-- Active Checkouts --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">
                                Active Checkouts
                                @if ($sample->activeCheckouts->isNotEmpty())
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $sample->activeCheckouts->count() }}
                                    </span>
                                @endif
                            </h2>
                        </div>

                        @if ($sample->activeCheckouts->isEmpty())
                            <div class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No active checkouts.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th class="px-6 py-3">Borrower</th>
                                            <th class="px-6 py-3">Type</th>
                                            <th class="px-6 py-3">Qty</th>
                                            <th class="px-6 py-3">Checked Out</th>
                                            <th class="px-6 py-3">Due Back</th>
                                            <th class="px-6 py-3 text-right">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($sample->activeCheckouts as $checkout)
                                            @php $overdue = $checkout->is_overdue; @endphp
                                            <tr class="border-b border-gray-100 dark:border-gray-700 {{ $overdue ? 'bg-red-50 dark:bg-red-900/10' : 'bg-white dark:bg-gray-800' }} hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">
                                                    {{ $checkout->borrower_name }}
                                                    @if ($checkout->destination)
                                                        <div class="text-xs text-gray-500">{{ $checkout->destination }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $checkout->checkout_type === 'staff' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700' }}">
                                                        {{ ucfirst($checkout->checkout_type) }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-3">{{ $checkout->qty_checked_out }}</td>
                                                <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $checkout->checked_out_at->format('M j, Y') }}</td>
                                                <td class="px-6 py-3 {{ $overdue ? 'text-red-600 font-semibold' : 'text-gray-600 dark:text-gray-400' }}">
                                                    {{ $checkout->due_back_at ? $checkout->due_back_at->format('M j, Y') : '—' }}
                                                    @if ($overdue)
                                                        <div class="text-xs font-normal">{{ $checkout->days_overdue }}d overdue</div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 text-right">
                                                    @can('manage sample checkouts')
                                                    <form method="POST"
                                                          action="{{ route('pages.samples.checkouts.return', [$sample, $checkout]) }}"
                                                          onsubmit="return confirm('Mark this sample as returned?')">
                                                        @csrf
                                                        <button type="submit"
                                                                class="text-xs px-3 py-1.5 font-medium text-green-700 bg-green-50 rounded-lg hover:bg-green-100 border border-green-200">
                                                            Mark Returned
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- Checkout History --}}
                    @php $history = $sample->checkouts->where('returned_at', '!=', null); @endphp
                    @if ($history->isNotEmpty())
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Checkout History</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                                <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                    <tr>
                                        <th class="px-6 py-3">Borrower</th>
                                        <th class="px-6 py-3">Checked Out</th>
                                        <th class="px-6 py-3">Returned</th>
                                        <th class="px-6 py-3">Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($history as $checkout)
                                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                        <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $checkout->borrower_name }}</td>
                                        <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $checkout->checked_out_at->format('M j, Y') }}</td>
                                        <td class="px-6 py-3 text-green-700">{{ $checkout->returned_at->format('M j, Y') }}</td>
                                        <td class="px-6 py-3 text-gray-500">{{ $checkout->return_notes ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                </div>

                {{-- Right column: stats + QR --}}
                <div class="space-y-6">

                    {{-- Inventory card --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Inventory</h2>
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sample->quantity }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Total</p>
                            </div>
                            <div class="rounded-lg p-3 {{ $sample->available_qty === 0 ? 'bg-red-50 dark:bg-red-900/20' : 'bg-green-50 dark:bg-green-900/20' }}">
                                <p class="text-2xl font-bold {{ $sample->available_qty === 0 ? 'text-red-600' : 'text-green-700 dark:text-green-400' }}">
                                    {{ $sample->available_qty }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Available</p>
                            </div>
                        </div>
                        <dl class="text-sm space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Location</dt>
                                <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $sample->location ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Received</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $sample->received_at?->format('M j, Y') ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- QR / Mobile link --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 text-center space-y-3">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Mobile / QR</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Scan to view and check out this sample on mobile.</p>
                        <a href="{{ route('mobile.samples.show', $sample->sample_id) }}" target="_blank"
                           class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 dark:bg-blue-900/20 dark:text-blue-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 9h3"/>
                            </svg>
                            Open Mobile Page
                        </a>
                        <p class="text-xs text-gray-400 font-mono">{{ $sample->sample_id }}</p>
                    </div>

                    {{-- Notes --}}
                    @if ($sample->notes)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Notes</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $sample->notes }}</p>
                    </div>
                    @endif

                    {{-- Record info --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Record</h2>
                        <dl class="text-xs space-y-1.5 text-gray-500">
                            <div class="flex justify-between"><dt>Created by</dt><dd>{{ $sample->creator?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt>Created</dt><dd>{{ $sample->created_at->format('M j, Y') }}</dd></div>
                            <div class="flex justify-between"><dt>Updated by</dt><dd>{{ $sample->updater?->name ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt>Updated</dt><dd>{{ $sample->updated_at->format('M j, Y') }}</dd></div>
                        </dl>
                    </div>

                    {{-- Delete --}}
                    @can('delete samples')
                    @if ($sample->activeCheckouts->isEmpty())
                    <form method="POST" action="{{ route('pages.samples.destroy', $sample) }}"
                          onsubmit="return confirm('Delete sample {{ $sample->sample_id }}? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100">
                            Delete Sample
                        </button>
                    </form>
                    @endif
                    @endcan

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
