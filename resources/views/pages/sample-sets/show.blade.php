<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @php
                $statusColors = \App\Models\SampleSet::STATUS_COLORS;
            @endphp

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sampleSet->set_id }}</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sampleSet->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ $sampleSet->status_label }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">Set</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $sampleSet->name ?? $sampleSet->productLine->name }}
                        · {{ $sampleSet->productLine->manufacturer }}
                        · {{ $sampleSet->items->count() }} {{ Str::plural('style', $sampleSet->items->count()) }}
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    @can('edit samples')
                    <a href="{{ route('pages.sample-sets.edit', $sampleSet) }}"
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

            {{-- Details Card --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Set Details</h2>
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Set ID</dt>
                        <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $sampleSet->set_id }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Product Line</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $sampleSet->productLine->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Location</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $sampleSet->location ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$sampleSet->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $sampleSet->status_label }}
                            </span>
                        </dd>
                    </div>
                    @if ($sampleSet->name)
                    <div class="col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">Name</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $sampleSet->name }}</dd>
                    </div>
                    @endif
                    @if ($sampleSet->notes)
                    <div class="col-span-2 md:col-span-4">
                        <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
                        <dd class="text-gray-900 dark:text-white whitespace-pre-line">{{ $sampleSet->notes }}</dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $sampleSet->created_at->format('M j, Y') }}{{ $sampleSet->creator ? ' by ' . $sampleSet->creator->name : '' }}</dd>
                    </div>
                    @if ($sampleSet->updater)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Last Updated</dt>
                        <dd class="text-gray-900 dark:text-white">{{ $sampleSet->updated_at->format('M j, Y') }} by {{ $sampleSet->updater->name }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            {{-- Styles in Set --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Styles in This Set
                        <span class="ml-1 text-sm font-normal text-gray-500">({{ $sampleSet->items->count() }})</span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3">Style</th>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Colour</th>
                                <th class="px-6 py-3 text-right">Display Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sampleSet->items as $item)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-3 font-medium text-gray-900 dark:text-white">{{ $item->productStyle->name }}</td>
                                    <td class="px-6 py-3 font-mono text-gray-600 dark:text-gray-400">{{ $item->productStyle->sku ?? '—' }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-400">{{ $item->productStyle->color ?? '—' }}</td>
                                    <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">
                                        @if ($item->display_price)
                                            ${{ number_format($item->display_price, 2) }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">No styles in this set.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Checkout Section --}}
            @can('manage sample checkouts')
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-4"
                 x-data="{ type: 'customer' }">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    @if ($sampleSet->activeCheckout)
                        Currently Checked Out
                    @else
                        Check Out This Set
                    @endif
                </h2>

                @if ($sampleSet->activeCheckout)
                    @php $co = $sampleSet->activeCheckout; @endphp
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm flex-1">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Borrower</dt>
                                <dd class="font-medium text-gray-900 dark:text-white">{{ $co->borrower_name }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Checked Out</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $co->checked_out_at->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">Due Back</dt>
                                <dd class="font-medium {{ $co->is_overdue ? 'text-red-600' : 'text-gray-900 dark:text-white' }}">
                                    {{ $co->due_back_at ? $co->due_back_at->format('M j, Y') : '—' }}
                                    @if ($co->is_overdue)
                                        <span class="ml-1 text-xs bg-red-100 text-red-700 px-1.5 py-0.5 rounded">{{ $co->days_overdue }}d overdue</span>
                                    @endif
                                </dd>
                            </div>
                            @if ($co->notes)
                            <div class="col-span-2 md:col-span-3">
                                <dt class="text-gray-500 dark:text-gray-400">Notes</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $co->notes }}</dd>
                            </div>
                            @endif
                        </dl>

                        <form action="{{ route('pages.sample-sets.checkouts.return', [$sampleSet, $co]) }}" method="POST"
                              class="flex flex-col items-end gap-2">
                            @csrf
                            <input type="text" name="return_notes" placeholder="Return notes (optional)"
                                   class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:ring-blue-500 focus:border-blue-500 w-56">
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300">
                                Mark as Returned
                            </button>
                        </form>
                    </div>

                @elseif ($sampleSet->status === 'active')
                    <form action="{{ route('pages.sample-sets.checkout', $sampleSet) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" name="checkout_type" value="customer" x-model="type"
                                       class="text-blue-600 border-gray-300 focus:ring-blue-500">
                                Customer
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" name="checkout_type" value="staff" x-model="type"
                                       class="text-blue-600 border-gray-300 focus:ring-blue-500">
                                Staff
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div x-show="type === 'customer'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Customer Name</label>
                                <input type="text" name="customer_name"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                            <div x-show="type === 'staff'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Staff Member</label>
                                <select name="user_id"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                    <option value="">— Select —</option>
                                    @foreach (\App\Models\User::orderBy('name')->get() as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Back</label>
                                <input type="date" name="due_back_at"
                                       value="{{ now()->addDays((int)\App\Models\Setting::get('sample_checkout_days', 5))->toDateString() }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                <input type="text" name="notes"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                    class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:ring-blue-300">
                                Check Out Set
                            </button>
                        </div>
                    </form>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">This set cannot be checked out (status: {{ $sampleSet->status_label }}).</p>
                @endif
            </div>
            @endcan

            {{-- Checkout History --}}
            @if ($sampleSet->checkouts->where('returned_at', '!=', null)->count() > 0)
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Checkout History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600">
                            <tr>
                                <th class="px-6 py-3">Borrower</th>
                                <th class="px-6 py-3">Checked Out</th>
                                <th class="px-6 py-3">Due Back</th>
                                <th class="px-6 py-3">Returned</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sampleSet->checkouts->where('returned_at', '!=', null)->sortByDesc('checked_out_at') as $co)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-3">{{ $co->borrower_name }}</td>
                                    <td class="px-6 py-3">{{ $co->checked_out_at->format('M j, Y') }}</td>
                                    <td class="px-6 py-3">{{ $co->due_back_at?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-6 py-3 text-green-700 dark:text-green-400">{{ $co->returned_at->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Danger Zone --}}
            @can('delete samples')
            @if (! $sampleSet->activeCheckout)
            <div class="bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800/50 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-red-700 dark:text-red-400 mb-2">Danger Zone</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Deleting this set is permanent and cannot be undone.</p>
                <form action="{{ route('pages.sample-sets.destroy', $sampleSet) }}" method="POST"
                      onsubmit="return confirm('Delete {{ $sampleSet->set_id }}? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300">
                        Delete Set
                    </button>
                </form>
            </div>
            @endif
            @endcan

        </div>
    </div>
</x-app-layout>
