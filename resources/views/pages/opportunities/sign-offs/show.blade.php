{{-- resources/views/pages/opportunities/sign-offs/show.blade.php --}}
<x-app-layout>
    <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Action Bar --}}
        <div class="mb-5 flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Flooring Selection Sign-Off #{{ $signOff->id }}</h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Opportunity #{{ $opportunity->id }}
                    @if ($signOff->sale) &nbsp;·&nbsp; Sale #{{ $signOff->sale->sale_number }} @endif
                    &nbsp;·&nbsp;
                    <span @class([
                        'inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => $signOff->status === 'draft',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => $signOff->status === 'finalized',
                    ])>{{ ucfirst($signOff->status) }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                    ← Documents
                </a>
                <a href="{{ route('pages.opportunities.sign-offs.pdf', [$opportunity->id, $signOff->id]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    View PDF
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('pages.opportunities.sign-offs.update', [$opportunity->id, $signOff->id]) }}">
            @csrf
            @method('PUT')

            {{-- Branding Header Preview --}}
            <div class="mb-5 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-900/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="font-bold text-blue-900 dark:text-blue-200">{{ $branding['company_name'] }}</p>
                        @if ($branding['tagline'])
                            <p class="text-xs text-blue-700 dark:text-blue-300">{{ $branding['tagline'] }}</p>
                        @endif
                        <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                            {{ implode(' · ', array_filter([$branding['street'], $branding['city'], $branding['province'], $branding['postal']])) }}
                        </p>
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            {{ implode(' · ', array_filter([$branding['phone'], $branding['email'], $branding['website']])) }}
                        </p>
                    </div>
                    <p class="text-sm font-semibold text-blue-900 dark:text-blue-200 whitespace-nowrap">Flooring Selection Sign-Off</p>
                </div>
            </div>

            {{-- Header Fields --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Document Details</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Date</label>
                        <input type="date" name="date" value="{{ old('date', $signOff->date?->format('Y-m-d')) }}" required
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Customer Name</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name', $signOff->customer_name) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job No.</label>
                        <input type="text" name="job_no" value="{{ old('job_no', $signOff->job_no) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Name</label>
                        <input type="text" name="job_site_name" value="{{ old('job_site_name', $signOff->job_site_name) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Phone</label>
                        <input type="text" name="job_site_phone" value="{{ old('job_site_phone', $signOff->job_site_phone) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Email</label>
                        <input type="text" name="job_site_email" value="{{ old('job_site_email', $signOff->job_site_email) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Job Site Address</label>
                        <input type="text" name="job_site_address" value="{{ old('job_site_address', $signOff->job_site_address) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Project Manager</label>
                        <input type="text" name="pm_name" value="{{ old('pm_name', $signOff->pm_name) }}"
                               class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" id="items-card">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Flooring Items</h2>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="addRoomRow()"
                                class="inline-flex items-center gap-1 rounded-lg border border-blue-600 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-50 dark:border-blue-500 dark:text-blue-400 dark:hover:bg-blue-900/20">
                            + Add Room
                        </button>
                        <button type="button" onclick="addItemRow()"
                                class="inline-flex items-center gap-1 rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500 dark:text-emerald-400 dark:hover:bg-emerald-900/20">
                            + Add Item
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium">Product / Description</th>
                                <th class="px-4 py-2 text-left font-medium w-20">Qty</th>
                                <th class="px-4 py-2 text-left font-medium w-24">Unit</th>
                                <th class="px-4 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody">
                            @php
                                $grouped = $signOff->items->groupBy('room_name');
                                $i = 0;
                            @endphp
                            @foreach ($grouped as $roomName => $roomItems)
                                {{-- Room header row --}}
                                <tr class="room-header-row bg-blue-50 dark:bg-blue-900/30">
                                    <td colspan="4" class="px-4 py-2">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                                            </svg>
                                            <input type="text"
                                                   data-room-label
                                                   value="{{ $roomName }}"
                                                   placeholder="Room name"
                                                   class="flex-1 rounded border-blue-200 bg-blue-50 text-sm font-semibold text-blue-800 focus:border-blue-500 focus:ring-blue-500 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                                            <button type="button" onclick="removeRoomRow(this)"
                                                    class="text-red-400 hover:text-red-600 text-xs font-medium whitespace-nowrap">
                                                Remove Room
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                {{-- Items in this room --}}
                                @foreach ($roomItems as $item)
                                <tr class="item-row border-t border-gray-100 dark:border-gray-700">
                                    <td class="px-4 py-2">
                                        <input type="hidden" name="items[{{ $i }}][room_name]" value="{{ $roomName }}" class="room-name-hidden">
                                        <input type="text" name="items[{{ $i }}][product_description]" value="{{ $item->product_description }}"
                                               class="block w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" name="items[{{ $i }}][qty]" value="{{ $item->qty }}" step="0.01" min="0"
                                               class="block w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="text" name="items[{{ $i }}][unit]" value="{{ $item->unit }}"
                                               class="block w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
                                    </td>
                                </tr>
                                @php $i++ @endphp
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Conditions --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                 x-data="{ conditionId: '{{ old('condition_id', $signOff->condition_id) }}', conditionText: {{ json_encode(old('condition_text', $signOff->condition_text ?? '')) }},
                     conditions: {{ $conditions->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'body' => $c->body])->toJson() }},
                     selectCondition(id) {
                         this.conditionId = id;
                         if (id) {
                             const c = this.conditions.find(c => c.id == id);
                             if (c) this.conditionText = c.body;
                         }
                     }
                 }">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Conditions</h2>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Select Condition Template</label>
                        <select name="condition_id" x-model="conditionId" @change="selectCondition($event.target.value)"
                                class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="">— None —</option>
                            @foreach ($conditions as $condition)
                                <option value="{{ $condition->id }}" @selected(old('condition_id', $signOff->condition_id) == $condition->id)>
                                    {{ $condition->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Condition Text (editable)</label>
                        <textarea name="condition_text" x-model="conditionText" rows="5"
                                  class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                </div>
            </div>

            {{-- Signature Section --}}
            <div class="mb-5 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Signatures</h2>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Signature fields will appear on the printed / PDF version.</p>
                </div>
                <div class="grid grid-cols-1 gap-0 divide-y sm:grid-cols-2 sm:divide-x sm:divide-y-0 divide-gray-200 dark:divide-gray-700">
                    <div class="p-4">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">Customer Signature</p>
                        <div class="border-b border-gray-400 dark:border-gray-500 h-12 mt-6"></div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ $branding['company_name'] }} Representative</p>
                        <div class="border-b border-gray-400 dark:border-gray-500 h-12 mt-6"></div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Signature &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Date</p>
                    </div>
                </div>
            </div>

            {{-- Status + Save --}}
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</label>
                    <select name="status"
                            class="rounded-lg border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="draft"      @selected($signOff->status === 'draft')>Draft</option>
                        <option value="finalized"  @selected($signOff->status === 'finalized')>Finalized</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                       class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-600 dark:hover:bg-emerald-700 dark:focus:ring-emerald-800">
                        Save Changes
                    </button>
                </div>
            </div>

        </form>
    </div>

    <script>
    let itemRowIndex = {{ $signOff->items->count() }};

    // When a room label input changes, sync all hidden room_name inputs in that group
    document.getElementById('items-tbody').addEventListener('input', function(e) {
        if (e.target.matches('[data-room-label]')) {
            const headerRow = e.target.closest('tr');
            let row = headerRow.nextElementSibling;
            while (row && row.classList.contains('item-row')) {
                const hidden = row.querySelector('.room-name-hidden');
                if (hidden) hidden.value = e.target.value;
                row = row.nextElementSibling;
            }
        }
    });

    function getCurrentRoomName() {
        // Find the last room header label value
        const headers = document.querySelectorAll('[data-room-label]');
        if (headers.length === 0) return '';
        return headers[headers.length - 1].value;
    }

    function getLastRoomHeaderRow() {
        const headers = document.querySelectorAll('#items-tbody tr.room-header-row');
        return headers.length ? headers[headers.length - 1] : null;
    }

    function addItemRow() {
        const tbody  = document.getElementById('items-tbody');
        const idx    = itemRowIndex++;
        const room   = getCurrentRoomName();
        const tr     = document.createElement('tr');
        tr.className = 'item-row border-t border-gray-100 dark:border-gray-700';
        tr.innerHTML = `
            <td class="px-4 py-2">
                <input type="hidden" name="items[${idx}][room_name]" value="${room.replace(/"/g,'&quot;')}" class="room-name-hidden">
                <input type="text" name="items[${idx}][product_description]" placeholder="Product / Description"
                       class="block w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </td>
            <td class="px-4 py-2">
                <input type="number" name="items[${idx}][qty]" value="0" step="0.01" min="0"
                       class="block w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white" required>
            </td>
            <td class="px-4 py-2">
                <input type="text" name="items[${idx}][unit]" placeholder="sq ft"
                       class="block w-full rounded border-gray-300 bg-gray-50 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function addRoomRow() {
        const tbody = document.getElementById('items-tbody');
        const tr    = document.createElement('tr');
        tr.className = 'room-header-row bg-blue-50 dark:bg-blue-900/30';
        tr.innerHTML = `
            <td colspan="4" class="px-4 py-2">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                    <input type="text" data-room-label value="" placeholder="Room name"
                           class="flex-1 rounded border-blue-200 bg-blue-50 text-sm font-semibold text-blue-800 focus:border-blue-500 focus:ring-blue-500 dark:border-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                    <button type="button" onclick="removeRoomRow(this)"
                            class="text-red-400 hover:text-red-600 text-xs font-medium whitespace-nowrap">
                        Remove Room
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
        tr.querySelector('[data-room-label]').focus();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
    }

    function removeRoomRow(btn) {
        const headerRow = btn.closest('tr');
        // Remove all following item-rows that belong to this room header
        let next = headerRow.nextElementSibling;
        while (next && next.classList.contains('item-row')) {
            const toRemove = next;
            next = next.nextElementSibling;
            toRemove.remove();
        }
        headerRow.remove();
    }
    </script>
</x-app-layout>
