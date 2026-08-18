<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knowledge Agent Tool Access</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Which roles can use each live-data tool in the staff chat assistant. The admin role always
                        has full access. Knowledge-base search visibility is set per-entry instead — see
                        <a href="{{ route('admin.knowledge.index') }}" class="text-blue-600 hover:underline dark:text-blue-400">Knowledge Base</a>.
                    </p>
                </div>
                <a href="{{ route('admin.settings') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">
                    Back
                </a>
            </div>

            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.settings.knowledge-agent.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400 border-b dark:border-gray-600">
                                <tr>
                                    <th class="px-6 py-3">Role</th>
                                    @foreach ($tools as $tool)
                                        <th class="px-6 py-3 text-center">{{ ucwords(str_replace('_', ' ', $tool)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $role)
                                    <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ ucfirst($role) }}</td>
                                        @foreach ($tools as $tool)
                                            <td class="px-6 py-4 text-center">
                                                <input type="hidden" name="access[{{ $role }}][{{ $tool }}]" value="0">
                                                <input type="checkbox" name="access[{{ $role }}][{{ $tool }}]" value="1"
                                                    {{ ($access[$role][$tool] ?? false) ? 'checked' : '' }}
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($tools) + 1 }}" class="px-6 py-10 text-center text-gray-500">
                                            No non-admin roles found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                        Save Access
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
