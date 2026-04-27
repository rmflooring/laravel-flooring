{{-- resources/views/pages/work-orders/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Work Order</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Sale: <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $sale->sale_number }}</span>
                        @if($sale->customer_name) &mdash; {{ $sale->customer_name }} @endif
                    </p>
                </div>
                <a href="{{ route('pages.sales.show', $sale) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    Cancel
                </a>
            </div>

            {{-- Errors --}}
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-gray-800">
                    <p class="mb-2 text-sm font-semibold text-red-800 dark:text-red-400">Please fix the following errors:</p>
                    <ul class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-400">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.sales.work-orders.store', $sale) }}"
                  x-data="woCreate()" @submit.prevent="submitForm">
                @csrf

                {{-- Installer --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Installer</h2>
                    </div>
                    <div class="p-6">
                        <label for="installer_id" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Assign Installer <span class="text-red-500">*</span>
                        </label>
                        <select id="installer_id" name="installer_id" required
                                x-model="installerId"
                                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                            <option value="">— Select an installer —</option>
                            @foreach($installers as $installer)
                                <option value="{{ $installer->id }}"
                                        data-email="{{ $installer->email }}"
                                        {{ old('installer_id') == $installer->id ? 'selected' : '' }}>
                                    {{ $installer->company_name }}
                                    @if($installer->email) ({{ $installer->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Labour Items — one card per room --}}
                @php $hasLabourItems = false; @endphp

                <div class="mb-2 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Labour Items</h2>
                    <button type="button" @click="toggleAll"
                            class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        Toggle All
                    </button>
                </div>

                @foreach ($rooms as $room)
                    @php $labourItems = $room->items->where('item_type', 'labour'); @endphp
                    @if ($labourItems->isEmpty()) @continue @endif
                    @php $hasLabourItems = true; @endphp

                    <div class="mb-4 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        {{-- Room header --}}
                        <div class="flex items-center gap-2 border-b border-gray-200 bg-gray-50 px-5 py-3 dark:border-gray-700 dark:bg-gray-700/40">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                {{ $room->room_name ?: 'Unnamed Room' }}
                            </h3>
                        </div>

                        <div class="space-y-3 p-4">
                            @foreach ($labourItems as $item)
                                        @php
                                            $scheduled    = (float) ($scheduledQtys[$item->id] ?? 0);
                                            $effectiveQty = $item->order_qty !== null ? (float) $item->order_qty : (float) $item->quantity;
                                            $remaining    = max(0, $effectiveQty - $scheduled);
                                            $fullyDone  = $remaining <= 0;
                                            $itemName   = implode(' — ', array_filter([$item->labour_type, $item->description])) ?: 'Labour Item';
                                        @endphp
                                        <div class="rounded-lg border {{ $fullyDone ? 'border-gray-200 bg-gray-50 opacity-60 dark:border-gray-700 dark:bg-gray-900' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }} p-4"
                                             x-data="woItem({{ $item->id }}, {{ $remaining }}, {{ (float) $item->cost_price }})">

                                            <div class="flex items-start gap-3">
                                                <input type="checkbox" name="items[{{ $item->id }}]" value="1"
                                                       id="item_{{ $item->id }}"
                                                       {{ $fullyDone ? 'disabled' : '' }}
                                                       x-model="checked"
                                                       @change="onCheck"
                                                       class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50">

                                                <div class="flex-1">
                                                    <label for="item_{{ $item->id }}"
                                                           class="block text-sm font-medium {{ $fullyDone ? 'text-gray-400' : 'text-gray-900 dark:text-white' }} {{ $fullyDone ? '' : 'cursor-pointer' }}">
                                                        {{ $itemName }}
                                                    </label>
                                                    <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                        <span>{{ number_format((float)$item->quantity, 2) }} {{ $item->unit }}</span>
                                                        @if ($fullyDone)
                                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                                Fully scheduled
                                                            </span>
                                                        @elseif ($scheduled > 0)
                                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                                                {{ number_format($remaining, 2) }} {{ $item->unit }} remaining
                                                            </span>
                                                        @endif
                                                    </div>

                                                    {{-- Qty + Cost + Notes override (shown when checked) --}}
                                                    <div x-show="checked" x-cloak class="mt-3 space-y-3">
                                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                            <div class="sm:col-span-2">
                                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                                                    Qty <span class="text-gray-400">(max {{ number_format($remaining, 2) }} {{ $item->unit }})</span>
                                                                </label>
                                                                <input type="number" name="qty[{{ $item->id }}]"
                                                                       x-model="qty"
                                                                       @input="validateQty"
                                                                       step="0.01" min="0.01" max="{{ $remaining }}"
                                                                       class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                                       :class="qtyError ? 'border-red-400' : ''">
                                                                <p x-show="qtyError" x-text="qtyError" x-cloak class="mt-1 text-xs text-red-500"></p>
                                                            </div>
                                                            <div class="sm:col-span-2">
                                                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Unit Cost</label>
                                                                <input type="number" name="cost[{{ $item->id }}]"
                                                                       x-model="cost"
                                                                       step="0.01" min="0"
                                                                       class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">WO Notes</label>
                                                            <textarea name="wo_notes[{{ $item->id }}]"
                                                                      rows="2"
                                                                      placeholder="Notes for this item on the work order..."
                                                                      class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-700 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ old('wo_notes.' . $item->id) }}</textarea>
                                                        </div>
                                                        {{-- Related Materials --}}
                                                        @php $roomMaterials = $room->items->where('item_type', 'material'); @endphp
                                                        @if($roomMaterials->isNotEmpty())
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Related Materials <span class="text-gray-400">(from this room)</span></label>
                                                            <div class="space-y-1">
                                                                @foreach($roomMaterials as $mat)
                                                                    @php
                                                                        $matName = implode(' — ', array_filter([$mat->product_type, $mat->manufacturer, $mat->style, $mat->color_item_number])) ?: 'Material';
                                                                    @endphp
                                                                    <label x-show="$store.woMaterials.isAvailable({{ $mat->id }}, {{ $item->id }})"
                                                                           class="flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 cursor-pointer hover:bg-blue-50 dark:border-gray-600 dark:bg-gray-700">
                                                                        <input type="checkbox"
                                                                               name="materials[{{ $item->id }}][]"
                                                                               value="{{ $mat->id }}"
                                                                               {{ in_array($mat->id, old('materials.' . $item->id, [])) ? 'checked' : '' }}
                                                                               @change="$event.target.checked
                                                                                   ? $store.woMaterials.claim({{ $mat->id }}, {{ $item->id }})
                                                                                   : $store.woMaterials.release({{ $mat->id }})"
                                                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        <span class="text-xs text-gray-700 dark:text-gray-300">
                                                                            {{ $matName }}
                                                                            <span class="ml-1 text-gray-400">{{ number_format((float)$mat->quantity, 2) }} {{ $mat->unit }}</span>
                                                                        </span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            @endforeach
                        </div>{{-- /.space-y-3.p-4 --}}
                    </div>{{-- /.room card --}}
                @endforeach

                @if (!$hasLabourItems)
                    <div class="rounded-lg border border-gray-200 bg-white px-5 py-6 text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        No labour items found on this sale. Add labour items to the sale before creating a work order.
                    </div>
                @endif

                <p x-show="itemError" x-text="itemError" x-cloak class="mt-2 text-sm text-red-500"></p>

                {{-- Scheduling --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Scheduling</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-2">
                        <div>
                            <label for="scheduled_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled Date</label>
                            <input type="date" id="scheduled_date" name="scheduled_date"
                                   value="{{ old('scheduled_date') }}"
                                   x-model="scheduledDate"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                        <div>
                            <label for="scheduled_time" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Scheduled Time</label>
                            <input type="time" id="scheduled_time" name="scheduled_time"
                                   value="{{ old('scheduled_time') }}"
                                   class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-500">
                        </div>
                        <div class="sm:col-span-2 flex items-start gap-3">
                            <input type="checkbox" id="sync_calendar" name="sync_calendar" value="1"
                                   x-model="syncCalendar"
                                   checked
                                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="sync_calendar" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                Add to RM – Installations calendar
                                <p x-show="syncCalendar && installerId && scheduledDate" x-cloak class="mt-0.5 text-xs text-blue-600 dark:text-blue-400">
                                    A calendar event will be created when this work order is saved.
                                </p>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                    </div>
                    <div>
                        <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
                        <div id="notes-quill-editor" style="min-height:100px; font-size:14px;"></div>
                        <input type="hidden" name="notes" id="notes-input" value="{{ old('notes') }}">
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex gap-3">
                    <button type="submit"
                            class="rounded-lg bg-blue-700 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Create Work Order
                    </button>
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="rounded-lg border border-gray-300 bg-white px-6 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.store('woMaterials', {
            claimed: {},
            claim(matId, labourItemId) {
                this.claimed = { ...this.claimed, [matId]: labourItemId };
            },
            release(matId) {
                const c = { ...this.claimed };
                delete c[matId];
                this.claimed = c;
            },
            isAvailable(matId, labourItemId) {
                return !(matId in this.claimed) || this.claimed[matId] === labourItemId;
            },
        });
    });

    function woCreate() {
        return {
            installerId:   '{{ old('installer_id', '') }}',
            scheduledDate: '{{ old('scheduled_date', '') }}',
            syncCalendar:  {{ old('sync_calendar', '1') }} !== '0',
            itemError:     '',
            hasQtyErrors:  false,

            toggleAll() {
                const boxes = document.querySelectorAll('input[name^="items["]:not(:disabled)');
                const anyUnchecked = [...boxes].some(b => !b.checked);
                boxes.forEach(b => {
                    b.checked = anyUnchecked;
                    b.dispatchEvent(new Event('change'));
                });
            },

            submitForm() {
                const checked = document.querySelectorAll('input[name^="items["]:checked');
                if (!checked.length) {
                    this.itemError = 'Please select at least one labour item.';
                    return;
                }
                this.itemError = '';
                this.$el.submit();
            },
        };
    }

    function woItem(itemId, maxQty, defaultCost) {
        return {
            checked:  {{ old('items.' . '0', 'false') }},
            qty:      maxQty,
            cost:     defaultCost,
            qtyError: '',

            onCheck() {
                if (this.checked) {
                    this.qty = maxQty;
                } else {
                    // Release any materials this labour item had claimed
                    const store = Alpine.store('woMaterials');
                    Object.keys(store.claimed).forEach(matId => {
                        if (store.claimed[matId] === itemId) {
                            store.release(parseInt(matId));
                        }
                    });
                    // Uncheck the material checkboxes visually
                    document.querySelectorAll(`input[name="materials[${itemId}][]"]:checked`).forEach(cb => {
                        cb.checked = false;
                    });
                }
            },

            validateQty() {
                const v = parseFloat(this.qty);
                if (isNaN(v) || v <= 0) {
                    this.qtyError = 'Qty must be greater than 0.';
                } else if (v > maxQty) {
                    this.qtyError = `Max allowed is ${maxQty}.`;
                } else {
                    this.qtyError = '';
                }
            },
        };
    }
    </script>

    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script>
        const notesQuill = new Quill('#notes-quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [['bold','italic','underline'],[{'color':[]}],['clean']]
            },
            placeholder: 'Special instructions or context...',
        });
        function syncNotesInput() {
            const html = notesQuill.root.innerHTML;
            document.getElementById('notes-input').value = (html === '<p><br></p>') ? '' : html;
        }
        notesQuill.on('text-change', syncNotesInput);
        const notesExisting = @json(old('notes', ''));
        if (notesExisting) notesQuill.clipboard.dangerouslyPasteHTML(notesExisting);
        else syncNotesInput();
    </script>
</x-app-layout>
