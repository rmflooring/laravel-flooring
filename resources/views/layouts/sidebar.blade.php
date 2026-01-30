@php
    use App\Support\Roles;

    $user = auth()->user();

    // Role-based (still fine to keep)
    $isAdminRole = auth()->check() && $user->hasRole(Roles::ADMIN);

    // Permission-based (more resilient)
    $canManageUsers  = auth()->check() && $user->can('manage users');
    $canManageRoles  = auth()->check() && $user->can('manage roles');
    $canEditSettings = auth()->check() && $user->can('edit settings');

    // Treat as "admin nav" if they have core admin permissions OR admin role
    $showAdminNav = $isAdminRole || $canManageUsers || $canManageRoles || $canEditSettings;
@endphp

<div class="flex">
    <aside id="app-sidebar"
           class="fixed left-0 top-0 z-40 h-screen -translate-x-full border-r border-gray-200 bg-white transition-transform transition-all duration-200 sm:translate-x-0 dark:border-gray-700 dark:bg-gray-900"
           aria-label="Sidebar">
        <div class="h-full overflow-y-auto px-3 py-4">

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="mb-6 flex items-center space-x-3 rtl:space-x-reverse">
                <x-application-logo class="block h-8 w-auto fill-current text-gray-800 dark:text-white" />
                <span class="sidebar-label self-center whitespace-nowrap text-lg font-semibold text-gray-900 dark:text-white">
                    {{ config('app.name', 'Floor Manager') }}
                </span>
            </a>

            <ul class="space-y-1 font-medium">

                {{-- MAIN --}}
                <li class="px-2 pt-2 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 sidebar-label">
                    Main
                </li>

                {{-- Dashboard --}}
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 22 21">
                            <path d="M16.975 11H10V4.025a1 1 0 0 1 1.066-.998 8.5 8.5 0 1 1 5.91 7.973Z"/>
                            <path d="M9 18.975V11H1.025A1 1 0 0 1 .027 9.934 8.5 8.5 0 0 1 9 1.026a1 1 0 0 1 1 .999V9H18.975a1 1 0 0 1 .999 1A8.5 8.5 0 0 1 10 19.973a1 1 0 0 1-1-.998Z"/>
                        </svg>
                        <span class="sidebar-label">Dashboard</span>
                    </a>
                </li>

                {{-- Calendar --}}
                <li>
                    <a href="{{ route('pages.calendar.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V7a2 2 0 0 0-2-2h-1V4a1 1 0 1 0-2 0v1H5V4Z"/>
                            <path d="M20 11H0v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-7Z"/>
                        </svg>
                        <span class="sidebar-label">Calendar</span>
                    </a>
                </li>

                {{-- Opportunities --}}
                <li>
                    <a href="{{ route('pages.opportunities.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                            <path d="M1 18h16a1 1 0 0 0 1-1v-6H0v6a1 1 0 0 0 1 1Z"/>
                            <path d="M0 9h18V7a2 2 0 0 0-2-2h-3V4a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2v1H2a2 2 0 0 0-2 2v2Z"/>
                        </svg>
                        <span class="sidebar-label">Opportunities</span>
                    </a>
                </li>

                {{-- Estimates --}}
                <li>
                    <a href="{{ route('pages.estimates.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 20">
                            <path d="M5 1a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V6.414A2 2 0 0 0 14.414 5L11 1.586A2 2 0 0 0 9.586 1H5Z"/>
                        </svg>
                        <span class="sidebar-label">Estimates</span>
                    </a>
                </li>

                {{-- Customers --}}
                <li>
                    <a href="{{ route('admin.customers.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 19">
                            <path d="M10 0a5 5 0 1 1 0 10A5 5 0 0 1 10 0Z"/>
                            <path d="M0 19a10 10 0 0 1 20 0H0Z"/>
                        </svg>
                        <span class="sidebar-label">Customers</span>
                    </a>
                </li>

                {{-- Vendors --}}
                <li>
                    <a href="{{ route('admin.vendors.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 2a2 2 0 0 0-2 2v14h12V4a2 2 0 0 0-2-2H6Z"/>
                            <path d="M2 6H1a1 1 0 0 0-1 1v11a2 2 0 0 0 2 2h2V6H2Z"/>
                            <path d="M18 6h1a1 1 0 0 1 1 1v11a2 2 0 0 1-2 2h-2V6h2Z"/>
                        </svg>
                        <span class="sidebar-label">Vendors</span>
                    </a>
                </li>

                {{-- Products --}}
                <li>
                    <a href="{{ route('admin.product_types.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 0 0 5l10 5 10-5L10 0Z"/>
                            <path d="M0 7l10 5 10-5v8l-10 5L0 15V7Z"/>
                        </svg>
                        <span class="sidebar-label">Products</span>
                    </a>
                </li>

                {{-- Labour --}}
                <li>
                    <a href="{{ route('admin.labour_types.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 2a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h6v-6a2 2 0 0 1 2-2h6V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2H6Z"/>
                            <path d="M20 14h-6a1 1 0 0 0-1 1v5h6a2 2 0 0 0 2-2v-3a1 1 0 0 0-1-1Z"/>
                        </svg>
                        <span class="sidebar-label">Labour</span>
                    </a>
                </li>

                {{-- ADMIN --}}
                @if ($showAdminNav)
                    <li class="mt-4 px-2 pt-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 sidebar-label border-t border-gray-200 dark:border-gray-700">
                        Admin
                    </li>

                    {{-- Admin Settings --}}
                    @if ($canEditSettings)
                        <li>
                            <a href="{{ route('admin.settings') }}"
                               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M11.983 1.5a1 1 0 0 0-1.966 0l-.216 1.46a6.99 6.99 0 0 0-1.43.59L7.08 2.8a1 1 0 0 0-1.39 1.39l.75 1.29a6.99 6.99 0 0 0-.59 1.43L4.39 7.12a1 1 0 0 0 0 1.966l1.46.216c.12.5.32.98.59 1.43l-.75 1.29a1 1 0 0 0 1.39 1.39l1.29-.75c.45.27.93.47 1.43.59l.216 1.46a1 1 0 0 0 1.966 0l.216-1.46c.5-.12.98-.32 1.43-.59l1.29.75a1 1 0 0 0 1.39-1.39l-.75-1.29c.27-.45.47-.93.59-1.43l1.46-.216a1 1 0 0 0 0-1.966l-1.46-.216a6.99 6.99 0 0 0-.59-1.43l.75-1.29a1 1 0 0 0-1.39-1.39l-1.29.75a6.99 6.99 0 0 0-1.43-.59L11.983 1.5Z"/>
                                    <path d="M10 13a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/>
                                </svg>
                                <span class="sidebar-label">Admin Settings</span>
                            </a>
                        </li>
                    @endif

                    {{-- Manage Users --}}
                    @if ($canManageUsers)
                        <li>
                            <a href="{{ route('admin.users.index') }}"
                               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 0a5 5 0 1 1 0 10A5 5 0 0 1 10 0Z"/>
                                    <path d="M2 19a8 8 0 0 1 16 0H2Z"/>
                                </svg>
                                <span class="sidebar-label">Manage Users</span>
                            </a>
                        </li>
                    @endif

                    {{-- Manage Employees --}}
                    <li>
                        <a href="{{ route('admin.employees.index') }}"
                           class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 0a5 5 0 1 1 0 10A5 5 0 0 1 10 0Z"/>
                                <path d="M0 19a10 10 0 0 1 20 0H0Z"/>
                            </svg>
                            <span class="sidebar-label">Manage Employees</span>
                        </a>
                    </li>

                    {{-- Manage Roles --}}
                    @if ($canManageRoles)
                        <li>
                            <a href="{{ route('admin.roles.index') }}"
                               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V3Z"/>
                                </svg>
                                <span class="sidebar-label">Manage Roles</span>
                            </a>
                        </li>
                    @endif

                    {{-- Unit Measures --}}
                    <li>
                        <a href="{{ route('admin.unit_measures.index') }}"
                           class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 3a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V3Z"/>
                            </svg>
                            <span class="sidebar-label">Unit Measures</span>
                        </a>
                    </li>

                    {{-- Calendar Settings --}}
                    @if (Route::has('admin.calendar.settings'))
                        <li>
                            <a href="{{ route('admin.calendar.settings') }}"
                               class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M5 4a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V7a2 2 0 0 0-2-2h-1V4a1 1 0 1 0-2 0v1H5V4Z"/>
                                    <path d="M20 11H0v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-7Z"/>
                                </svg>
                                <span class="sidebar-label">Calendar Settings</span>
                            </a>
                        </li>
                    @endif

                    {{-- Chart of Accounts dropdown --}}
                    <li class="relative">
                        <button id="dropdownCoaButton" data-dropdown-toggle="dropdownCoa"
                                class="sidebar-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                                type="button">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 0 0 5l10 5 10-5L10 0Z"/>
                                    <path d="M0 7l10 5 10-5v8l-10 5L0 15V7Z"/>
                                </svg>
                                <span class="sidebar-label">Chart of Accounts</span>
                            </div>
                            <svg class="h-4 w-4 text-gray-500 dark:text-gray-400 sidebar-label" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
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

                    {{-- Tax Management --}}
                    <li>
                        <a href="{{ route('admin.tax.index') }}"
                           class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3Z"/>
                                <path d="M6 6h8M6 10h8M6 14h5" stroke="currentColor" stroke-width="2" fill="none"/>
                            </svg>
                            <span class="sidebar-label">Tax Management</span>
                        </a>
                    </li>
                @endif

            </ul>
        </div>
    </aside>

    <div id="app-shell" class="w-full sm:ml-64 transition-all duration-200">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between px-4 py-3">

                <div class="flex items-center gap-2">
                    <!-- Mobile: open drawer -->
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 sm:hidden dark:text-gray-400 dark:hover:bg-gray-800 dark:focus:ring-gray-600"
                        data-drawer-target="app-sidebar"
                        data-drawer-toggle="app-sidebar"
                        aria-controls="app-sidebar"
                    >
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <!-- Desktop: collapse sidebar -->
                    <button
                        type="button"
                        id="sidebar-collapse-btn"
                        class="hidden sm:inline-flex items-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:focus:ring-gray-600"
                    >
                        <span class="sr-only">Collapse sidebar</span>
                        <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>

                    <span class="text-sm font-semibold text-gray-900 dark:text-white sidebar-label">
                        {{ config('app.name', 'Floor Manager') }}
                    </span>
                </div>

                <!-- User dropdown -->
                <div class="relative">
                    <button
                        type="button"
                        class="flex rounded-full text-sm focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700"
                        id="user-menu-button"
                        aria-expanded="false"
                        data-dropdown-toggle="user-dropdown"
                        data-dropdown-placement="bottom-end"
                    >
                        <span class="sr-only">Open user menu</span>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                        </div>
                    </button>

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
                </div>

            </div>
        </header>
    </div>
</div>