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
                                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700/50">
                                            <input type="checkbox"
                                                   name="item_select[{{ $item->id }}]"
                                                   value="1"
                                                   checked
                                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                                            {{-- Hidden fields for this item --}}
                                            <input type="hidden" name="_items[{{ $item->id }}][room_name]"           value="{{ $room->name }}">
                                            <input type="hidden" name="_items[{{ $item->id }}][product_description]" value="{{ trim(($item->manufacturer ? $item->manufacturer . ' — ' : '') . ($item->style ?? $item->description ?? '')) }}">
                                            <input type="hidden" name="_items[{{ $item->id }}][qty]"                 value="{{ $item->quantity ?? 0 }}">
                                            <input type="hidden" name="_items[{{ $item->id }}][unit]"                value="{{ $item->unit ?? '' }}">
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
    // When a checkbox is unchecked, disable the hidden fields so they don't submit
    document.querySelectorAll('input[name^="item_select["]').forEach(function(cb) {
        const itemId = cb.name.match(/\[(\d+)\]/)[1];
        const hiddens = document.querySelectorAll(`input[name^="_items[${itemId}]"]`);

        function toggle() {
            hiddens.forEach(h => h.disabled = !cb.checked);
        }
        toggle();
        cb.addEventListener('change', toggle);
    });

    // On form submit, build items[] from enabled hidden fields
    document.querySelector('form').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('input[name^="item_select["]:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Please select at least one item.');
            return;
        }
        // Remap _items[id][field] → items[][field] (sequential array)
        let idx = 0;
        checked.forEach(function(cb) {
            const itemId = cb.name.match(/\[(\d+)\]/)[1];
            ['room_name','product_description','qty','unit'].forEach(function(field) {
                const hidden = document.querySelector(`input[name="_items[${itemId}][${field}]"]`);
                if (hidden) {
                    const clone = document.createElement('input');
                    clone.type = 'hidden';
                    clone.name = `items[${idx}][${field}]`;
                    clone.value = hidden.value;
                    document.querySelector('form').appendChild(clone);
                }
            });
            idx++;
        });
    });
    </script>
</x-app-layout>
