<x-app-layout>
    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-300">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-300">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        {{ $customer->company_name ?? $customer->name }}
                    </h1>
                    @if($customer->company_name && $customer->name)
                        <p class="text-sm text-gray-500 mt-0.5">{{ $customer->name }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.customers.edit', $customer) }}"
                       class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2">
                        Edit Customer
                    </a>
                    <a href="{{ route('admin.customers.index') }}"
                       class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 font-medium rounded-lg text-sm px-4 py-2">
                        Back to Customers
                    </a>
                </div>
            </div>

            {{-- Customer details card --}}
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-700 mb-4">Customer Details</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Type</p>
                        <p class="font-medium text-gray-900">{{ ucfirst($customer->customer_type ?? '—') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Status</p>
                        <p class="font-medium">
                            @if($customer->customer_status === 'inactive')
                                <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">Inactive</span>
                            @else
                                <span class="bg-green-100 text-green-700 text-xs font-medium px-2 py-0.5 rounded">{{ ucfirst($customer->customer_status ?? 'Active') }}</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Phone</p>
                        <p class="font-medium text-gray-900">{{ $customer->phone ?? $customer->mobile ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Email</p>
                        <p class="font-medium text-gray-900">
                            @if($customer->email)
                                <a href="mailto:{{ $customer->email }}" class="text-blue-600 hover:underline">{{ $customer->email }}</a>
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-500">Address</p>
                        <p class="font-medium text-gray-900">
                            {{ $customer->address ?? '' }}
                            @if($customer->city || $customer->province)
                                <br>{{ trim(($customer->city ?? '') . ' ' . ($customer->province ?? '')) }}
                            @endif
                            @if($customer->postal_code)
                                {{ $customer->postal_code }}
                            @endif
                            @if(!$customer->address && !$customer->city) —@endif
                        </p>
                    </div>
                    @if($customer->parent)
                        <div>
                            <p class="text-gray-500">Parent Customer</p>
                            <p class="font-medium text-gray-900">
                                <a href="{{ route('admin.customers.show', $customer->parent) }}" class="text-blue-600 hover:underline">
                                    {{ $customer->parent->company_name ?? $customer->parent->name }}
                                </a>
                            </p>
                        </div>
                    @endif
                    @if($customer->notes)
                        <div class="col-span-2 md:col-span-4">
                            <p class="text-gray-500">Notes</p>
                            <p class="font-medium text-gray-900">{{ $customer->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Opportunities as Main Customer --}}
            @if($opportunitiesAsParent->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-700">
                        Opportunities
                        <span class="ml-2 bg-blue-100 text-blue-700 text-xs font-medium px-2 py-0.5 rounded">{{ $opportunitiesAsParent->count() }}</span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Job #</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">RFMs</th>
                                <th class="px-6 py-3">Estimates</th>
                                <th class="px-6 py-3">POs</th>
                                <th class="px-6 py-3">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($opportunitiesAsParent as $opp)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $opp->job_no ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">
                                            {{ ucfirst($opp->status ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @forelse($opp->rfms as $rfm)
                                            <a href="{{ route('pages.opportunities.rfms.show', [$opp, $rfm]) }}"
                                               class="inline-block text-blue-600 hover:underline text-xs mr-1">
                                                RFM #{{ $rfm->id }}
                                                @if($rfm->scheduled_at)
                                                    ({{ $rfm->scheduled_at->format('M j') }})
                                                @endif
                                            </a>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-6 py-4">
                                        @forelse($opp->estimates as $est)
                                            <a href="{{ route('pages.estimates.show', $est) }}"
                                               class="inline-block text-blue-600 hover:underline text-xs mr-1">
                                                Est #{{ $est->estimate_number }}
                                            </a>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-6 py-4">
                                        @forelse($opp->purchaseOrders as $po)
                                            <a href="{{ route('pages.purchase-orders.show', $po) }}"
                                               class="inline-block text-blue-600 hover:underline text-xs mr-1">
                                                PO #{{ $po->po_number }}
                                            </a>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $opp->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('pages.opportunities.show', $opp) }}"
                                           class="font-medium text-blue-600 hover:underline">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Opportunities as Job Site --}}
            @if($opportunitiesAsJobSite->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-700">
                        Opportunities (as Job Site)
                        <span class="ml-2 bg-purple-100 text-purple-700 text-xs font-medium px-2 py-0.5 rounded">{{ $opportunitiesAsJobSite->count() }}</span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Job #</th>
                                <th class="px-6 py-3">Main Customer</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">RFMs</th>
                                <th class="px-6 py-3">Estimates</th>
                                <th class="px-6 py-3">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($opportunitiesAsJobSite as $opp)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        {{ $opp->job_no ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($opp->parentCustomer)
                                            <a href="{{ route('admin.customers.show', $opp->parentCustomer) }}"
                                               class="text-blue-600 hover:underline">
                                                {{ $opp->parentCustomer->company_name ?? $opp->parentCustomer->name }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">
                                            {{ ucfirst($opp->status ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @forelse($opp->rfms as $rfm)
                                            <a href="{{ route('pages.opportunities.rfms.show', [$opp, $rfm]) }}"
                                               class="inline-block text-blue-600 hover:underline text-xs mr-1">
                                                RFM #{{ $rfm->id }}
                                                @if($rfm->scheduled_at)
                                                    ({{ $rfm->scheduled_at->format('M j') }})
                                                @endif
                                            </a>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-6 py-4">
                                        @forelse($opp->estimates as $est)
                                            <a href="{{ route('pages.estimates.show', $est) }}"
                                               class="inline-block text-blue-600 hover:underline text-xs mr-1">
                                                Est #{{ $est->estimate_number }}
                                            </a>
                                        @empty
                                            <span class="text-gray-400">—</span>
                                        @endforelse
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $opp->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('pages.opportunities.show', $opp) }}"
                                           class="font-medium text-blue-600 hover:underline">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Sales --}}
            @if($sales->isNotEmpty())
            <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-base font-semibold text-gray-700">
                        Sales
                        <span class="ml-2 bg-green-100 text-green-700 text-xs font-medium px-2 py-0.5 rounded">{{ $sales->count() }}</span>
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Sale #</th>
                                <th class="px-6 py-3">Job Name</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Created</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        #{{ $sale->sale_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $sale->job_name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded">
                                            {{ ucfirst($sale->status ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $sale->customer_name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $sale->created_at->format('M j, Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="{{ route('pages.sales.show', $sale) }}"
                                           class="font-medium text-blue-600 hover:underline">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- No activity --}}
            @if($opportunitiesAsParent->isEmpty() && $opportunitiesAsJobSite->isEmpty() && $sales->isEmpty())
            <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-10 text-center text-gray-500">
                No activities found for this customer.
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
