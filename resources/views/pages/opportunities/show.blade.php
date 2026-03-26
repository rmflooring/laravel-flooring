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

                        @if($opportunity->status_reason && in_array($opportunity->status, ['Lost', 'Closed']))
                            <span class="text-gray-400 dark:text-gray-500">—</span>
                            <span class="text-sm text-gray-600 dark:text-gray-400 italic">{{ $opportunity->status_reason }}</span>
                        @endif

                        @if($opportunity->job_no)
                            <span class="text-gray-400 dark:text-gray-500">•</span>
                            <span>Job No:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $opportunity->job_no }}</span>
                        @endif

                        @if($navPosition && $navTotal > 1)
                            <span class="text-gray-400 dark:text-gray-500">•</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $navPosition }} of {{ $navTotal }}</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    {{-- Prev/Next navigation --}}
                    @php $qs = http_build_query(array_filter($filterParams)); @endphp

                    @if($prev)
                        <a href="{{ route('pages.opportunities.show', $prev->id) }}{{ $qs ? '?' . $qs : '' }}"
                           title="Previous: {{ $prev->job_no ?: 'Opp #' . $prev->id }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            ← Prev
                        </a>
                    @else
                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                            ← Prev
                        </span>
                    @endif

                    @if($next)
                        <a href="{{ route('pages.opportunities.show', $next->id) }}{{ $qs ? '?' . $qs : '' }}"
                           title="Next: {{ $next->job_no ?: 'Opp #' . $next->id }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            Next →
                        </a>
                    @else
                        <span class="inline-flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-400 cursor-not-allowed">
                            Next →
                        </span>
                    @endif

                    <a href="{{ $backUrl }}"
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
                                        @php
                                            $pcAddr = array_filter([
                                                $opportunity->parentCustomer->address,
                                                $opportunity->parentCustomer->address2,
                                                $opportunity->parentCustomer->city,
                                                $opportunity->parentCustomer->postal_code,
                                            ]);
                                        @endphp
                                        @if($pcAddr)
                                            <div class="pt-0.5">
                                                @if($opportunity->parentCustomer->address)
                                                    <div>{{ $opportunity->parentCustomer->address }}</div>
                                                @endif
                                                @if($opportunity->parentCustomer->address2)
                                                    <div>{{ $opportunity->parentCustomer->address2 }}</div>
                                                @endif
                                                @if($opportunity->parentCustomer->city || $opportunity->parentCustomer->postal_code)
                                                    <div>{{ implode(', ', array_filter([$opportunity->parentCustomer->city, $opportunity->parentCustomer->postal_code])) }}</div>
                                                @endif
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
                                        @if($opportunity->jobSiteCustomer->mobile)
                                            <div><span class="text-gray-500 dark:text-gray-400">Mobile:</span> {{ $opportunity->jobSiteCustomer->mobile }}</div>
                                        @endif
                                        @if($opportunity->jobSiteCustomer->email)
                                            <div><span class="text-gray-500 dark:text-gray-400">Email:</span> {{ $opportunity->jobSiteCustomer->email }}</div>
                                        @endif
                                        @php
                                            $jsAddr = array_filter([
                                                $opportunity->jobSiteCustomer->address,
                                                $opportunity->jobSiteCustomer->address2,
                                                $opportunity->jobSiteCustomer->city,
                                                $opportunity->jobSiteCustomer->postal_code,
                                            ]);
                                        @endphp
                                        @if($jsAddr)
                                            <div class="pt-0.5">
                                                @if($opportunity->jobSiteCustomer->address)
                                                    <div>{{ $opportunity->jobSiteCustomer->address }}</div>
                                                @endif
                                                @if($opportunity->jobSiteCustomer->address2)
                                                    <div>{{ $opportunity->jobSiteCustomer->address2 }}</div>
                                                @endif
                                                @if($opportunity->jobSiteCustomer->city || $opportunity->jobSiteCustomer->postal_code)
                                                    <div>{{ implode(', ', array_filter([$opportunity->jobSiteCustomer->city, $opportunity->jobSiteCustomer->postal_code])) }}</div>
                                                @endif
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
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">RFMs, estimates, sales, invoices, POs, and work orders linked to this opportunity.</p>
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
                                    <th scope="col" class="px-6 py-3">RFM's</th>
                                    <th scope="col" class="px-6 py-3">Estimates</th>
                                    <th scope="col" class="px-6 py-3">Sales</th>
                                    <th scope="col" class="px-6 py-3">Invoices</th>
                                    <th scope="col" class="px-6 py-3">PO's</th>
                                    <th scope="col" class="px-6 py-3">WO's</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b bg-white align-top dark:border-gray-700 dark:bg-gray-800">
                                    {{-- RFMs --}}
                                    <td class="px-6 py-4">
                                        @if($opportunity->rfms->count())
                                            <ul class="list-disc space-y-1 pl-5 text-gray-700 dark:text-gray-200">
                                                @foreach($opportunity->rfms->sortBy('scheduled_at') as $rfmItem)
                                                    @php
                                                        $rfmStatusColors = [
                                                            'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                                                            'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                                            'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                                        ];
                                                        $rfmStatusColor = $rfmStatusColors[$rfmItem->status] ?? 'bg-gray-100 text-gray-800';
                                                    @endphp
                                                    <li>
                                                        <a href="{{ route('pages.opportunities.rfms.show', [$opportunity->id, $rfmItem->id]) }}"
                                                           class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                            {{ $rfmItem->scheduled_at->format('M j, Y') }}
                                                        </a>
                                                        <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $rfmStatusColor }}">
                                                            {{ ucfirst($rfmItem->status) }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>

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
                                                        <a href="{{ route('pages.estimates.edit', $estimate->id) }}"
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
<td class="px-6 py-4">
    @if(isset($sales) && $sales->count())
        <ul class="list-disc space-y-1 pl-5 text-gray-700 dark:text-gray-200">
            @foreach($sales as $sale)
                @php
                    // Display total: revised -> locked -> current
                    $displayTotal = $sale->revised_contract_total
                        ?? $sale->locked_grand_total
                        ?? $sale->grand_total
                        ?? 0;

                    $locked = !is_null($sale->locked_at);

                    $invoiceLabel = '';
                    if (!empty($sale->is_fully_invoiced)) $invoiceLabel = 'Fully invoiced';
                    elseif (($sale->invoiced_total ?? 0) > 0) $invoiceLabel = 'Partially invoiced';
                @endphp

                <li>
                    <a href="{{ route('pages.sales.edit', $sale->id) }}"
                       class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                        {{ $sale->sale_number ?? ('Sale #'.$sale->id) }}
                    </a>

                    <span class="text-gray-600 dark:text-gray-300">
                        (${{ number_format((float)$displayTotal, 2) }})
                    </span>

                    @if($locked)
                        <span class="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-medium text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200">
                            Locked
                        </span>
                    @endif

                    @if($sale->status)
                        <span class="ml-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-100">
                            {{ $sale->status }}
                        </span>
                    @endif

                    @if($invoiceLabel)
                        <span class="ml-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-800 dark:bg-green-900/40 dark:text-green-200">
                            {{ $invoiceLabel }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <span class="text-gray-400 dark:text-gray-500">—</span>
    @endif
</td>

                                    {{-- Invoices --}}
                                    <td class="px-6 py-4 text-gray-400 dark:text-gray-500">—</td>

                                    {{-- POs --}}
                                    <td class="px-6 py-4">
                                        @if($purchaseOrders->isNotEmpty())
                                            @php
                                                $poStatusColors = [
                                                    'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                                                    'ordered'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                                    'received'  => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                                ];
                                            @endphp
                                            <ul class="list-disc space-y-1 pl-5 text-gray-700 dark:text-gray-200">
                                                @foreach($purchaseOrders as $po)
                                                    <li>
                                                        <a href="{{ route('pages.purchase-orders.show', $po) }}"
                                                           class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                            {{ $po->po_number }}
                                                        </a>
                                                        <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium {{ $poStatusColors[$po->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                            {{ $po->status_label }}
                                                        </span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">—</span>
                                        @endif
                                    </td>

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
                        <a href="{{ route('pages.estimates.create') }}?opportunity_id={{ $opportunity->id }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            Create Estimate
                        </a>

                        {{-- Request for Measure --}}
                        <a href="{{ route('pages.opportunities.rfms.create', $opportunity->id) }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-gray-700">
                            + Request for Measure
                        </a>

                    </div>
                </div>
            </div>

            {{-- RFMs --}}
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Requests for Measure</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Site measure appointments linked to this opportunity.</p>
                    </div>
                    <a href="{{ route('pages.opportunities.rfms.create', $opportunity->id) }}"
                       class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        + Request for Measure
                    </a>
                </div>

                <div class="p-6">
                    @if($opportunity->rfms->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No RFMs yet.</p>
                    @else
                        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">Scheduled</th>
                                        <th class="px-4 py-3">Estimator</th>
                                        <th class="px-4 py-3">Flooring Type</th>
                                        <th class="px-4 py-3">Site Address</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($opportunity->rfms as $rfm)
                                        @php
                                            $statusColors = [
                                                'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                                                'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                                'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                            ];
                                            $statusColor = $statusColors[$rfm->status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <tr class="border-b bg-white last:border-0 dark:border-gray-700 dark:bg-gray-800">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                {{ $rfm->scheduled_at->format('M j, Y g:i A') }}
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ $rfm->estimator?->first_name }} {{ $rfm->estimator?->last_name }}
                                            </td>
                                            <td class="px-4 py-3">{{ implode(', ', (array) $rfm->flooring_type) }}</td>
                                            <td class="px-4 py-3">
                                                @php
                                                    $addrParts = array_filter([$rfm->site_address, $rfm->site_city, $rfm->site_postal_code]);
                                                @endphp
                                                {{ $addrParts ? implode(', ', $addrParts) : '—' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <form method="POST"
                                                      action="{{ route('pages.opportunities.rfms.updateStatus', [$opportunity->id, $rfm->id]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="status" onchange="this.form.submit()"
                                                            class="rounded-full px-2.5 py-0.5 text-xs font-medium border-0 cursor-pointer focus:ring-2 focus:ring-blue-300 {{ $statusColor }}">
                                                        @foreach(\App\Models\Rfm::STATUSES as $s)
                                                            <option value="{{ $s }}" {{ $rfm->status === $s ? 'selected' : '' }}>
                                                                {{ ucfirst($s) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 flex items-center gap-3">
                                                <a href="{{ route('pages.opportunities.rfms.show', [$opportunity->id, $rfm->id]) }}"
                                                   class="text-sm font-medium text-gray-600 hover:underline dark:text-gray-400">
                                                    View
                                                </a>
                                                <a href="{{ route('pages.opportunities.rfms.edit', [$opportunity->id, $rfm->id]) }}"
                                                   class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Purchase Orders --}}
            @can('view purchase orders')
            <div class="mt-6 rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-6 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Purchase Orders</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">All POs raised across sales on this opportunity.</p>
                    </div>
                </div>

                <div class="p-6">
                    @if($purchaseOrders->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">No purchase orders yet.</p>
                    @else
                        @php
                            $poStatusColors = [
                                'pending'   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                                'ordered'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                'received'  => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                            ];
                        @endphp
                        <div class="relative overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-3">PO Number</th>
                                        <th class="px-4 py-3">Vendor</th>
                                        <th class="px-4 py-3">Sale</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Fulfillment</th>
                                        <th class="px-4 py-3">Expected</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchaseOrders as $po)
                                        <tr class="border-b bg-white last:border-0 dark:border-gray-700 dark:bg-gray-800">
                                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                                {{ $po->po_number }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-700 dark:text-gray-200">
                                                {{ $po->vendor->company_name }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <a href="{{ route('pages.sales.show', $po->sale) }}"
                                                   class="font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                    {{ $po->sale->sale_number }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $poStatusColors[$po->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ $po->status_label }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                                {{ $po->fulfillment_label }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                {{ $po->expected_delivery_date?->format('M j, Y') ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 flex items-center gap-3">
                                                <a href="{{ route('pages.purchase-orders.show', $po) }}"
                                                   class="text-sm font-medium text-gray-600 hover:underline dark:text-gray-400">
                                                    View
                                                </a>
                                                @can('edit purchase orders')
                                                <a href="{{ route('pages.purchase-orders.edit', $po) }}"
                                                   class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                                                    Edit
                                                </a>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
            @endcan

        </div>
    </div>
</x-app-layout>
