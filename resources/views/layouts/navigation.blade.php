<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>
                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @if (auth()->check() && auth()->user()->hasRole('Admin'))
                        <x-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">
                            {{ __('Admin Settings') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            {{ __('Manage Users') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')">
                            {{ __('Manage Roles') }}
                        </x-nav-link>

                        <!-- Customers Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition
                                    {{ (request()->routeIs('admin.customers.*') || request()->routeIs('admin.project_managers.*') || request()->routeIs('admin.customer_types.*')) ? 'text-gray-900 bg-gray-100' : '' }}">
                                {{ __('Manage Customers') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-left left-0">
                                <div class="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                                    <div class="py-1">
                                        <a href="{{ route('admin.customers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.customers.*') ? 'bg-gray-100' : '' }}">
                                            Customers List
                                        </a>
                                        <a href="{{ route('admin.project_managers.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.project_managers.*') ? 'bg-gray-100' : '' }}">
                                            Project Managers
                                        </a>
                                        <a href="{{ route('admin.customer_types.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.customer_types.*') ? 'bg-gray-100' : '' }}">
                                            Customer Types
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vendors Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition
                                    {{ (request()->routeIs('admin.vendors.*') || request()->routeIs('admin.vendor_reps.*')) ? 'text-gray-900 bg-gray-100' : '' }}">
                                {{ __('Manage Vendors') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-left left-0">
                                <div class="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                                    <div class="py-1">
                                        <a href="{{ route('admin.vendors.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.vendors.*') ? 'bg-gray-100' : '' }}">
                                            Vendors List
                                        </a>
                                        <a href="{{ route('admin.vendor_reps.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.vendor_reps.*') ? 'bg-gray-100' : '' }}">
                                            Vendor Reps
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-nav-link :href="route('admin.unit_measures.index')" :active="request()->routeIs('admin.unit_measures.*')">
                            {{ __('Manage Unit Measures') }}
                        </x-nav-link>
			
			<!-- New: Product Management Dropdown -->
<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition
            {{ request()->routeIs('admin.product_types.*') ? 'text-gray-900 bg-gray-100' : '' }}">
        {{ __('Product Management') }}
        <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
    <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-left left-0">
        <div class="rounded-md bg-white ring-1 ring-black ring-opacity-5">
            <div class="py-1">
                <!-- Sub-item: Product Types -->
                <a href="{{ route('admin.product_types.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.product_types.*') ? 'bg-gray-100' : '' }}">
                    Product Types
                </a>

                <!-- NEW: Product Lines sub-item -->
                <a href="{{ route('admin.product_lines.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.product-lines.*') ? 'bg-gray-100' : '' }}">
                    Product Lines
                </a>
            </div>
        </div>
    </div>
</div>
			
                        <!-- Labour Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition
                                    {{ request()->routeIs('admin.labour_types.*') ? 'text-gray-900 bg-gray-100' : '' }}">
                                {{ __('Manage Labour') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-left left-0">
                                <div class="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                                    <div class="py-1">
                                        <a href="{{ route('admin.labour_types.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.labour_types.*') ? 'bg-gray-100' : '' }}">
                                            Labour Types
                                        </a>
					<!-- NEW: Labour Items sub-item -->
    					<a href="{{ route('admin.labour_items.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.labour_items.*') ? 					'bg-gray-100' : '' }}">
					        Labour Items
					    </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart of Accounts Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition
                                    {{ (request()->routeIs('admin.account_types.*') || request()->routeIs('admin.detail_types.*') || request()->routeIs('admin.gl_accounts.*')) ? 'text-gray-900 bg-gray-100' : '' }}">
                                {{ __('Manage Chart of Accounts') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-left left-0">
                                <div class="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                                    <div class="py-1">
                                        <a href="{{ route('admin.account_types.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.account_types.*') ? 'bg-gray-100' : '' }}">
                                            Account Types
                                        </a>
                                        <a href="{{ route('admin.detail_types.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.detail_types.*') ? 'bg-gray-100' : '' }}">
                                            Detail Types
                                        </a>
                                        <a href="{{ route('admin.gl_accounts.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.gl_accounts.*') ? 'bg-gray-100' : '' }}">
                                            GL Accounts
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax Management Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button
                                @click="open = !open"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition
                                    {{ request()->routeIs('admin.tax_agencies.*') ? 'text-gray-900 bg-gray-100' : '' }}">
                                {{ __('Tax Management') }}
                                <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-56 rounded-md shadow-lg origin-top-left left-0">
                                <div class="rounded-md bg-white ring-1 ring-black ring-opacity-5">
                                    <div class="py-1">
                                        <a href="{{ route('admin.tax_agencies.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.tax_agencies.*') ? 'bg-gray-100' : '' }}">
                                            Tax Agencies
                                        </a>
					<a href="{{ route('admin.tax_rates.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ request()->routeIs('admin.tax_rates.*') ? 'bg-						gray-100' : '' }}">
                                            Tax Rates
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @if (auth()->check() && auth()->user()->hasRole('Admin'))
                <x-responsive-nav-link :href="route('admin.settings')" :active="request()->routeIs('admin.settings')">
                    {{ __('Admin Settings') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    {{ __('Manage Users') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('admin.roles.index')" :active="request()->routeIs('admin.roles.*')">
                    {{ __('Manage Roles') }}
                </x-responsive-nav-link>

                <!-- Mobile Customers Dropdown -->
                <li x-data="{ open: false }">
                    <button @click="open = !open" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        {{ __('Manage Customers') }}
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="pl-6 pt-1 pb-2">
                        <li>
                            <x-responsive-nav-link :href="route('admin.customers.index')" :active="request()->routeIs('admin.customers.*')">
                                Customers List
                            </x-responsive-nav-link>
                        </li>
                        <li>
                            <x-responsive-nav-link :href="route('admin.project_managers.index')" :active="request()->routeIs('admin.project_managers.*')">
                                Project Managers
                            </x-responsive-nav-link>
                        </li>
                        <li>
                            <x-responsive-nav-link :href="route('admin.customer_types.index')" :active="request()->routeIs('admin.customer_types.*')">
                                Customer Types
                            </x-responsive-nav-link>
                        </li>
                    </ul>
                </li>

                <!-- Mobile Vendors Dropdown -->
                <li x-data="{ open: false }">
                    <button @click="open = !open" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        {{ __('Manage Vendors') }}
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="pl-6 pt-1 pb-2">
                        <li>
                            <x-responsive-nav-link :href="route('admin.vendors.index')" :active="request()->routeIs('admin.vendors.*')">
                                Vendors List
                            </x-responsive-nav-link>
                        </li>
                        <li>
                            <x-responsive-nav-link :href="route('admin.vendor_reps.index')" :active="request()->routeIs('admin.vendor_reps.*')">
                                Vendor Reps
                            </x-responsive-nav-link>
                        </li>
                    </ul>
                </li>

                <x-responsive-nav-link :href="route('admin.unit_measures.index')" :active="request()->routeIs('admin.unit_measures.*')">
                    {{ __('Manage Unit Measures') }}
                </x-responsive-nav-link>

                <!-- Mobile Labour Dropdown -->
                <li x-data="{ open: false }">
                    <button @click="open = !open" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        {{ __('Manage Labour') }}
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="pl-6 pt-1 pb-2">
                        <li>
                            <x-responsive-nav-link :href="route('admin.labour_types.index')" :active="request()->routeIs('admin.labour_types.*')">
                                Labour Types
                            </x-responsive-nav-link>
                        </li>
			<!-- Mobile Product Management Dropdown -->
<li x-data="{ open: false }">
    <button @click="open = !open" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
        {{ __('Product Management') }}
        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>
    <ul x-show="open" x-cloak class="pl-6 pt-1 pb-2">
        <li>
            <x-responsive-nav-link :href="route('admin.product_types.index')" :active="request()->routeIs('admin.product_types.*')">
                Product Types
            </x-responsive-nav-link>
        </li>

        <!-- NEW: Product Lines sub-item for mobile -->
        <li>
            <x-responsive-nav-link :href="route('admin.product_lines.index')" :active="request()->routeIs('admin.product-lines.*')">
                Product Lines
            </x-responsive-nav-link>
        </li>
    </ul>
</li>

			<!-- NEW: Labour Items sub-item -->
    			<li>
			        <x-responsive-nav-link :href="route('admin.labour_items.index')" :active="request()->routeIs('admin.labour_items.*')">
			            Labour Items
			        </x-responsive-nav-link>
			    </li>
                    </ul>
                </li>

                <!-- Mobile Chart of Accounts Dropdown -->
                <li x-data="{ open: false }">
                    <button @click="open = !open" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        {{ __('Manage Chart of Accounts') }}
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="pl-6 pt-1 pb-2">
                        <li>
                            <x-responsive-nav-link :href="route('admin.account_types.index')" :active="request()->routeIs('admin.account_types.*')">
                                Account Types
                            </x-responsive-nav-link>
                        </li>
                        <li>
                            <x-responsive-nav-link :href="route('admin.detail_types.index')" :active="request()->routeIs('admin.detail_types.*')">
                                Detail Types
                            </x-responsive-nav-link>
                        </li>
                        <li>
                            <x-responsive-nav-link :href="route('admin.gl_accounts.index')" :active="request()->routeIs('admin.gl_accounts.*')">
                                GL Accounts
                            </x-responsive-nav-link>
                        </li>
                    </ul>
                </li>

                <!-- Mobile Tax Management Dropdown -->
                <li x-data="{ open: false }">
                    <button @click="open = !open" class="w-full text-left flex items-center justify-between px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        {{ __('Tax Management') }}
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="pl-6 pt-1 pb-2">
                        <li>
                            <x-responsive-nav-link :href="route('admin.tax_agencies.index')" :active="request()->routeIs('admin.tax_agencies.*')">
                                Tax Agencies
                            </x-responsive-nav-link>
                        </li>
                    </ul>
                </li>
		<!-- NEW: Tax Rates sub-item -->
        <li>
            <x-responsive-nav-link :href="route('admin.tax_rates.index')" :active="request()->routeIs('admin.tax_rates.*')">
                Tax Rates
            </x-responsive-nav-link>
        </li>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-3 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>