<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Freight Items</h1>
                    <p class="text-sm text-gray-600">Manage freight descriptions and default pricing.</p>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Add New --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Freight Item</h2>

                <form method="POST" action="{{ route('admin.freight_items.store') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    @csrf

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Description</label>
                        <input type="text" name="description" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5" required>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Cost Price</label>
                        <input type="number" step="0.01" min="0" name="cost_price" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5" value="0.00">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-medium text-gray-700">Sell Price</label>
                        <input type="number" step="0.01" min="0" name="sell_price" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5" value="0.00">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Notes</label>
                        <input type="text" name="notes" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5">
                    </div>

                    <div class="md:col-span-6 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                            Add Freight Item
                        </button>
                    </div>
                </form>
            </div>

            {{-- List / Inline Edit --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-4 py-3">Description</th>
                                <th class="px-4 py-3">Cost</th>
                                <th class="px-4 py-3">Sell</th>
                                <th class="px-4 py-3">Notes</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($items as $item)
                                <tr class="bg-white">
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('admin.freight_items.update', $item) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')

                                            <input type="text" name="description" value="{{ $item->description }}"
                                                class="w-72 bg-gray-50 border border-gray-300 rounded-lg p-2" required>
                                    </td>

                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0" name="cost_price" value="{{ $item->cost_price }}"
                                            class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                    </td>

                                    <td class="px-4 py-3">
                                        <input type="number" step="0.01" min="0" name="sell_price" value="{{ $item->sell_price }}"
                                            class="w-28 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                    </td>

                                    <td class="px-4 py-3">
                                        <input type="text" name="notes" value="{{ $item->notes }}"
                                            class="w-80 bg-gray-50 border border-gray-300 rounded-lg p-2">
                                    </td>

                                    <td class="px-4 py-3">
                                        <button type="submit" class="text-blue-700 hover:underline font-medium">
                                            Save
                                        </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                        No freight items yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-admin-layout>
