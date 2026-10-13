{{-- Product details for the rmflooring.ca shop filters. Fields shown depend on the selected product type. --}}
@php
    $attrs = old('attrs', $product_line?->shop_attributes ?? []);
    $typeFamilies = $types->mapWithKeys(fn ($t) => [$t->id => \App\Support\ShopAttributes::family($t->name)]);
    $input = 'bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white';
@endphp
<div id="shop-attributes" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600" data-families='@json($typeFamilies)'>
    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Product details (shop filters)</h3>
    <p class="mt-1 mb-4 text-xs text-gray-500 dark:text-gray-400">Used for the filters on rmflooring.ca (e.g. species, AC rating). Leave blank if unknown. Colour tone is set on each colour (style).</p>
    <p id="shop-attributes-none" class="text-sm text-gray-500 dark:text-gray-400">No extra details for this product type.</p>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach (\App\Support\ShopAttributes::FIELDS as $key => $field)
            <div class="shop-attr" data-for="{{ implode(' ', $field['for']) }}">
                <label for="attr_{{ $key }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $field['label'] }}</label>
                @if ($field['type'] === 'text')
                    <input type="text" id="attr_{{ $key }}" name="attrs[{{ $key }}]" value="{{ $attrs[$key] ?? '' }}" maxlength="{{ $field['max'] ?? 50 }}" placeholder="{{ $field['placeholder'] ?? '' }}" class="{{ $input }}">
                @elseif ($field['type'] === 'multi')
                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                        @foreach ($field['options'] as $value => $label)
                            <label class="inline-flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200">
                                <input type="checkbox" name="attrs[{{ $key }}][]" value="{{ $value }}" @checked(in_array($value, (array) ($attrs[$key] ?? []), true)) class="rounded border-gray-300 text-blue-600">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                @else
                    <select id="attr_{{ $key }}" name="attrs[{{ $key }}]" class="{{ $input }}">
                        <option value="">—</option>
                        @foreach ($field['options'] as $value => $label)
                            <option value="{{ $value }}" @selected(($attrs[$key] ?? null) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
                @error("attrs.{$key}")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>
</div>
<script>
    (function () {
        const box = document.getElementById('shop-attributes');
        const families = JSON.parse(box.dataset.families);
        const select = document.getElementById('product_type_id');
        function refresh() {
            const family = families[select.value] || null;
            let shown = 0;
            box.querySelectorAll('.shop-attr').forEach(function (el) {
                const on = family && el.dataset.for.split(' ').includes(family);
                el.style.display = on ? '' : 'none';
                if (on) shown++;
            });
            document.getElementById('shop-attributes-none').style.display = shown ? 'none' : '';
        }
        select.addEventListener('change', refresh);
        refresh();
    })();
</script>
