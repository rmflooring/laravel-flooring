<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $vendorRep->name }}
                    </h1>
                    @if($vendorRep->vendors->isNotEmpty())
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $vendorRep->vendors->pluck('company_name')->implode(', ') }}
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.vendor_reps.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Back to Vendor Reps
                    </a>
                    <a href="{{ route('admin.vendor_reps.edit', $vendorRep) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Edit
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Left: Contact details + Notes --}}
                <div class="lg:col-span-2 flex flex-col gap-6">

                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Contact Details</h2>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    @if($vendorRep->email)
                                        <a href="mailto:{{ $vendorRep->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $vendorRep->email }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendorRep->phone ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mobile</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendorRep->mobile ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vendor</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    @if($vendorRep->vendors->isNotEmpty())
                                        @foreach($vendorRep->vendors as $vendor)
                                            <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $vendor->company_name }}</a>{{ !$loop->last ? ', ' : '' }}
                                        @endforeach
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    @if($vendorRep->notes)
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $vendorRep->notes }}</p>
                    </div>
                    @endif

                </div>

                {{-- Right: Record info --}}
                <div>
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Record Info</h2>

                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Created By</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendorRep->creator?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendorRep->created_at->format('M j, Y') }}</dd>
                            </div>
                            @if($vendorRep->updater && $vendorRep->updated_at != $vendorRep->created_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Updated By</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendorRep->updater->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendorRep->updated_at->format('M j, Y') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
