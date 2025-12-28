<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Product Type
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.product_types.update', $productType) }}">
                        @csrf
                        @method('PUT')

                        <div class="space-y-6 max-w-2xl">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="name"
                                       value="{{ old('name', $productType->name) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('name') border-red-300 @enderror">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Ordered By Unit -->
                            <div>
                                <label for="ordered_by_unit_id" class="block text-sm font-medium text-gray-700">Ordered By Unit</label>
                                <select name="ordered_by_unit_id" id="ordered_by_unit_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('ordered_by_unit_id') border-red-300 @enderror">
                                    @foreach($unitMeasures as $unit)
                                        <option value="{{ $unit->id }}" {{ old('ordered_by_unit_id', $productType->ordered_by_unit_id) == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ordered_by_unit_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Sold By Unit -->
                            <div>
                                <label for="sold_by_unit_id" class="block text-sm font-medium text-gray-700">Sold By Unit</label>
                                <select name="sold_by_unit_id" id="sold_by_unit_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('sold_by_unit_id') border-red-300 @enderror">
                                    @foreach($unitMeasures as $unit)
                                        <option value="{{ $unit->id }}" {{ old('sold_by_unit_id', $productType->sold_by_unit_id) == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sold_by_unit_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Default Cost GL Account -->
                            <div>
                                <label for="default_cost_gl_account_id" class="block text-sm font-medium text-gray-700">Default Cost GL Account (optional)</label>
                                <select name="default_cost_gl_account_id" id="default_cost_gl_account_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">-- None --</option>
                                    @foreach($glAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('default_cost_gl_account_id', $productType->default_cost_gl_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Default Sell GL Account -->
                            <div>
                                <label for="default_sell_gl_account_id" class="block text-sm font-medium text-gray-700">Default Sell GL Account (optional)</label>
                                <select name="default_sell_gl_account_id" id="default_sell_gl_account_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">-- None --</option>
                                    @foreach($glAccounts as $account)
                                        <option value="{{ $account->id }}" {{ old('default_sell_gl_account_id', $productType->default_sell_gl_account_id) == $account->id ? 'selected' : '' }}>
                                            {{ $account->account_number }} - {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                          <!-- Buttons with Update, Cancel and Delete -->
<div class="flex items-center justify-between gap-4 mt-8 pt-6 border-t border-gray-200">
    <!-- Left side: Update + Cancel -->
    <div class="flex items-center gap-4">
        <button type="submit"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
            Update Product Type
        </button>

        <a href="{{ route('admin.product_types.index') }}"
           class="text-gray-600 hover:text-gray-900">
            Cancel
        </a>
    </div>

 
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>