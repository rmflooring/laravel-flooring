<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1800px;">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">

                    {{-- Flash messages --}}
                    @if(session('success'))
                        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-300">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-300">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Header -->
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Manage Project Managers</h1>
                        <a href="{{ route('admin.project_managers.create') }}"
                           class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5">
                            Add New Project Manager
                        </a>
                    </div>

                    <!-- Filters / Search -->
                    @include('admin.partials.filter-bar', [
                        'action' => route('admin.project_managers.index'),
                        'searchPlaceholder' => 'Name, email, phone...',
                        'searchValue' => request('search'),
                        'perPageValue' => request('perPage', 15),
                        'perPageOptions' => [15, 25, 50, 100],
                        'selects' => [],
                    ])

                    <!-- Table -->
                    <div class="relative overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">
                                        @include('admin.partials.sort-link', ['label' => 'Name', 'field' => 'name'])
                                    </th>
                                    <th scope="col" class="px-4 py-3">Customer</th>
                                    <th scope="col" class="px-4 py-3">Phone / Mobile</th>
                                    <th scope="col" class="px-4 py-3">
                                        @include('admin.partials.sort-link', ['label' => 'Email', 'field' => 'email'])
                                    </th>
                                    <th scope="col" class="px-4 py-3">Created By</th>
                                    <th scope="col" class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pms as $pm)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                            {{ $pm->name }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $pm->customer?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $pm->phone ?? $pm->mobile ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $pm->email ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            {{ $pm->creator?->name ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <a href="{{ route('admin.project_managers.edit', $pm) }}"
                                               class="font-medium text-blue-600 hover:underline mr-3">Edit</a>
                                            <form action="{{ route('admin.project_managers.destroy', $pm) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="font-medium text-red-600 hover:underline"
                                                        onclick="return confirm('Are you sure?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b">
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            No project managers found.
                                            <a href="{{ route('admin.project_managers.create') }}" class="text-blue-600 hover:underline">
                                                Add the first one
                                            </a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $pms->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
