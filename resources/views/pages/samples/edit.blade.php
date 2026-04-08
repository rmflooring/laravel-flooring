<x-app-layout>
    <div class="py-6">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-mono">{{ $sample->sample_id }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $sample->productStyle->name }}</p>
                </div>
                <a href="{{ route('pages.samples.show', $sample) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    ← Back
                </a>
            </div>

            @if ($errors->any())
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pages.samples.update', $sample) }}">
                @csrf @method('PUT')

                {{-- Product (read-only) --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-2">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Product</h2>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 p-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $sample->productStyle->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $sample->productStyle->productLine?->manufacturer }}
                            @if ($sample->productStyle->color) · {{ $sample->productStyle->color }} @endif
                            @if ($sample->productStyle->sku) · SKU: {{ $sample->productStyle->sku }} @endif
                        </p>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Product cannot be changed after creation.</p>
                    </div>
                </div>

                {{-- Sample Details --}}
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm p-6 space-y-5 mt-4">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Sample Details</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select name="status"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                @foreach (\App\Models\Sample::STATUSES as $val => $label)
                                    <option value="{{ $val }}" @selected(old('status', $sample->status) === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantity</label>
                            <input type="number" name="quantity" value="{{ old('quantity', $sample->quantity) }}" min="1"
                                   class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Display Price Override
                            <span class="text-gray-400 font-normal">(leave blank to use catalog price: {{ $sample->productStyle->sell_price ? '$' . number_format($sample->productStyle->sell_price, 2) : 'not set' }})</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500 text-sm">$</span>
                            <input type="number" name="display_price" value="{{ old('display_price', $sample->display_price) }}"
                                   step="0.01" min="0" placeholder="0.00"
                                   class="block w-full pl-7 p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Location in Showroom</label>
                        <input type="text" name="location" value="{{ old('location', $sample->location) }}"
                               placeholder="e.g. Showroom – Hardwood Wall"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date Received</label>
                        <input type="date" name="received_at" value="{{ old('received_at', $sample->received_at?->format('Y-m-d')) }}"
                               class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="block w-full p-2.5 text-sm text-gray-900 bg-gray-50 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white dark:border-gray-600">{{ old('notes', $sample->notes) }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('pages.samples.show', $sample) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
