@php
    $badge = ['new' => 'background:#fef3c7;color:#92400e', 'confirmed' => 'background:#dbeafe;color:#1e40af', 'ready' => 'background:#dcfce7;color:#166534', 'picked_up' => 'background:#f3f4f6;color:#374151', 'refunded' => 'background:#fee2e2;color:#991b1b'];
    $tabs = ['open' => 'Needs action'] + \App\Models\Sale::WEB_STATUSES;
@endphp
<x-app-layout>
    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Web Orders</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Paid online orders from rmflooring.ca for pickup at the warehouse. Confirm availability within one business day — the customer is emailed and texted at every step.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($tabs as $key => $label)
                    @php $n = $key === 'open' ? ($counts['new'] ?? 0) + ($counts['confirmed'] ?? 0) + ($counts['ready'] ?? 0) : ($counts[$key] ?? 0); @endphp
                    <a href="{{ route('pages.web-orders.index', ['status' => $key]) }}"
                       class="rounded-full px-4 py-1.5 text-sm font-medium border {{ $status === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600' }}">
                        {{ $label }} <span class="opacity-75">({{ $n }})</span>
                    </a>
                @endforeach
            </div>

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-left text-xs uppercase tracking-wider text-gray-500 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Order</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Items</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Placed</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($orders as $o)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer" onclick="location='{{ route('pages.web-orders.show', $o) }}'">
                                <td class="px-4 py-3 font-semibold text-indigo-600">{{ $o->web_order_number }}<span class="block text-xs font-normal text-gray-500">Sale #{{ $o->sale_number }}</span></td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $o->customer_name }}<span class="block text-xs text-gray-500">{{ $o->job_phone }}</span></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ count($o->web_order_data['items'] ?? []) }}</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">${{ number_format((float) $o->grand_total, 2) }}</td>
                                <td class="px-4 py-3"><span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold" style="{{ $badge[$o->web_status] ?? '' }}">{{ \App\Models\Sale::WEB_STATUSES[$o->web_status] ?? $o->web_status }}</span>
                                    @if ($o->web_status === 'new' && $o->created_at->lt(now()->subDay()))<span class="block mt-1 text-xs font-semibold" style="color:#dc2626">Over 1 business day</span>@endif
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $o->created_at->timezone('America/Vancouver')->format('M j, g:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No web orders here.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
