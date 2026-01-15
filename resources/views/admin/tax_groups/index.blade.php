{{-- resources/views/admin/tax_groups/index.blade.php --}}
<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Header --}}
<div class="flex items-start justify-between gap-4 mb-6">
    <div class="flex items-start gap-4">
        <a href="{{ route('admin.tax.index') }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-800 bg-gray-200 rounded-lg hover:bg-gray-300">
            ← Back
        </a>

        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Tax Groups</h1>
            <p class="text-sm text-gray-600 mt-1">
                Create groups by selecting one or more Tax Rates. The group total is the sum of the selected sales rates.
            </p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tax_groups.create') }}"
           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            Create Tax Group
        </a>
    </div>
</div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Filters --}}
            <div class="bg-white border border-gray-200 rounded-lg p-4 mb-4">
                <form method="GET" action="{{ route('admin.tax_groups.index') }}" class="flex items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox"
                               name="show_archived"
                               value="1"
                               {{ $showArchived ? 'checked' : '' }}
                               onchange="this.form.submit()"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                        Show archived
                    </label>
                    <div class="text-sm text-gray-500">
                        {{ $groups->total() }} total
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-700">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3">Name</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 w-40 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($groups as $g)
                                @php
                                    $isArchived = !is_null($g->deleted_at);
                                    $isDefault = ($defaultGroupId && (int)$defaultGroupId === (int)$g->id);
                                @endphp
                                <tr class="border-b last:border-b-0">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span>{{ $g->name }}</span>
                                            @if ($isDefault)
                                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-blue-100 text-blue-800">
                                                    Default
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">
                                        {{ $g->description ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if ($isArchived)
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-800">
                                                Archived
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            @if (!$isArchived)
												<a href="{{ route('admin.tax_groups.edit', $g->id) }}"
												   class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
													Edit
												</a>
											@endif

                                            @if ($isArchived)
                                                <form method="POST" action="{{ route('admin.tax_groups.restore', $g->id) }}"
                                                      onsubmit="return confirm('Restore this tax group?');">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-white bg-green-600 border border-gray-300 rounded-lg hover:bg-green-500">
                                                        Restore
                                                    </button>
                                                </form>
                                            @endif

                                            @if (!$isArchived)
                                                <form method="POST" action="{{ route('admin.tax_groups.destroy', $g->id) }}"
                                                      onsubmit="return confirm('Archive this tax group?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center justify-center px-3 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                                        Archive
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                        No tax groups found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($groups->hasPages())
                    <div class="p-4 border-t">
                        {{ $groups->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-admin-layout>