@php
    $isAdmin = auth()->check() && auth()->user()->hasRole('admin');
@endphp

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">

        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 rtl:space-x-reverse">
            <x-application-logo class="block h-8 w-auto fill-current text-gray-800" />
            <span class="self-center text-xl font-semibold whitespace-nowrap text-gray-900">
                {{ config('app.name', 'Floor Manager') }}
            </span>
        </a>

        <!-- Right side: user dropdown + mobile toggle -->
        <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">

            <!-- User dropdown trigger -->
            <button
                type="button"
                class="flex text-sm rounded-full focus:ring-4 focus:ring-gray-200"
                id="user-menu-button"
                aria-expanded="false"
                data-dropdown-toggle="user-dropdown"
                data-dropdown-placement="bottom"
            >
                <span class="sr-only">Open user menu</span>

                <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-semibold">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
            </button>

            <!-- User dropdown menu -->
            <div
                class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow"
                id="user-dropdown"
            >
                <div class="px-4 py-3">
                    <span class="block text-sm text-gray-900">{{ Auth::user()->name }}</span>
                    <span class="block text-sm text-gray-500 truncate">{{ Auth::user()->email }}</span>
                </div>

                <ul class="py-2" aria-labelledby="user-menu-button">
                    <li>
                        <a href="{{ route('profile.edit') }}"
                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Profile
                        </a>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full text-left block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
                class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200"
                aria-controls="navbar-main"
                aria-expanded="false"
            >
                <span class="sr-only">Open main menu</span>
                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M1 1h15M1 7h15M1 13h15"/>
                </svg>
            </button>
        </div>

        <!-- Main nav links -->
        <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-main">
            <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50
                       md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0 md:bg-white">

                <!-- Dashboard (everyone) -->
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="block py-2 px-3 rounded md:p-0
                              {{ request()->routeIs('dashboard') ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}">
                        Dashboard
                    </a>
                </li>

                <!-- Manage Customers (everyone) -->
                <li>
                    <button id="dropdownCustomersButton" data-dropdown-toggle="dropdownCustomers"
                            class="flex items-center justify-between w-full py-2 px-3 rounded md:p-0 md:w-auto
                                   {{ (request()->routeIs('admin.customers.*') || request()->routeIs('admin.project_managers.*') || request()->routeIs('admin.customer_types.*')) ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}"
                            type="button">
                        Manage Customers
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownCustomers" class="z-50 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-56">
                        <ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownCustomersButton">
                            <li>
                                <a href="{{ route('admin.customers.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Customers List
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.project_managers.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Project Managers
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.customer_types.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Customer Types
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Manage Vendors (everyone) -->
                <li>
                    <button id="dropdownVendorsButton" data-dropdown-toggle="dropdownVendors"
                            class="flex items-center justify-between w-full py-2 px-3 rounded md:p-0 md:w-auto
                                   {{ (request()->routeIs('admin.vendors.*') || request()->routeIs('admin.vendor_reps.*')) ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}"
                            type="button">
                        Manage Vendors
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownVendors" class="z-50 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-56">
                        <ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownVendorsButton">
                            <li>
                                <a href="{{ route('admin.vendors.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Vendors List
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.vendor_reps.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Vendor Reps
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Product Management (everyone) -->
                <li>
                    <button id="dropdownProductsButton" data-dropdown-toggle="dropdownProducts"
                            class="flex items-center justify-between w-full py-2 px-3 rounded md:p-0 md:w-auto
                                   {{ (request()->routeIs('admin.product_types.*') || request()->routeIs('admin.product_lines.*')) ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}"
                            type="button">
                        Product Management
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownProducts" class="z-50 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-56">
                        <ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownProductsButton">
                            <li>
                                <a href="{{ route('admin.product_types.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Product Types
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.product_lines.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Product Lines
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Manage Labour (everyone) -->
                <li>
                    <button id="dropdownLabourButton" data-dropdown-toggle="dropdownLabour"
                            class="flex items-center justify-between w-full py-2 px-3 rounded md:p-0 md:w-auto
                                   {{ (request()->routeIs('admin.labour_types.*') || request()->routeIs('admin.labour_items.*')) ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}"
                            type="button">
                        Manage Labour
                        <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                        </svg>
                    </button>

                    <div id="dropdownLabour" class="z-50 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-56">
                        <ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownLabourButton">
                            <li>
                                <a href="{{ route('admin.labour_types.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Labour Types
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.labour_items.index') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    Labour Items
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Admin-only extras -->
                @if ($isAdmin)

                    <li>
                        <a href="{{ route('admin.settings') }}"
                           class="block py-2 px-3 rounded md:p-0 {{ request()->routeIs('admin.settings') ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}">
                            Admin Settings
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.users.index') }}"
                           class="block py-2 px-3 rounded md:p-0 {{ request()->routeIs('admin.users.*') ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}">
                            Manage Users
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.roles.index') }}"
                           class="block py-2 px-3 rounded md:p-0 {{ request()->routeIs('admin.roles.*') ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}">
                            Manage Roles
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('admin.unit_measures.index') }}"
                           class="block py-2 px-3 rounded md:p-0 {{ request()->routeIs('admin.unit_measures.*') ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}">
                            Unit Measures
                        </a>
                    </li>

                    <li>
                        <button id="dropdownCoaButton" data-dropdown-toggle="dropdownCoa"
                                class="flex items-center justify-between w-full py-2 px-3 rounded md:p-0 md:w-auto
                                       {{ (request()->routeIs('admin.account_types.*') || request()->routeIs('admin.detail_types.*') || request()->routeIs('admin.gl_accounts.*')) ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}"
                                type="button">
                            Chart of Accounts
                            <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>

                        <div id="dropdownCoa" class="z-50 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-56">
                            <ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownCoaButton">
                                <li><a href="{{ route('admin.account_types.index') }}" class="block px-4 py-2 hover:bg-gray-100">Account Types</a></li>
                                <li><a href="{{ route('admin.detail_types.index') }}" class="block px-4 py-2 hover:bg-gray-100">Detail Types</a></li>
                                <li><a href="{{ route('admin.gl_accounts.index') }}" class="block px-4 py-2 hover:bg-gray-100">GL Accounts</a></li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <button id="dropdownTaxButton" data-dropdown-toggle="dropdownTax"
                                class="flex items-center justify-between w-full py-2 px-3 rounded md:p-0 md:w-auto
                                       {{ (request()->routeIs('admin.tax_agencies.*') || request()->routeIs('admin.tax_rates.*')) ? 'text-blue-700' : 'text-gray-900 hover:text-blue-700' }}"
                                type="button">
                            Tax Management
                            <svg class="w-2.5 h-2.5 ms-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                            </svg>
                        </button>

                        <div id="dropdownTax" class="z-50 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-56">
                            <ul class="py-2 text-sm text-gray-700" aria-labelledby="dropdownTaxButton">
                                <li><a href="{{ route('admin.tax_agencies.index') }}" class="block px-4 py-2 hover:bg-gray-100">Tax Agencies</a></li>
                                <li><a href="{{ route('admin.tax_rates.index') }}" class="block px-4 py-2 hover:bg-gray-100">Tax Rates</a></li>
                            </ul>
                        </div>
                    </li>

                @endif
            </ul>
        </div>
    </div>
</nav>
