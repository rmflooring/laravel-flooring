<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Labour Types</h1>
                        <a href="{{ route('admin.labour_types.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">
                            Add New Labour Type
                        </a>
                    </div>
				
					{{-- ================= FILTER BAR GOES HERE ================= --}}

					<form method="GET" action="{{ route('admin.labour_types.index') }}"
						  class="border border-gray-200 rounded-2xl p-6 mb-6 bg-white">

						<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
							<div>
								<label class="block text-sm font-medium mb-2">Search</label>
								<input type="text"
									   name="search"
									   value="{{ request('search') }}"
									   placeholder="Name, notes, creator..."
									   class="w-full rounded-lg border-gray-300 focus:ring-4 focus:ring-blue-300" />
							</div>

							<div>
								<label class="block text-sm font-medium mb-2">Per Page</label>
								<select name="per_page"
										class="w-full rounded-lg border-gray-300 focus:ring-4 focus:ring-blue-300">
									@foreach([10,15,25,50,100] as $n)
										<option value="{{ $n }}" @selected((int)request('per_page', 15) === $n)>{{ $n }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="mt-6 flex gap-3">
							<button class="bg-blue-700 text-white px-5 py-2.5 rounded-lg hover:bg-blue-800">
								Apply
							</button>

							<a href="{{ route('admin.labour_types.index') }}"
							   class="border px-5 py-2.5 rounded-lg hover:bg-gray-50">
								Reset
							</a>
						</div>
					</form>

					{{-- ================= END FILTER BAR ================= --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($types as $type)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $type->name }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $type->notes ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $type->creator?->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.labour_types.edit', $type) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <form action="{{ route('admin.labour_types.destroy', $type) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                            No labour types found. <a href="{{ route('admin.labour_types.create') }}" class="text-indigo-600 hover:text-indigo-900">Add the first one</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $types->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
