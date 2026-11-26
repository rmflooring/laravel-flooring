<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Excel Export (Xactimate)</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Set the XA Cat, XA Selector and an optional description for each material type and freight item. These are filled in whenever an estimate is exported to Excel.
                    </p>
                </div>
                <a href="{{ route('admin.settings') }}" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Back to Settings</a>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400">
                    <svg class="h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/20 dark:text-red-400">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Info banner --}}
            <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-400">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>
                    Material types apply to <strong>material</strong> rows, labour types to <strong>labour</strong> rows, and freight items to <strong>freight</strong> rows.
                    Leave a field blank to keep the current behaviour: blank XA columns, and the item's own description.
                    If <strong>Override Description</strong> is filled in, it replaces the description for every matching row
                    (e.g. Laminate &rarr; "As per independent analysis").
                </span>
            </div>

            @php
                $inputClass = 'w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';

                $sections = [
                    [
                        'title'  => 'Material Types',
                        'label'  => 'Material Type',
                        'field'  => 'types',
                        'rows'   => $productTypes->map(fn ($t) => [
                            'model' => $t,
                            'name'  => $t->name,
                            'note'  => ($t->status && $t->status !== 'active') ? $t->status : null,
                        ]),
                        'empty'  => 'No product types found.',
                    ],
                    [
                        'title'  => 'Labour Types',
                        'label'  => 'Labour Type',
                        'field'  => 'labour',
                        'rows'   => $labourTypes->map(fn ($l) => [
                            'model' => $l,
                            'name'  => $l->name,
                            'note'  => null,
                        ]),
                        'empty'  => 'No labour types found.',
                    ],
                    [
                        'title'  => 'Freight Items',
                        'label'  => 'Freight Item',
                        'field'  => 'freight',
                        'rows'   => $freightItems->map(fn ($f) => [
                            'model' => $f,
                            'name'  => $f->description,
                            'note'  => null,
                        ]),
                        'empty'  => 'No freight items found.',
                    ],
                ];
            @endphp

            <form method="POST" action="{{ route('admin.settings.xactimate-export.update') }}" class="space-y-6">
                @csrf

                @foreach ($sections as $section)
                @php
                    $configured = $section['rows']->filter(fn ($r) =>
                        $r['model']->xa_cat || $r['model']->xa_selector || $r['model']->xa_description_override
                    )->count();
                    // Collapsed by default; open when a save failed validation so errors are visible
                    $startOpen = $errors->any() ? 'true' : 'false';
                @endphp
                {{-- Collapsed sections stay in the form (hidden, not removed), so Save still submits them --}}
                <div x-data="{ open: {{ $startOpen }} }"
                     class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                            class="flex w-full items-center justify-between gap-4 rounded-lg px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-500 transition-transform dark:text-gray-400"
                                 :style="open ? 'transform: rotate(90deg)' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $section['title'] }}</span>
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $configured }} of {{ $section['rows']->count() }} set
                        </span>
                    </button>

                    <div x-show="open" x-cloak class="border-t border-gray-200 p-4 dark:border-gray-700">
                    @if ($section['rows']->isEmpty())
                        <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                            {{ $section['empty'] }}
                        </div>
                    @else
                    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3 w-56">{{ $section['label'] }}</th>
                                    <th class="px-4 py-3 w-36">XA Cat</th>
                                    <th class="px-4 py-3 w-36">XA Selector</th>
                                    <th class="px-4 py-3">Override Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($section['rows'] as $row)
                                @php
                                    $m   = $row['model'];
                                    $key = "{$section['field']}.{$m->id}";
                                    $nm  = "{$section['field']}[{$m->id}]";
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $row['name'] }}
                                        @if ($row['note'])
                                            <span class="ml-1 text-xs font-normal text-gray-400">({{ $row['note'] }})</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" maxlength="50" name="{{ $nm }}[xa_cat]"
                                               value="{{ old("{$key}.xa_cat", $m->xa_cat) }}" class="{{ $inputClass }}">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" maxlength="50" name="{{ $nm }}[xa_selector]"
                                               value="{{ old("{$key}.xa_selector", $m->xa_selector) }}" class="{{ $inputClass }}">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" maxlength="500" name="{{ $nm }}[xa_description_override]"
                                               value="{{ old("{$key}.xa_description_override", $m->xa_description_override) }}"
                                               placeholder="Leave blank to use the item's description" class="{{ $inputClass }}">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                    </div>
                </div>
                @endforeach

                {{-- Labour item description overrides: only items the admin has added are listed --}}
                <div x-data="labourItemOverrides(@js($labourItemOverrides), '{{ route('admin.settings.xactimate-export.labour-items') }}', {{ $errors->any() ? 'true' : 'false' }})"
                     class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <input type="hidden" name="labour_items_present" value="1">

                    <button type="button" @click="open = ! open" :aria-expanded="open.toString()"
                            class="flex w-full items-center justify-between gap-4 rounded-lg px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <span class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-gray-500 transition-transform dark:text-gray-400"
                                 :style="open ? 'transform: rotate(90deg)' : ''"
                                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">Labour Item Description Overrides</span>
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="items.length + (items.length === 1 ? ' item' : ' items')"></span>
                    </button>

                    <div x-show="open" x-cloak class="space-y-4 border-t border-gray-200 p-4 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Replace the description for one specific labour item. This takes priority over the labour type's override above;
                            XA Cat and XA Selector still come from the labour type. It only applies to estimate rows whose description still matches the labour item.
                        </p>

                        {{-- Search --}}
                        <div class="relative" @click.outside="results = []">
                            <input type="text" x-model="query" @input.debounce.300ms="search()" @keydown.escape="results = []"
                                   @keydown.enter.prevent="results.length && add(results[0])"
                                   placeholder="Search labour items to add (e.g. laminate click)…"
                                   class="{{ $inputClass }}">
                            <div x-show="loading" class="absolute right-3 top-2.5 text-xs text-gray-400">Searching…</div>

                            <ul x-show="results.length" x-cloak
                                class="absolute z-20 mt-1 max-h-72 w-full overflow-y-auto rounded-md border border-gray-200 bg-white text-sm shadow-lg dark:border-gray-600 dark:bg-gray-700">
                                <template x-for="r in results" :key="r.id">
                                    <li>
                                        <button type="button" @click="add(r)" :disabled="has(r.id)"
                                                class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-blue-50 disabled:cursor-default disabled:opacity-50 dark:hover:bg-gray-600">
                                            <span>
                                                <span class="text-gray-900 dark:text-white" x-text="r.description"></span>
                                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400" x-text="r.labour_type ? '(' + r.labour_type + ')' : ''"></span>
                                            </span>
                                            <span class="text-xs text-gray-400" x-text="has(r.id) ? 'Added' : 'Add'"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="noResults" x-cloak class="mt-1 text-xs text-gray-500 dark:text-gray-400">No labour items match.</p>
                        </div>

                        {{-- Current list --}}
                        <template x-if="items.length === 0">
                            <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-600 dark:text-gray-400">
                                No labour item overrides yet. Search above to add one.
                            </div>
                        </template>

                        <div x-show="items.length" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
                                <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3 w-80">Labour Item</th>
                                        <th class="px-4 py-3">Override Description</th>
                                        <th class="px-4 py-3 w-20"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="item in items" :key="item.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900 dark:text-white" x-text="item.description"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="item.labour_type || ''"></div>
                                            </td>
                                            <td class="px-4 py-3">
                                                {{-- Not "required": a collapsed section can't show the browser's warning. Blank = removed on save. --}}
                                                <input type="text" maxlength="500"
                                                       :name="'labour_items[' + item.id + '][xa_description_override]'"
                                                       x-model="item.override"
                                                       placeholder="Description to use in the Excel export (blank = remove)"
                                                       class="{{ $inputClass }}">
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button type="button" @click="remove(item.id)"
                                                        class="text-sm font-medium text-red-600 hover:underline dark:text-red-400">Remove</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">Changes here, including removals, are saved when you click <strong>Save</strong>.</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function labourItemOverrides(initial, searchUrl, startOpen) {
            return {
                open: startOpen,
                items: initial,
                query: '',
                results: [],
                loading: false,
                noResults: false,
                has(id) { return this.items.some(i => i.id === id); },
                async search() {
                    const q = this.query.trim();
                    this.noResults = false;
                    if (q.length < 2) { this.results = []; return; }
                    this.loading = true;
                    try {
                        const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        // Ignore a stale response if the box changed while it was in flight
                        if (q !== this.query.trim()) return;
                        this.results = res.ok ? await res.json() : [];
                        this.noResults = this.results.length === 0;
                    } catch (e) {
                        this.results = [];
                    } finally {
                        this.loading = false;
                    }
                },
                add(r) {
                    if (! this.has(r.id)) {
                        this.items.push({ id: r.id, description: r.description, labour_type: r.labour_type, override: r.override || '' });
                    }
                    this.query = '';
                    this.results = [];
                    this.noResults = false;
                    // Put the cursor in the new row's override field
                    this.$nextTick(() => {
                        const input = this.$root.querySelector('input[name="labour_items[' + r.id + '][xa_description_override]"]');
                        if (input) input.focus();
                    });
                },
                remove(id) { this.items = this.items.filter(i => i.id !== id); },
            };
        }
    </script>
</x-app-layout>
