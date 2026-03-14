<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $installer->company_name }}</h1>
                        @if ($installer->status === 'active')
                            <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
                        @else
                            <span class="inline-flex rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">Inactive</span>
                        @endif
                    </div>
                    @if ($installer->vendor)
                        <p class="mt-1 text-sm text-blue-600 dark:text-blue-400">
                            Linked vendor: {{ $installer->vendor->company_name }}
                        </p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.installers.edit', $installer) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Edit
                    </a>
                    <a href="{{ route('admin.installers.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                        Back to Installers
                    </a>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- Contact Info --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Contact Information</h2>
                    </div>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->company_name }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Contact</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->contact_name ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->phone ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Mobile</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->mobile ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">
                                @if ($installer->email)
                                    <a href="mailto:{{ $installer->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $installer->email }}</a>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Address --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Address</h2>
                    </div>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Street</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->address ?? '—' }}</dd>
                        </div>
                        @if ($installer->address2)
                            <div class="grid grid-cols-3 gap-4 px-6 py-3">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Address 2</dt>
                                <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->address2 }}</dd>
                            </div>
                        @endif
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">City</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->city ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Province</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">
                                {{ $provinces[$installer->province] ?? $installer->province ?? '—' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Postal Code</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->postal_code ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Account & Financial --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Account & Financial</h2>
                    </div>
                    <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Account #</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->account_number ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">GST #</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->gst_number ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Terms</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">{{ $installer->terms ?? '—' }}</dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">GL Cost Account</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">
                                @if ($installer->glCostAccount)
                                    {{ $installer->glCostAccount->account_number }} — {{ $installer->glCostAccount->name }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-3 gap-4 px-6 py-3">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">GL Sale Account</dt>
                            <dd class="col-span-2 text-sm text-gray-900 dark:text-white">
                                @if ($installer->glSaleAccount)
                                    {{ $installer->glSaleAccount->account_number }} — {{ $installer->glSaleAccount->name }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Notes --}}
                @if ($installer->notes)
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $installer->notes }}</p>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Meta / Audit --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 px-6 py-3 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500">
                Created by {{ $installer->creator?->name ?? 'system' }} on {{ $installer->created_at?->format('M j, Y') }}
                @if ($installer->updater && $installer->updated_at?->ne($installer->created_at))
                    &nbsp;·&nbsp; Last updated by {{ $installer->updater->name }} on {{ $installer->updated_at->format('M j, Y') }}
                @endif
            </div>

            {{-- Danger zone --}}
            <div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-6 dark:border-red-900 dark:bg-gray-800">
                <h3 class="mb-2 text-sm font-semibold text-red-800 dark:text-red-400">Danger Zone</h3>
                <p class="mb-4 text-sm text-red-600 dark:text-red-400">Permanently delete this installer. This cannot be undone.</p>
                <form action="{{ route('admin.installers.destroy', $installer) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            onclick="return confirm('Delete {{ addslashes($installer->company_name) }}? This cannot be undone.')"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 dark:focus:ring-red-900">
                        Delete Installer
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
