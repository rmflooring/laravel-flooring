{{-- resources/views/pages/opportunities/show.blade.php --}}
<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header (Flowbite style) --}}
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Opportunity #{{ $opportunity->id }}
                    </h1>

                    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <span>Status:</span>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                            {{ $opportunity->status ?? 'New' }}
                        </span>

                        @if($opportunity->job_no)
                            <span class="text-gray-400 dark:text-gray-500">•</span>
                            <span>Job No:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $opportunity->job_no }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.opportunities.index') }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                        Back
                    </a>

                    <a href="{{ route('pages.opportunities.edit', $opportunity->id) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Edit
                    </a>
                </div>
            </div>

            {{-- Flash (Flowbite alert) --}}
            @if (session('success'))
                <div class="mb-6 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400"
                     role="alert">
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            {{-- Opportunity Details (Flowbite card) --}}
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Opportunity Details</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Customer + job site + PM details.</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

                        {{-- Parent Customer --}}
                        <div class="lg:col-span-6">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/30">
                                <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Parent Customer</h3>

                                @if($opportunity->parentCustomer)
                                    <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $opportunity->parentCustomer->company_name ?: $opportunity->parentCustomer->name }}
                                        </div>

                                        @if($opportunity->parentCustomer->phone)
                                            <div><span class="text-gray-500 dark:text-gray-400">Phone:</span> {{ $opportunity->parentCustomer->phone }}</div>
                                        @endif
                                        @if($opportunity->parentCustomer->email)
                                            <div><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $opportunity->parentCustomer->email }}</div>
                                        @endif
                                        @if($opportunity->parentCustomer->address || $opportunity->parentCustomer->city)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Address:</span>
                                                {{ $opportunity->parentCustomer->address }}
                                                {{ $opportunity->parentCustomer->city ? ', '.$opportunity->parentCustomer->city : '' }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No parent customer selected.</p>
                                @endif
                            </div>
                        </div>

                        {{-- Job Site Customer --}}
                        <div class="lg:col-span-6">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-900/30">
                                <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">Job Site Customer</h3>

                                @if($opportunity->jobSiteCustomer)
                                    <div class="space-y-1 text-sm text-gray-700 dark:text-gray-200">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $opportunity->jobSiteCustomer->name ?: ($opportunity->jobSiteCustomer->company_name ?? 'Job Site') }}
                                        </div>

                                        @if($opportunity->jobSiteCustomer->phone)
                                            <div><span class="text-gray-500 dark:text-gray-400">Phone:</span> {{ $opportunity->jobSiteCustomer->phone }}</div>
                                        @endif
                                        @if($opportunity->jobSiteCustomer->email)
                                            <div><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $opportunity->jobSiteCustomer->email }}</div>
                                        @endif
                                        @if($opportunity->jobSiteCustomer->address || $opportunity->jobSiteCustomer->city)
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400">Address:</span>
                                                {{ $opportunity->jobSiteCustomer->address }}
                                                {{ $opportunity->jobSiteCustomer->city ? ', '.$opportunity->jobSiteCustomer->city : '' }}
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No job site selected yet.</p>
                                @endif
                            </div>
                        </div>

                        {{-- PM + Sales (Flowbite stat cards) --}}
                        <div class="lg:col-span-12">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">

                                <div class="md:col-span-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">Project Manager</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $opportunity->projectManager->name ?? '—' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">Sales Person 1</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $salesPeople[$opportunity->sales_person_1]->first_name ?? '' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="mb-1 text-xs text-gray-500 dark:text-gray-400">Sales Person 2</div>
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $salesPeople[$opportunity->sales_person_2]->first_name ?? '' }}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Job Transactions (Flowbite card + table) --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Job Transactions</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">RFQs, estimates, sales, invoices, POs, and work orders linked to this opportunity.</p>
                    </div>

                    <a href="{{ url('/pages/estimates/create') }}?opportunity_id={{ $opportunity->id }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        + Create Estimate
                    </a>
                </div>

                <div class="p-6">
                    <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">RFQ's</th>
                                    <th scope="col" class="px-6 py-3">Estimates</th>
                                    <th scope="col" class="px-6 py-3">Sales</th>
                                    <th scope="col" class="px-6 py-3">Invoices</th>
                                    <th scope="col" class="px-6 py-3">PO's</th>
                                    <th scope="col" class="px-6 py-3">WO's</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b bg-white align-top dark:border-gray-700 dark:bg-gray-800">
                                    {{-- RFQs --}}
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">—</td>

                                    {{-- Estimates --}}
                                    <td class="px-6 py-4">
										@php
											$estimateSort = request('estimate_sort', 'desc'); // desc = newest first
											$sortedEstimates = $opportunity->estimates
												->sortBy('created_at')
												->values();

											if ($estimateSort === 'desc') {
												$sortedEstimates = $sortedEstimates->reverse()->values();
											}
										@endphp

										<div class="mb-2">
											<a href="{{ request()->fullUrlWithQuery(['estimate_sort' => ($estimateSort === 'desc' ? 'asc' : 'desc')]) }}"
											   class="inline-flex items-center text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
												Sort: {{ $estimateSort === 'desc' ? 'Newest → Oldest' : 'Oldest → Newest' }}
											</a>
										</div>

                                        @if($opportunity->estimates && $opportunity->estimates->count())
                                            <ul class="list-disc space-y-1 pl-5 text-gray-700 dark:text-gray-200">
                                                @foreach($sortedEstimates as $estimate)
                                                    <li>
                                                        <a href="{{ route('admin.estimates.edit', $estimate->id) }}"
														   class="font-medium text-blue-600 hover:underline dark:text-blue-400">
															{{ $estimate->estimate_number ?? ('Draft #'.$estimate->id) }}
														</a>
																		<span class="text-gray-600 dark:text-gray-300">
                                                            ({{ $estimate->grand_total ?? $estimate->total_amount ?? '—' }})
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>

                                    {{-- Sales --}}
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">—</td>

                                    {{-- Invoices --}}
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">—</td>

                                    {{-- POs --}}
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">—</td>

                                    {{-- WOs --}}
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">—</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Opportunity Actions (Flowbite buttons) --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Opportunity Actions</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Quick links to manage documents/media and create transactions.</p>
                </div>

                <div class="p-6">
                    <div class="flex flex-wrap gap-3">

                        {{-- Documents / Media (Primary) --}}
                        <a href="{{ route('pages.opportunities.documents.index', ['opportunity' => $opportunity->id]) }}"
                           class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Documents
                        </a>
						
						<a href="{{ route('pages.opportunities.media.index', ['opportunity' => $opportunity->id]) }}"
                           class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Media
                        </a>
						
                        {{-- Create Estimate (Secondary) --}}
                        <a href="{{ url('/admin/estimates/mock-create') }}?opportunity_id={{ $opportunity->id }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            Create Estimate
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
