<x-app-layout>
    <div class="py-12">
        <div class="w-full mx-auto px-4 sm:px-6 lg:px-8" style="max-width: 1200px;">

            <h1 class="text-3xl font-bold text-gray-900 mb-2">Reports</h1>
            <p class="text-gray-500 mb-8">Financial and operational reports for RM Flooring.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                {{-- Sales Report --}}
                <a href="{{ route('admin.reports.sales') }}"
                   class="block bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-blue-300 transition-all p-6 group">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center group-hover:bg-blue-100 transition-colors">
                            <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">Sales Report</h2>
                            <p class="text-sm text-gray-500">All sales with filters for status, date range, invoiced status, and salesperson. Identify uninvoiced jobs and track the value of your pipeline.</p>
                            <span class="inline-block mt-3 text-xs font-medium text-blue-600">View Report →</span>
                        </div>
                    </div>
                </a>

                {{-- AR / Invoices Report --}}
                <a href="{{ route('admin.reports.invoices') }}"
                   class="block bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-orange-300 transition-all p-6 group">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-50 rounded-lg flex items-center justify-center group-hover:bg-orange-100 transition-colors">
                            <svg class="w-6 h-6 text-orange-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">Accounts Receivable</h2>
                            <p class="text-sm text-gray-500">All invoices with an aging summary (current, 1–30, 31–60, 61–90, 90+ days). Filter by status, date, or view overdue-only.</p>
                            <span class="inline-block mt-3 text-xs font-medium text-orange-600">View Report →</span>
                        </div>
                    </div>
                </a>

                {{-- Revenue Summary --}}
                <a href="{{ route('admin.reports.revenue') }}"
                   class="block bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-green-300 transition-all p-6 group">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center group-hover:bg-green-100 transition-colors">
                            <svg class="w-6 h-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">Revenue Summary</h2>
                            <p class="text-sm text-gray-500">Monthly breakdown of invoiced amounts, total payments received, and outstanding balances. Switch between years.</p>
                            <span class="inline-block mt-3 text-xs font-medium text-green-600">View Report →</span>
                        </div>
                    </div>
                </a>

                {{-- Purchase Orders --}}
                <a href="{{ route('admin.reports.purchaseOrders') }}"
                   class="block bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-purple-300 transition-all p-6 group">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center group-hover:bg-purple-100 transition-colors">
                            <svg class="w-6 h-6 text-purple-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">Purchase Orders</h2>
                            <p class="text-sm text-gray-500">All POs with filters for status, vendor, and date range. Quickly identify open, overdue, or pending orders across all vendors.</p>
                            <span class="inline-block mt-3 text-xs font-medium text-purple-600">View Report →</span>
                        </div>
                    </div>
                </a>

                {{-- Aging Estimates --}}
                <a href="{{ route('admin.reports.agingEstimates') }}"
                   class="block bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md hover:border-yellow-300 transition-all p-6 group">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-yellow-50 rounded-lg flex items-center justify-center group-hover:bg-yellow-100 transition-colors">
                            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">Aging Estimates</h2>
                            <p class="text-sm text-gray-500">Track sent estimates that haven't converted. See which are overdue for a follow-up, then send a pre-drafted email or SMS directly from the report.</p>
                            <span class="inline-block mt-3 text-xs font-medium text-yellow-600">View Report →</span>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
