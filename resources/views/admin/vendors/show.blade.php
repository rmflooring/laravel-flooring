<x-app-layout>
    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $vendor->company_name }}
                    </h1>
                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        @if($vendor->vendor_type)
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                {{ $vendor->vendor_type }}
                            </span>
                        @endif
                        @if($vendor->status)
                            @php
                                $statusColor = match($vendor->status) {
                                    'active'   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                    'inactive' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                    default    => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                                {{ ucfirst($vendor->status) }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.vendors.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Back to Vendors
                    </a>
                    <a href="{{ route('admin.vendors.edit', $vendor) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Edit Vendor
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Left column: Contact & Address --}}
                <div class="lg:col-span-2 flex flex-col gap-6">

                    {{-- Contact details --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Contact Details</h2>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contact Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->contact_name ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    @if($vendor->email)
                                        <a href="mailto:{{ $vendor->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $vendor->email }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->phone ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mobile</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->mobile ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Website</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                                    @if($vendor->website)
                                        <a href="{{ $vendor->website }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline dark:text-blue-400">{{ $vendor->website }}</a>
                                    @else
                                        —
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Address --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Address</h2>

                        @if($vendor->address || $vendor->city || $vendor->province || $vendor->postal_code)
                            <address class="not-italic text-sm text-gray-900 dark:text-white leading-relaxed">
                                @if($vendor->address) {{ $vendor->address }}<br>@endif
                                @if($vendor->address2) {{ $vendor->address2 }}<br>@endif
                                @if($vendor->city || $vendor->province || $vendor->postal_code)
                                    {{ implode(', ', array_filter([$vendor->city, $vendor->province])) }}
                                    {{ $vendor->postal_code }}
                                @endif
                            </address>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500">No address on file.</p>
                        @endif
                    </div>

                    {{-- Account & Terms --}}
                    @if($vendor->account_number || $vendor->terms)
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Account Info</h2>

                        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Account Number</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->account_number ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Terms</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->terms ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                    @endif

                    {{-- Notes --}}
                    @if($vendor->notes)
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Notes</h2>
                        <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $vendor->notes }}</p>
                    </div>
                    @endif

                </div>

                {{-- Right column: Reps + Meta --}}
                <div class="flex flex-col gap-6">

                    {{-- Vendor Reps --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Vendor Reps</h2>

                        @forelse($vendor->reps as $rep)
                            <div class="mb-3 last:mb-0 rounded-lg bg-gray-50 p-3 dark:bg-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $rep->name }}</p>
                                @if($rep->email)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        <a href="mailto:{{ $rep->email }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $rep->email }}</a>
                                    </p>
                                @endif
                                @if($rep->phone || $rep->mobile)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $rep->phone ?: $rep->mobile }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 dark:text-gray-500">No reps assigned.</p>
                        @endforelse
                    </div>

                    {{-- Record info --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="mb-4 text-base font-semibold text-gray-900 dark:text-white">Record Info</h2>

                        <dl class="space-y-3">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Created By</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->creator?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Created</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->created_at->format('M j, Y') }}</dd>
                            </div>
                            @if($vendor->updater && $vendor->updated_at != $vendor->created_at)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Updated By</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->updater->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Updated</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $vendor->updated_at->format('M j, Y') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
