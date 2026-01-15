@php
    $isAdmin = auth()->check() && auth()->user()->hasRole('admin');
@endphp

<nav class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
    <div class="mx-auto flex max-w-screen-xl flex-wrap items-center justify-between p-4">
        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
            <x-application-logo class="block h-8 w-auto fill-current text-gray-800 dark:text-white" />
            <span class="self-center whitespace-nowrap text-xl font-semibold text-gray-900 dark:text-white">
                {{ config('app.name', 'Floor Manager') }}
            </span>
        </a>

        <!-- Right side: user dropdown + mobile toggle -->
        <div class="flex items-center gap-3 md:order-2">
            <!-- User dropdown trigger -->
            <button
                type="button"
                class="flex rounded-full text-sm focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700"
                id="user-menu-button"
                aria-expanded="false"
                data-dropdown-toggle="user-dropdown"
                data-dropdown-placement="bottom"
            >
                <span class="sr-only">Open user menu</span>
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
            </button>

            <!-- User dropdown menu -->
            <div
                class="z-50 hidden my-4 list-none divide-y divide-gray-100 rounded-lg bg-white text-base shadow dark:divide-gray-600 dark:bg-gray-700"
                id="user-dropdown"
            >
                <div class="px-4 py-3">
                    <span class="block text-sm text-gray-900 dark:text-white">{{ Auth::user()->name }}</span>
                    <span class="block truncate text-sm text-gray-500 dark:text-gray-300">{{ Auth::user()->email }}</span>
                </div>
                <ul class="py-2" aria-labelledby="user-menu-button">
                    <li>
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">
                            Profile
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">
                                Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>

            <!-- Mobile menu toggle -->
            <button
                data-collapse-toggle="navbar-main"
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 md:hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600"
                aria-controls="navbar-main"
                aria-expanded="false"
            >
                <span class="sr-only">Open main menu</span>
                <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M1 1h15M1 7h15M1 13h15"/>
                </svg>
            </button>
        </div>

        <!-- Main nav links -->
        <div class="hidden w-full items-center justify-between md:order-1 md:flex md:w-auto" id="navbar-main">
            <ul class="mt-4 flex flex-col rounded-lg border border-gray-100 bg-gray-50 p-4 font-medium
                       md:mt-0 md:flex-row md:space-x-8 md:border-0 md:bg-white md:p-0 rtl:space-x-reverse
                       dark:border-gray-700 dark:bg-gray-800 md:dark:bg-gray-900">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="block rounded px-3 py-2 md:p-0
                              {{ request()->routeIs('dashboard') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                        Dashboard
                    </a>
                </li>

                <!-- Manage Customers -->
                <li class="relative">
                    <button id="dropdownCustomersButton" data-dropdown-toggle="dropdownCustomers"
                            class="flex w-full items-center justify-between rounded px-3 py-2 md:w-auto md:p-0
                                   {{ (request()->routeIs('admin.customers.*') || request()->routeIs('admin.project_managers.*') || request()->routeIs('admin.customer_types.*')) ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}"
                            type="button">
                        Manage Customers
                        <svg class="ms-2.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownCustomers" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownCustomersButton">
                            <li><a href="{{ route('admin.customers.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Customers List</a></li>
                            <li><a href="{{ route('admin.project_managers.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Project Managers</a></li>
                            <li><a href="{{ route('admin.customer_types.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Customer Types</a></li>
                        </ul>
                    </div>
                </li>

                <!-- Manage Opportunities -->
                <li>
                    <a href="{{ route('pages.opportunities.index') }}"
                       class="block rounded px-3 py-2 md:p-0
                              {{ request()->routeIs('pages.opportunities.*') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                        Manage Opportunities
                    </a>
                </li>

                <!-- Manage Vendors -->
                <li class="relative">
                    <button id="dropdownVendorsButton" data-dropdown-toggle="dropdownVendors"
                            class="flex w-full items-center justify-between rounded px-3 py-2 md:w-auto md:p-0
                                   {{ (request()->routeIs('admin.vendors.*') || request()->routeIs('admin.vendor_reps.*')) ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}"
                            type="button">
                        Manage Vendors
                        <svg class="ms-2.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownVendors" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownVendorsButton">
                            <li><a href="{{ route('admin.vendors.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Vendors List</a></li>
                            <li><a href="{{ route('admin.vendor_reps.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Vendor Reps</a></li>
                        </ul>
                    </div>
                </li>

                <!-- Product Management -->
                <li class="relative">
                    <button id="dropdownProductsButton" data-dropdown-toggle="dropdownProducts"
                            class="flex w-full items-center justify-between rounded px-3 py-2 md:w-auto md:p-0
                                   {{ (request()->routeIs('admin.product_types.*') || request()->routeIs('admin.product_lines.*')) ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}"
                            type="button">
                        Product Management
                        <svg class="ms-2.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownProducts" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownProductsButton">
                            <li><a href="{{ route('admin.product_types.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Product Types</a></li>
                            <li><a href="{{ route('admin.product_lines.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Product Lines</a></li>
                        </ul>
                    </div>
                </li>

                <!-- Manage Labour -->
                <li class="relative">
                    <button id="dropdownLabourButton" data-dropdown-toggle="dropdownLabour"
                            class="flex w-full items-center justify-between rounded px-3 py-2 md:w-auto md:p-0
                                   {{ (request()->routeIs('admin.labour_types.*') || request()->routeIs('admin.labour_items.*')) ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}"
                            type="button">
                        Manage Labour
                        <svg class="ms-2.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownLabour" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownLabourButton">
                            <li><a href="{{ route('admin.labour_types.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Labour Types</a></li>
                            <li><a href="{{ route('admin.labour_items.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Labour Items</a></li>
                        </ul>
                    </div>
                </li>

                <!-- Manage Estimates -->
                <li class="relative">
                    <button id="dropdownEstimatesButton" data-dropdown-toggle="dropdownEstimates"
                            class="flex w-full items-center justify-between rounded px-3 py-2 md:w-auto md:p-0
                                   {{ (request()->routeIs('admin.estimates.*') || request()->is('admin/estimates/mock-create')) ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}"
                            type="button">
                        Manage Estimates
                        <svg class="ms-2.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>
                    <div id="dropdownEstimates" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownEstimatesButton">
                            <li><a href="{{ route('admin.estimates.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">View Estimates</a></li>
                            <li><a href="{{ url('/admin/estimates/mock-create') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Create Estimate</a></li>
                        </ul>
                    </div>
                </li>

                <!-- Admin-only items -->
                @if ($isAdmin)
                    <li>
                        <a href="{{ route('admin.settings') }}"
                           class="block rounded px-3 py-2 md:p-0
                                  {{ request()->routeIs('admin.settings') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                            Admin Settings
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.users.index') }}"
                           class="block rounded px-3 py-2 md:p-0
                                  {{ request()->routeIs('admin.users.*') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                            Manage Users
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.employees.index') }}"
                           class="block rounded px-3 py-2 md:p-0
                                  {{ request()->routeIs('admin.employees.*') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                            Manage Employees
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.roles.index') }}"
                           class="block rounded px-3 py-2 md:p-0
                                  {{ request()->routeIs('admin.roles.*') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                            Manage Roles
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.unit_measures.index') }}"
                           class="block rounded px-3 py-2 md:p-0
                                  {{ request()->routeIs('admin.unit_measures.*') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
                            Unit Measures
                        </a>
                    </li>

                    <!-- Chart of Accounts -->
                    <li class="relative">
                        <button id="dropdownCoaButton" data-dropdown-toggle="dropdownCoa"
                                class="flex w-full items-center justify-between rounded px-3 py-2 md:w-auto md:p-0
                                       {{ (request()->routeIs('admin.account_types.*') || request()->routeIs('admin.detail_types.*') || request()->routeIs('admin.gl_accounts.*')) ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}"
                                type="button">
                            Chart of Accounts
                            <svg class="ms-2.5 h-2.5 w-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>
                        <div id="dropdownCoa" class="z-50 hidden w-56 divide-y divide-gray-100 rounded-lg bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200" aria-labelledby="dropdownCoaButton">
                                <li><a href="{{ route('admin.account_types.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Account Types</a></li>
                                <li><a href="{{ route('admin.detail_types.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Detail Types</a></li>
                                <li><a href="{{ route('admin.gl_accounts.index') }}" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">GL Accounts</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
    <a href="{{ route('admin.tax.index') }}"
       class="block rounded px-3 py-2 md:p-0
              {{ request()->routeIs('admin.tax.*') ? 'text-blue-700 dark:text-blue-400' : 'text-gray-900 hover:text-blue-700 dark:text-white dark:hover:text-blue-400' }}">
        Tax Management
    </a>
</li>

                @endif
            </ul>
        </div>
    </div>
</nav>