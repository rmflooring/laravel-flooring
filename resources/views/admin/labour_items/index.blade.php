<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-8">
                        <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white">
                            Labour Items
                        </h1>

                        <a href="{{ route('admin.labour_items.create') }}"
                           class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-blue-700 rounded-xl hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add New Labour Item
                        </a>
                    </div>

                    {{-- Filters (Customer-style layout) --}}
                    <div class="border border-gray-200 dark:border-gray-700 rounded-2xl p-6 mb-8">
                        <form method="GET" action="{{ route('admin.labour_items.index') }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

                                {{-- Search --}}
                                <div>
                                    <label for="search" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Search
                                    </label>
                                    <input
                                        type="text"
                                        id="search"
                                        name="search"
                                        value="{{ request('search') }}"
                                        placeholder="Description or notes..."
                                        class="block w-full p-3 text-sm text-gray-900 bg-white border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500
                                               dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    >
                                </div>

                                {{-- Status --}}
                                <div>
                                    <label for="status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Status
                                    </label>
                                    <select
                                        id="status"
                                        name="status"
                                        class="block w-full p-3 text-sm text-gray-900 bg-white border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500
                                               dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    >
                                        <option value="">All</option>
                                        <option value="Active" @selected(request('status') === 'Active')>Active</option>
                                        <option value="Inactive" @selected(request('status') === 'Inactive')>Inactive</option>
                                        <option value="Needs Update" @selected(request('status') === 'Needs Update')>Needs Update</option>
                                    </select>
                                </div>

                                {{-- Labour Type (this is your "Type") --}}
                                <div>
                                    <label for="labour_type_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Type
                                    </label>
                                    <select
                                        id="labour_type_id"
                                        name="labour_type_id"
                                        class="block w-full p-3 text-sm text-gray-900 bg-white border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500
                                               dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    >
                                        <option value="">All</option>
                                        @foreach ($labourTypes as $type)
                                            <option value="{{ $type->id }}" @selected((string)request('labour_type_id') === (string)$type->id)>
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Per Page --}}
                                <div>
                                    <label for="per_page" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Per Page
                                    </label>
                                    <select
                                        id="per_page"
                                        name="per_page"
                                        class="block w-full p-3 text-sm text-gray-900 bg-white border border-gray-300 rounded-xl focus:ring-blue-500 focus:border-blue-500
                                               dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                    >
                                        @foreach ([10, 15, 25, 50, 100] as $n)
                                            <option value="{{ $n }}" @selected((int)request('per_page', 15) === $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>

                            {{-- Buttons (Customer-style: left aligned, not full width) --}}
                            <div class="mt-6 flex gap-3">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-blue-700 rounded-xl hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                                >
                                    Apply
                                </button>

                                <a
                                    href="{{ route('admin.labour_items.index') }}"
                                    class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-xl hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200
                                           dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700"
                                >
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>

                    {{-- Flash --}}
                    @if (session('success'))
                        <div class="mb-6 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-700 dark:text-green-400" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Results --}}
                    @if ($labourItems->total() === 0)
                        <p class="text-gray-500 dark:text-gray-400">
                            No labour items found.
                            <a href="{{ route('admin.labour_items.create') }}" class="text-blue-700 hover:underline dark:text-blue-400">Create one now</a>.
                        </p>
                    @else
                        <div class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                            Showing <span class="font-semibold">{{ $labourItems->firstItem() }}</span>
                            to <span class="font-semibold">{{ $labourItems->lastItem() }}</span>
                            of <span class="font-semibold">{{ $labourItems->total() }}</span> results
                        </div>

                        <div class="relative overflow-x-auto border border-gray-200 rounded-2xl dark:border-gray-700">
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Description</th>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Labour Type</th>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Unit</th>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Cost</th>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Sell</th>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Status</th>
                                        <th scope="col" class="px-6 py-4 whitespace-nowrap">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($labourItems as $item)
                                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                                {{ $item->description }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $item->labourType->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                {{ $item->unitMeasure->label ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                ${{ number_format($item->cost, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                ${{ number_format($item->sell, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="text-xs font-medium px-2.5 py-0.5 rounded
                                                    @if ($item->status === 'Active')
                                                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300
                                                    @elseif ($item->status === 'Needs Update')
                                                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300
                                                    @else
                                                        bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                                    @endif
                                                ">
                                                    {{ $item->status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('admin.labour_items.edit', $item) }}"
                                                   class="font-medium text-blue-600 hover:underline dark:text-blue-500">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6">
                            {{ $labourItems->links() }}
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
