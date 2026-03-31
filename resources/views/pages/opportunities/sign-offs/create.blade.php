{{-- resources/views/pages/opportunities/sign-offs/create.blade.php --}}
<x-app-layout>
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Header --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">New Flooring Selection Sign-Off</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Opportunity #{{ $opportunity->id }}
                        @if ($sale->job_name) — {{ $sale->job_name }} @endif
                        &nbsp;·&nbsp; Sale #{{ $sale->sale_number }}
                    </p>
                </div>
                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                    ← Cancel
                </a>
            </div>
        </div>

        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400">
                <p class="font-semibold mb-1">Please fix the following errors:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('pages.opportunities.sign-offs.store', $opportunity->id) }}">
            @csrf
            <input type="hidden" name="sale_id" value="{{ $sale->id }}">

            {{-- Header Fields --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Document Header</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">These fields will appear at the top of the sign-off document and can be edited later.</p>
                </div>
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Date</label>
                        <input type="date" name="date" value="{{ old('date', $defaults['date']) }}" required
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Customer Name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $defaults['customer_name']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job No.</label>
                        <input type="text" name="job_no" value="{{ old('job_no', $defaults['job_no']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Name</label>
                        <input type="text" name="job_site_name" value="{{ old('job_site_name', $defaults['job_site_name']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Phone</label>
                        <input type="text" name="job_site_phone" value="{{ old('job_site_phone', $defaults['job_site_phone']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Email</label>
                        <input type="text" name="job_site_email" value="{{ old('job_site_email', $defaults['job_site_email']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Address</label>
                        <input type="text" name="job_site_address" value="{{ old('job_site_address', $defaults['job_site_address']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Project Manager</label>
                        <input type="text" name="pm_name" value="{{ old('pm_name', $defaults['pm_name']) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Item Picker --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Select Items</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Choose the flooring materials to include. At least one item is required.</p>
                </div>

                @if ($rooms->isEmpty())
                    <div class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No material items found in this sale.
                    </div>
                @else
                    <div class="space-y-0 divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($rooms as $roomGroup)
                            @php $room = $roomGroup['room']; $items = $roomGroup['items']; @endphp
                            <div class="px-4 py-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-400">
                                    {{ $room->name }}
                                </p>
                                <div class="space-y-2">
                                    @foreach ($items as $item)
                                        @php
                                            $desc = trim(($item->manufacturer ? $item->manufacturer . ' — ' : '') . ($item->style ?? $item->description ?? ''));
                                        @endphp
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/50"
                                               data-item-id="{{ $item->id }}">
                                            <input type="checkbox"
                                                   data-toggle-item="{{ $item->id }}"
                                                   checked
                                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            {{-- Submitted under items[{id}] — disabled when unchecked --}}
                                            <span class="item-fields-{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $item->id }}][room_name]"           value="{{ $room->name }}">
                                                <input type="hidden" name="items[{{ $item->id }}][product_description]" value="{{ $desc }}">
                                                <input type="hidden" name="items[{{ $item->id }}][qty]"                 value="{{ $item->quantity ?? 0 }}">
                                                <input type="hidden" name="items[{{ $item->id }}][unit]"                value="{{ $item->unit ?? '' }}">
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $item->style ?? $item->description ?? '(No description)' }}
                                                </p>
                                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                    Qty: {{ $item->quantity ?? 0 }} {{ $item->unit ?? '' }}
                                                </p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                    Cancel
                </a>
                <button type="submit"
                        class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 dark:focus:ring-emerald-800">
                    Create Sign-Off →
                </button>
            </div>
        </form>

    </div>

    <script>
    document.querySelectorAll('[data-toggle-item]').forEach(function(cb) {
        const id = cb.dataset.toggleItem;
        function toggle() {
            document.querySelectorAll('.item-fields-' + id + ' input').forEach(function(h) {
                h.disabled = !cb.checked;
            });
        }
        cb.addEventListener('change', toggle);
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const anyChecked = document.querySelector('[data-toggle-item]:checked');
        if (!anyChecked) {
            e.preventDefault();
            alert('Please select at least one item.');
        }
    });
    </script>
</x-app-layout>
