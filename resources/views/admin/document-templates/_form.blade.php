{{-- Shared form partial for create + edit --}}
@php
    $isEdit = !is_null($template);
@endphp

<div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6 space-y-5">

    {{-- Name --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
            Template Name <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name"
               value="{{ old('name', $template?->name) }}"
               placeholder="e.g. Front File Label"
               class="w-full rounded-lg border-gray-300 bg-gray-50 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-blue-500 focus:ring-blue-500"
               required>
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Description --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
        <input type="text" name="description"
               value="{{ old('description', $template?->description) }}"
               placeholder="Short description shown to staff when choosing a template"
               class="w-full rounded-lg border-gray-300 bg-gray-50 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-blue-500 focus:ring-blue-500">
    </div>

    {{-- Options row --}}
    <div class="grid grid-cols-3 gap-4">

        {{-- Sort order --}}
        <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Sort Order</label>
            <input type="number" name="sort_order" min="0"
                   value="{{ old('sort_order', $template?->sort_order ?? 0) }}"
                   class="w-full rounded-lg border-gray-300 bg-gray-50 text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-blue-500 focus:ring-blue-500">
        </div>

        {{-- Needs sale --}}
        <div class="flex items-start gap-3 pt-6">
            <input type="hidden" name="needs_sale" value="0">
            <input type="checkbox" id="needs_sale" name="needs_sale" value="1"
                   {{ old('needs_sale', $template?->needs_sale) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="needs_sale" class="text-sm text-gray-700 dark:text-gray-300">
                Requires Sale selection
                <span class="block text-xs text-gray-400 dark:text-gray-500">Staff picks a sale when generating — enables <code class="text-xs">{{flooring_items_table}}</code></span>
            </label>
        </div>

        {{-- Active --}}
        <div class="flex items-start gap-3 pt-6">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1"
                   {{ old('is_active', $template?->is_active ?? true) ? 'checked' : '' }}
                   class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">
                Active
                <span class="block text-xs text-gray-400 dark:text-gray-500">Inactive templates are hidden from staff</span>
            </label>
        </div>
    </div>

    {{-- Body --}}
    <div>
        <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
            Template Body <span class="text-red-500">*</span>
            <span class="ml-2 font-normal text-gray-400 dark:text-gray-500 text-xs">HTML supported</span>
        </label>
        <textarea name="body" rows="16"
                  class="w-full rounded-lg border-gray-300 bg-gray-50 font-mono text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                  required>{{ old('body', $template?->body) }}</textarea>
        @error('body') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    {{-- Tag reference --}}
    <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-4 space-y-3">
        <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide">Available Merge Tags — click to copy</p>

        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Always available</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Models\DocumentTemplate::OPPORTUNITY_TAGS as $tag)
                    <code onclick="navigator.clipboard.writeText('{{ $tag }}')"
                          class="cursor-pointer select-all px-2 py-0.5 text-xs rounded bg-white border border-gray-200 text-gray-700 hover:bg-blue-50 hover:border-blue-300 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-blue-900/30 transition-colors">{{ $tag }}</code>
                @endforeach
            </div>
        </div>

        <div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5">Sale tags <span class="text-indigo-500">(only when "Requires Sale" is checked)</span></p>
            <div class="flex flex-wrap gap-1.5">
                @foreach (\App\Models\DocumentTemplate::SALE_TAGS as $tag)
                    <code onclick="navigator.clipboard.writeText('{{ $tag }}')"
                          class="cursor-pointer select-all px-2 py-0.5 text-xs rounded bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 dark:bg-gray-800 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/30 transition-colors">{{ $tag }}</code>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
                <code>{{flooring_items_table}}</code> renders a full HTML table of rooms + material items from the selected sale.
            </p>
        </div>

        <p class="text-xs text-gray-400 dark:text-gray-500">Click any tag to copy it to your clipboard.</p>
    </div>

</div>
