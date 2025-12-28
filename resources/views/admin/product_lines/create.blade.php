<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add New Product Line
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    @if ($errors->any())
                        <div class="mb-4 text-red-600">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.product-lines.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="product_type_id" class="block font-medium text-gray-700">Product Type</label>
                            <select name="product_type_id" id="product_type_id" class="border-gray-300 rounded mt-1 w-full">
                                <option value="">-- Select Product Type --</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ old('product_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="name" class="block font-medium text-gray-700">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="border-gray-300 rounded mt-1 w-full" required>
                        </div>

                        <div class="mb-4">
                            <label for="vendor" class="block font-medium text-gray-700">Vendor</label>
                            <input type="text" name="vendor" id="vendor" value="{{ old('vendor') }}" class="border-gray-300 rounded mt-1 w-full">
                        </div>

                        <div class="mb-4">
                            <label for="manufacturer" class="block font-medium text-gray-700">Manufacturer</label>
                            <input type="text" name="manufacturer" id="manufacturer" value="{{ old('manufacturer') }}" class="border-gray-300 rounded mt-1 w-full">
                        </div>

                        <div class="mb-4">
                            <label for="model" class="block font-medium text-gray-700">Model</label>
                            <input type="text" name="model" id="model" value="{{ old('model') }}" class="border-gray-300 rounded mt-1 w-full">
                        </div>

                        <div class="mb-4">
                            <label for="collection" class="block font-medium text-gray-700">Collection</label>
                            <input type="text" name="collection" id="collection" value="{{ old('collection') }}" class="border-gray-300 rounded mt-1 w-full">
                        </div>

                        <div class="mb-4">
                            <label for="status" class="block font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="border-gray-300 rounded mt-1 w-full">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="mt-6">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save Product Line</button>
                            <a href="{{ route('admin.product-lines.index') }}" class="ml-2 px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
