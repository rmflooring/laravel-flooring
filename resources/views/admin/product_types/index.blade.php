<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800 leading-tight">
                Product Types
            </h2>

            @can('create product types')
                <a href="{{ route('admin.product_types.create') }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white
                          hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300">
                    <span class="text-base leading-none">+</span>
                    <span>Add Product Type</span>
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800" role="alert">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 h-2.5 w-2.5 rounded-full bg-green-500"></div>
                        <div class="text-sm font-medium">
                            {{ session('success') }}
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow">

                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Product Types List</h3>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full text-left text-sm text-gray-700">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Name</th>
                                <th class="px-4 py-3">Ordered By</th>
                                <th class="px-4 py-3">Sold By</th>
                                <th class="px-4 py-3">Cost GL</th>
                                <th class="px-4 py-3">Sell GL</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($productTypes as $productType)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $productType->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $productType->orderedByUnit->label ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $productType->soldByUnit->label ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ optional($productType->defaultCostGlAccount)->account_number }}
                                        {{ optional($productType->defaultCostGlAccount)->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ optional($productType->defaultSellGlAccount)->account_number }}
                                        {{ optional($productType->defaultSellGlAccount)->name }}
                                    </td>

                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center gap-2">

                                            @can('edit product types')
                                                <a href="{{ route('admin.product_types.edit', $productType->id) }}"
                                                   class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm font-medium text-gray-800
                                                          hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200">
                                                    Edit
                                                </a>
                                            @endcan

                                            @can('delete product types')
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-sm font-medium text-white
                                                           hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300"
                                                    data-modal-target="deleteProductTypeModal"
                                                    data-modal-toggle="deleteProductTypeModal"
                                                    data-delete-url="{{ route('admin.product_types.destroy', $productType->id) }}"
                                                    data-item-name="{{ $productType->name }}"
                                                >
                                                    Delete
                                                </button>
                                            @endcan

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        No product types found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    {{-- Flowbite Delete Confirmation Modal --}}
    <div id="deleteProductTypeModal" tabindex="-1"
         class="fixed left-0 top-0 z-50 hidden h-modal w-full overflow-y-auto overflow-x-hidden p-4 md:h-full">
        <div class="relative h-full w-full max-w-md md:h-auto">

            <div class="relative rounded-lg bg-white shadow">
                <button type="button"
                        class="absolute right-2.5 top-2.5 inline-flex items-center rounded-lg p-1.5 text-gray-400 hover:bg-gray-200 hover:text-gray-900"
                        data-modal-hide="deleteProductTypeModal">
                    <span class="sr-only">Close modal</span>
                    ✕
                </button>

                <div class="p-6 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-50">
                        <span class="text-red-600 text-xl">!</span>
                    </div>

                    <h3 class="mb-2 text-lg font-semibold text-gray-900">Confirm delete</h3>
                    <p class="mb-5 text-sm text-gray-600">
                        Are you sure you want to delete <span id="deleteItemName" class="font-semibold text-gray-800">this product type</span>?
                        This action cannot be undone.
                    </p>

                    <form id="deleteProductTypeForm" method="POST" action="#">
                        @csrf
                        @method('DELETE')

                        <div class="flex items-center justify-center gap-3">
                            <button type="button"
                                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-800
                                           hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200"
                                    data-modal-hide="deleteProductTypeModal">
                                Cancel
                            </button>

                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white
                                           hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
                                Yes, delete
                            </button>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>

    {{-- Minimal JS to set modal form action + item name --}}
    <script>
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('button[data-delete-url][data-modal-target="deleteProductTypeModal"]');
            if (!btn) return;

            const deleteUrl = btn.getAttribute('data-delete-url');
            const itemName = btn.getAttribute('data-item-name') || 'this product type';

            const form = document.getElementById('deleteProductTypeForm');
            const nameEl = document.getElementById('deleteItemName');

            if (form) form.setAttribute('action', deleteUrl);
            if (nameEl) nameEl.textContent = itemName;
        });
    </script>
</x-admin-layout>
