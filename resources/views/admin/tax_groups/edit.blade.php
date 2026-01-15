{{-- resources/views/admin/tax_groups/edit.blade.php --}}
<x-admin-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Edit Tax Group</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Update group details and included Tax Rates.
                </p>

                <div class="mt-3 flex items-center justify-between gap-3">
                    <a href="{{ route('admin.tax_groups.index') }}"
                       class="text-sm text-blue-700 hover:underline">
                        ← Back to Tax Groups
                    </a>

                    @php
                        $isArchived = !is_null($group->deleted_at);
                        $isDefault = ($defaultGroupId && (int)$defaultGroupId === (int)$group->id);
                    @endphp

                    <div class="flex items-center gap-2">
                        @if ($isDefault)
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                Default
                            </span>
                        @endif

                        @if ($isArchived)
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                Archived
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">
                                Active
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Validation --}}
            @if ($errors->any())
                <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Separate Archive Form (NOT nested) --}}
            @if (!$isArchived)
                <form id="archive-tax-group-form"
                      method="POST"
                      action="{{ route('admin.tax_groups.destroy', $group->id) }}"
                      class="hidden"
                      onsubmit="return confirm('Archive this tax group?');">
                    @csrf
                    @method('DELETE')
                </form>
            @endif

            <form method="POST" action="{{ route('admin.tax_groups.update', $group->id) }}" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Card --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6 space-y-6">

                    {{-- Name --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Group Name</label>
                        <input type="text"
                               name="name"
                               value="{{ old('name', $group->name) }}"
                               required
                               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="e.g. BC GST + PST">
                    </div>

                    {{-- Description --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Description (optional)</label>
                        <input type="text"
                               name="description"
                               value="{{ old('description', $group->description) }}"
                               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Short description shown in lists">
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Notes (optional)</label>
                        <textarea name="notes"
                                  rows="3"
                                  class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Internal notes">{{ old('notes', $group->notes) }}</textarea>
                    </div>

                    {{-- Make Default --}}
                    <div class="flex items-center gap-2">
                        <input id="make_default"
                               type="checkbox"
                               name="make_default"
                               value="1"
                               {{ old('make_default', $isDefault ? 1 : 0) ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        <label for="make_default" class="text-sm text-gray-700">
                            Set as default tax group
                        </label>
                    </div>
                </div>

                {{-- Tax Rates Picker --}}
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Tax Rates</h2>
                            <p class="text-sm text-gray-600 mt-1">Choose one or more rates to include.</p>
                        </div>

                        <div class="text-sm text-gray-700">
                            Total Sales Rate:
                            <span id="rateTotal" class="font-semibold">0.00%</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                                <tr>
                                    <th class="px-4 py-3 w-12"></th>
                                    <th class="px-4 py-3">Tax Rate</th>
                                    <th class="px-4 py-3 w-40 text-right">Sales Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $oldSelected = old('tax_rate_ids');
                                    $selected = is_array($oldSelected) ? $oldSelected : $selectedRateIds;
                                @endphp

                                @foreach ($taxRates as $rate)
                                    @php
                                        $checked = in_array($rate->id, $selected);
                                        $salesRate = is_null($rate->sales_rate) ? 0 : (float) $rate->sales_rate;
                                    @endphp
                                    <tr class="border-b last:border-b-0">
                                        <td class="px-4 py-3">
                                            <input type="checkbox"
                                                   name="tax_rate_ids[]"
                                                   value="{{ $rate->id }}"
                                                   data-sales-rate="{{ $salesRate }}"
                                                   {{ $checked ? 'checked' : '' }}
                                                   class="rate-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                        </td>
                                        <td class="px-4 py-3 text-gray-900 font-medium">
                                            {{ $rate->name }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            {{ number_format($salesRate, 4) }}%
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($taxRates) === 0)
                                    <tr>
                                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">
                                            No tax rates found. Create tax rates first.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

 {{-- Actions --}}
<div class="flex items-center justify-between gap-3">
    <div class="flex items-center gap-2">
        @if ($isArchived)
            <form method="POST" action="{{ route('admin.tax_groups.restore', $group->id) }}"
                  onsubmit="return confirm('Restore this tax group?');">
                @csrf
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Restore Tax Group
                </button>
            </form>
        @endif

        @if (!$isArchived)
            <button type="submit"
                    form="archive-tax-group-form"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                Archive Tax Group
            </button>
        @endif
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tax_groups.index') }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
            Cancel
        </a>

        <button type="submit"
                class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            Save Changes
        </button>
    </div>
</div>

            </form>

        </div>
    </div>

    <script>
        (function () {
            function calcTotal() {
                const boxes = document.querySelectorAll('.rate-checkbox');
                let total = 0;

                boxes.forEach(cb => {
                    if (cb.checked) {
                        const val = parseFloat(cb.getAttribute('data-sales-rate') || '0');
                        if (!Number.isNaN(val)) total += val;
                    }
                });

                document.getElementById('rateTotal').textContent = total.toFixed(4) + '%';
            }

            document.addEventListener('change', function (e) {
                if (e.target && e.target.classList.contains('rate-checkbox')) {
                    calcTotal();
                }
            });

            calcTotal();
        })();
    </script>
</x-admin-layout>