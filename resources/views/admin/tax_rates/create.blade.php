<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Add New Tax Rate</h1>

                    <form method="POST" action="{{ route('admin.tax_rates.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Tax Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Tax Description -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Description</label>
                                <textarea name="description" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Tax Agency -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Agency *</label>
                                <select name="tax_agency_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select Agency --</option>
                                    @foreach($agencies as $agency)
                                        <option value="{{ $agency->id }}" {{ old('tax_agency_id') == $agency->id ? 'selected' : '' }}>
                                            {{ $agency->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('tax_agency_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Collect on Sales Checkbox -->
                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" id="collect_on_sales" name="collect_on_sales" value="1" {{ old('collect_on_sales') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="collect_on_sales" class="ml-2 text-sm text-gray-700">I collect this on sales</label>
                            </div>

                            <!-- Sales Fields (conditional) -->
                            <div id="sales_fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 {{ old('collect_on_sales') ? '' : 'hidden' }}">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tax on Sales (%)</label>
                                    <input type="number" step="0.0001" name="sales_rate" value="{{ old('sales_rate') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('sales_rate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sales GL Account</label>
                                    <select name="sales_gl_account_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Select GL Account --</option>
                                        @foreach($glAccounts as $gl)
                                            <option value="{{ $gl->id }}" {{ old('sales_gl_account_id') == $gl->id ? 'selected' : '' }}>
                                                {{ $gl->account_number }} - {{ $gl->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('sales_gl_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <!-- Pay on Purchases Checkbox -->
                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" id="pay_on_purchases" name="pay_on_purchases" value="1" {{ old('pay_on_purchases') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="pay_on_purchases" class="ml-2 text-sm text-gray-700">I pay this on purchases</label>
                            </div>

                            <!-- Purchases Fields (conditional) -->
                            <div id="purchases_fields" class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6 {{ old('pay_on_purchases') ? '' : 'hidden' }}">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Tax on Purchases (%)</label>
                                    <input type="number" step="0.0001" name="purchase_rate" value="{{ old('purchase_rate') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('purchase_rate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Purchase GL Account</label>
                                    <select name="purchase_gl_account_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">-- Select GL Account --</option>
                                        @foreach($glAccounts as $gl)
                                            <option value="{{ $gl->id }}" {{ old('purchase_gl_account_id') == $gl->id ? 'selected' : '' }}>
                                                {{ $gl->account_number }} - {{ $gl->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('purchase_gl_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <!-- Show on Return Line Checkbox -->
                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" id="show_on_return_line" name="show_on_return_line" value="1" {{ old('show_on_return_line') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="show_on_return_line" class="ml-2 text-sm text-gray-700">Show tax amount on return line</label>
                            </div>
                        </div>

                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('admin.tax_rates.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg">
                                Create Tax Rate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for conditional fields -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const salesCheckbox = document.getElementById('collect_on_sales');
            const purchasesCheckbox = document.getElementById('pay_on_purchases');

            const salesFields = document.getElementById('sales_fields');
            const purchasesFields = document.getElementById('purchases_fields');

            // Initial check on load
            salesFields.classList.toggle('hidden', !salesCheckbox.checked);
            purchasesFields.classList.toggle('hidden', !purchasesCheckbox.checked);

            salesCheckbox.addEventListener('change', function () {
                salesFields.classList.toggle('hidden', !this.checked);
            });

            purchasesCheckbox.addEventListener('change', function () {
                purchasesFields.classList.toggle('hidden', !this.checked);
            });
        });
    </script>
</x-app-layout>
