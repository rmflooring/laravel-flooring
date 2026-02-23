<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Welcome Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-2xl font-bold mb-2">Welcome back, {{ auth()->user()->name }}!</h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        You're logged in. Here's a quick overview of your activity.
                    </p>
                </div>
            </div>

            <!-- Stats Grid (Flowbite Cards) -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Card Grid --}}
    <div class="space-y-6">

        {{-- Row 1: 4-up --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $cardsRow1 = [
                    [
                        'title' => 'Customers',
                        'subtitle' => 'Manage customer records',
                        'href' => route('admin.customers.index'),
						'accent' => 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 border-l-blue-600',
                        'icon' => 'M7 20a4 4 0 0 1-4-4V8a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4H7Z',
                    ],
                    [
                        'title' => 'Opportunities',
                        'subtitle' => 'Track jobs & leads',
                        'href' => route('pages.opportunities.index'),
                        'accent' => 'bg-emerald-600 text-white border border-emerald-700 dark:bg-emerald-700 dark:border-emerald-800',
                        'icon' => 'M12 6v6l4 2',
                    ],
                    [
                        'title' => 'Estimates',
                        'subtitle' => 'Create & manage estimates',
                        'href' => route('admin.estimates.index'),
                        'accent' => 'bg-blue-600 text-white border border-blue-700 dark:bg-blue-700 dark:border-blue-800',
                        'icon' => 'M7 7h10M7 11h10M7 15h6',
                    ],
                    [
                        'title' => 'Sales',
                        'subtitle' => 'View & manage sales',
                        'href' => route('pages.sales.index'),
						'accent' => 'bg-indigo-600 text-white border border-indigo-700 dark:bg-indigo-700 dark:border-indigo-800',
                        'icon' => 'M8 7h8M8 11h8M8 15h4',
                    ],
                ];
            @endphp

            @foreach ($cardsRow1 as $c)
                @include('components.dashboard-card', ['c' => $c])
            @endforeach
        </div>

        {{-- Row 2: 4-up --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $cardsRow2 = [
                    [
                        'title' => 'Labour Catalog',
                        'subtitle' => 'Labour types & pricing',
                        'href' => route('admin.labour_items.index'),
                        'accent' => 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 border-l-amber-600',
                        'icon' => 'M9 12h6M12 9v6',
                    ],
                    [
                        'title' => 'Product Catalog',
                        'subtitle' => 'Lines, styles, pricing',
                        'href' => route('admin.product_lines.index'),
                        'accent' => 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 border-l-indigo-600',
                        'icon' => 'M6 7h12M6 12h12M6 17h12',
                    ],
                    [
                        'title' => 'Inventory',
                        'subtitle' => 'Coming soon',
                        'href' => null,
                        'accent' => 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 border-l-gray-300 dark:border-l-gray-700',
                        'icon' => 'M4 7h16M6 11h12M6 15h12',
                    ],
                    [
                        'title' => 'Vendors',
                        'subtitle' => 'Vendors & reps',
                        'href' => route('admin.vendors.index'),
                        'accent' => 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 border-l-slate-600',
                        'icon' => 'M6 10h12M6 14h12M8 18h8',
                    ],
                ];
            @endphp

            @foreach ($cardsRow2 as $c)
                @include('components.dashboard-card', ['c' => $c])
            @endforeach
        </div>

        {{-- Row 3: 2-up --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @php
                $cardsRow3 = [
                    [
                        'title' => 'Work Orders',
                        'subtitle' => 'Coming soon',
                        'href' => null,
                        'accent' => 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800',
                        'icon' => 'M7 8h10M7 12h10M7 16h6',
                    ],
                    [
                        'title' => 'Purchase Orders',
                        'subtitle' => 'Coming soon',
                        'href' => null,
                        'accent' => 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800',
                        'icon' => 'M7 7h10M7 11h10M7 15h10',
                    ],
                ];
            @endphp

            @foreach ($cardsRow3 as $c)
                @include('components.dashboard-card', ['c' => $c])
            @endforeach
        </div>

        {{-- Row 4: 1-up --}}
        <div class="grid grid-cols-1 gap-4">
            @php
                $cardsRow4 = [
                    [
                        'title' => 'Calendar',
                        'subtitle' => 'Schedules & events',
                        'href' => route('pages.calendar.index'),
                        'accent' => 'bg-fuchsia-600 text-white border border-fuchsia-700 dark:bg-fuchsia-700 dark:border-fuchsia-800',
                        'icon' => 'M7 3v2m10-2v2M5 8h14M7 12h4m-4 4h6',
                    ],
                ];
            @endphp

            @foreach ($cardsRow4 as $c)
                @include('components.dashboard-card', ['c' => $c])
            @endforeach
        </div>

    </div>
</div>

            <!-- Quick Actions (Flowbite Buttons + Dropdown) -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">Quick Actions</h3>

                <div class="flex flex-wrap gap-4">
                    <!-- Primary Button -->
                    <button type="button"
                            class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                        Create New Estimate
                    </button>

                    <!-- Secondary Button -->
                    <button type="button"
                            class="text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
                        View All Jobs
                    </button>

                    <!-- Dropdown Button -->
                    <button data-dropdown-toggle="quick-actions-dropdown"
                            class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
                        More Actions
                        <svg class="w-2.5 h-2.5 ms-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div id="quick-actions-dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-44 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                            <li>
                                <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Schedule Installation</a>
                            </li>
                            <li>
                                <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Create Purchase Order</a>
                            </li>
                            <li>
                                <a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">View Reports</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>