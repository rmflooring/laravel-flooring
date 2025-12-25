<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Edit Role: {{ $role->name }}</h1>

                    <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role Name</label>
                                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <h2 class="text-2xl font-semibold mb-4">Assign Permissions</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                            @foreach(\Spatie\Permission\Models\Permission::all() as $permission)
                                <label class="flex items-center space-x-3">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}"
                                        {{ $role->permissions->contains($permission) ? 'checked' : '' }}
                                        class="h-6 w-6 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-base font-medium text-gray-900">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-start gap-8">
                            <a href="{{ route('admin.roles.index') }}" class="px-10 py-5 text-lg font-bold text-white bg-gray-800 rounded-lg shadow-lg hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300">
                                Cancel
                            </a>
                            <button type="submit" class="px-10 py-5 text-lg font-bold text-white bg-green-600 rounded-lg shadow-lg hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300">
                                Update Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
