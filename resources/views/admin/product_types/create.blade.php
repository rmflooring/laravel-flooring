<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Add New Product Type</h1>

                    <form method="POST" action="{{ route('admin.product_types.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Product Type Name -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Product Type Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Ordered By -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ordered By *</label>
                                <select name="ordered_by_unit_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select Unit --</option>
                                    @foreach($unitMeasures as $unit)
                                        <option value="{{ $unit->id }}" {{ old('ordered_by_unit_id') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ordered_by_unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Sold By -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sold By *</label>
                                <select name="sold_by_unit_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select Unit --</option>
                                    @foreach($unitMeasures as $unit)
                                        <option value="{{ $unit->id }}" {{ old('sold_by_unit_id') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('sold_by_unit_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Default Cost GL Account -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Default Cost GL Account</label>
                                <select name="default_cost_gl_account_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select GL Account --</option>
                                    @foreach($glAccounts as $gl)
                                        <option value="{{ $gl->id }}" {{ old('default_cost_gl_account_id') == $gl->id ? 'selected' : '' }}>
                                            {{ $gl->account_number }} - {{ $gl->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('default_cost_gl_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <!-- Default Sell GL Account -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Default Sell GL Account</label>
                                <select name="default_sell_gl_account_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Select GL Account --</option>
                                    @foreach($glAccounts as $gl)
                                        <option value="{{ $gl->id }}" {{ old('default_sell_gl_account_id') == $gl->id ? 'selected' : '' }}>
                                            {{ $gl->account_number }} - {{ $gl->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('default_sell_gl_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('admin.product_types.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg">
                                Create Product Type
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
