@props([
    'action' => '',
    'searchPlaceholder' => 'Search...',
    'searchValue' => '',
    'selects' => [],
    'perPageValue' => 15,
    'perPageOptions' => [15, 25, 50, 100],
])


<form method="GET" action="{{ $action }}" class="mb-6">
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            <!-- Search -->
            <div>
                <label for="search" class="block mb-2 text-sm font-medium text-gray-900">Search</label>
                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ $searchValue }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                >
            </div>

            <!-- Select filters -->
            @foreach($selects as $select)
                <div>
                    <label for="{{ $select['name'] }}" class="block mb-2 text-sm font-medium text-gray-900">
                        {{ $select['label'] }}
                    </label>

                    <select
                        id="{{ $select['name'] }}"
                        name="{{ $select['name'] }}"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                    >
                        <option value="">All</option>

                        @foreach($select['options'] as $opt)
                            <option value="{{ $opt['value'] }}" @selected(($select['selected'] ?? '') === $opt['value'])>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
			
			<!-- Per Page -->
<div>
    <label for="perPage" class="block mb-2 text-sm font-medium text-gray-900">Per Page</label>
  <select
    id="perPage"
    name="perPage"
    onchange="this.form.submit()"
    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
>
        @foreach($perPageOptions as $opt)
            <option value="{{ $opt }}" @selected((string)$perPageValue === (string)$opt)>{{ $opt }}</option>
        @endforeach
    </select>
</div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5">
                    Apply
                </button>

                <a href="{{ $action }}"
                   class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2.5">
                    Reset
                </a>
            </div>

        </div>
    </div>
</form>
