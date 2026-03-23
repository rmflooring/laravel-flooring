{{-- resources/views/pages/change-orders/create.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6">
                <nav class="mb-2 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <a href="{{ route('pages.sales.show', $sale) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                        Sale #{{ $sale->sale_number }}
                    </a>
                    <span>/</span>
                    <span class="text-gray-700 dark:text-gray-200">New Change Order</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Change Order</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    A snapshot of the current sale items will be taken. You will then be able to edit
                    the sale items to reflect the homeowner's requested changes.
                </p>
            </div>

            {{-- Current Sale Summary --}}
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Current Approved Total (will be locked as original)</p>
                <p class="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-100">
                    ${{ number_format($sale->grand_total, 2) }}
                </p>
                <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                    {{ $sale->rooms->count() }} room(s) · {{ $sale->items()->count() }} line item(s)
                </p>
            </div>

            {{-- Form --}}
            <form method="POST" action="{{ route('pages.sales.change-orders.store', $sale) }}">
                @csrf

                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    <div class="space-y-5">

                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Change Order Title <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <input type="text" id="title" name="title"
                                   value="{{ old('title') }}"
                                   placeholder="e.g. Upgrade master bedroom flooring"
                                   class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">
                            @error('title')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Reason for Change <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea id="reason" name="reason" rows="3"
                                      placeholder="Describe what the homeowner wants to change..."
                                      class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Internal Notes <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea id="notes" name="notes" rows="2"
                                      placeholder="Any internal notes about this change order..."
                                      class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400">{{ old('notes') }}</textarea>
                        </div>

                    </div>

                </div>

                {{-- Warning --}}
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                    <div class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                        <div class="text-sm text-amber-800 dark:text-amber-200">
                            <p class="font-medium">Once created:</p>
                            <ul class="mt-1 list-disc list-inside space-y-1 text-amber-700 dark:text-amber-300">
                                <li>The current sale items will be snapshotted as the original</li>
                                <li>Purchase Orders and Work Orders will be blocked until this Change Order is approved or cancelled</li>
                                <li>You will edit the sale items directly to reflect the changes</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="mt-6 flex items-center justify-between">
                    <a href="{{ route('pages.sales.show', $sale) }}"
                       class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        ← Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Create Change Order
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
