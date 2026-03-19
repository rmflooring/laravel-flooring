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
        <div class="relative h-full px-3 py-4 flex flex-col">
			<!-- Desktop: collapse sidebar (inside sidebar) -->
<button
    type="button"
    id="sidebar-collapse-btn"
    class="hidden sm:inline-flex absolute top-3 right-3 items-center rounded-lg p-2 text-sm text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-800 dark:focus:ring-gray-600"
>
    <span class="sr-only">Collapse sidebar</span>
    <svg class="h-6 w-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M9 18l6-6-6-6"/>
    </svg>
</button>


            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="mb-6 flex items-center space-x-3 rtl:space-x-reverse flex-shrink-0">
                <x-application-logo class="block h-8 w-auto fill-current text-gray-800 dark:text-white" />
                <span class="sidebar-label self-center whitespace-nowrap text-lg font-semibold text-gray-900 dark:text-white">
                    {{ config('app.name', 'Floor Manager') }}
                </span>
            </a>

            <ul class="space-y-1 font-medium flex-1 overflow-y-auto min-h-0">

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

			    {{-- Sales --}}
                <li>
                    <a href="{{ route('pages.sales.index') }}"
                       class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                       <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400"
						 xmlns="http://www.w3.org/2000/svg"
						 fill="none"
						 viewBox="0 0 24 24"
						 stroke="currentColor"
						 stroke-width="1.5">
						<circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round"/>
						<path stroke-linecap="round" stroke-linejoin="round"
							  d="M12 8c-1.657 0-3 1.12-3 2.5S10.343 13 12 13s3 1.12 3 2.5S13.657 18 12 18m0-10v10" />
					</svg>
                        <span class="sidebar-label">Sales</span>
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

                {{-- Vendors accordion --}}
                <li x-data="{ open: false }">
                    <button @click="open = !open"
                            class="sidebar-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6 2a2 2 0 0 0-2 2v14h12V4a2 2 0 0 0-2-2H6Z"/>
                                <path d="M2 6H1a1 1 0 0 0-1 1v11a2 2 0 0 0 2 2h2V6H2Z"/>
                                <path d="M18 6h1a1 1 0 0 1 1 1v11a2 2 0 0 1-2 2h-2V6h2Z"/>
                            </svg>
                            <span class="sidebar-label">Vendors</span>
                        </div>
                        <svg :class="open ? 'rotate-90' : ''" class="sidebar-label h-4 w-4 flex-shrink-0 text-gray-500 transition-transform dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <ul x-show="open" class="mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('admin.vendors.index') }}"
                               class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                Vendors
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.installers.index') }}"
                               class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                Installers
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Products accordion --}}
                <li x-data="{ open: false }">
                    <div class="flex items-center">
                        <a href="{{ route('admin.products.index') }}"
                           class="sidebar-link flex flex-1 items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 0 0 5l10 5 10-5L10 0Z"/>
                                <path d="M0 7l10 5 10-5v8l-10 5L0 15V7Z"/>
                            </svg>
                            <span class="sidebar-label">Products</span>
                        </a>
                        <button @click="open = !open"
                                class="sidebar-label p-2 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-800">
                            <svg :class="open ? 'rotate-90' : ''" class="h-4 w-4 flex-shrink-0 transition-transform" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <ul x-show="open" class="mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('admin.product_types.index') }}"
                               class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                Product Types
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.product_lines.index') }}"
                               class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                Product Lines
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Labour accordion --}}
                <li x-data="{ open: false }">
                    <button @click="open = !open"
                            class="sidebar-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6 2a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h6v-6a2 2 0 0 1 2-2h6V8a2 2 0 0 0-2-2h-2V4a2 2 0 0 0-2-2H6Z"/>
                                <path d="M20 14h-6a1 1 0 0 0-1 1v5h6a2 2 0 0 0 2-2v-3a1 1 0 0 0-1-1Z"/>
                            </svg>
                            <span class="sidebar-label">Labour</span>
                        </div>
                        <svg :class="open ? 'rotate-90' : ''" class="sidebar-label h-4 w-4 flex-shrink-0 text-gray-500 transition-transform dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <ul x-show="open" class="mt-1 space-y-1 pl-10">
                        <li>
                            <a href="{{ route('admin.labour_types.index') }}"
                               class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                Labour Types
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.labour_items.index') }}"
                               class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                Labour Items
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Freight --}}
                <li>
                    <a href="{{ route('admin.freight_items.index') }}"
					   class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
					<svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400"
						 xmlns="http://www.w3.org/2000/svg"
						 fill="none"
						 viewBox="0 0 24 24"
						 stroke="currentColor"
						 stroke-width="1.5">
						<path stroke-linecap="round"
							  stroke-linejoin="round"
							  d="M3 7.5h11.25M3 12h11.25m-11.25 4.5h11.25M16.5 8.25h2.25l2.25 2.25v4.5h-4.5M6.75 17.25a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>
					</svg>
					<span class="sidebar-label">Freight</span>
				</a>
                </li>

                {{-- Inventory --}}
                <li x-data="{ open: {{ request()->routeIs('pages.inventory.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="sidebar-link flex w-full items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 text-left">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="sidebar-label flex-1">Inventory</span>
                        <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="mt-1 ml-8 space-y-1">
                        <li>
                            <a href="{{ route('pages.inventory.index') }}"
                               class="sidebar-link flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 {{ request()->routeIs('pages.inventory.index') || request()->routeIs('pages.inventory.show') ? 'bg-gray-100 font-medium dark:bg-gray-800' : '' }}">
                                Records
                            </a>
                        </li>
                        @can('view rfcs')
                        <li>
                            <a href="{{ route('pages.inventory.rfc.index') }}"
                               class="sidebar-link flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 {{ request()->routeIs('pages.inventory.rfc.*') ? 'bg-gray-100 font-medium dark:bg-gray-800' : '' }}">
                                RFC
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- Warehouse --}}
                @can('view pick tickets')
                <li x-data="{ open: {{ request()->routeIs('pages.warehouse.*') ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="sidebar-link flex w-full items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 text-left">
                        <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                        <span class="sidebar-label flex-1">Warehouse</span>
                        <svg class="h-4 w-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul x-show="open" x-cloak class="mt-1 ml-8 space-y-1">
                        <li>
                            <a href="{{ route('pages.warehouse.pick-tickets.index') }}"
                               class="sidebar-link flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 {{ request()->routeIs('pages.warehouse.pick-tickets.*') ? 'bg-gray-100 font-medium dark:bg-gray-800' : '' }}">
                                Pick Tickets
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan


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

                    {{-- People accordion --}}
                    <li x-data="{ open: false }">
                        <button @click="open = !open"
                                class="sidebar-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 0a4 4 0 1 1 0 8 4 4 0 0 1 0-8Z"/>
                                    <path d="M2 20a8 8 0 0 1 16 0H2Z"/>
                                </svg>
                                <span class="sidebar-label">People</span>
                            </div>
                            <svg :class="open ? 'rotate-90' : ''" class="sidebar-label h-4 w-4 flex-shrink-0 text-gray-500 transition-transform dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <ul x-show="open" class="mt-1 space-y-1 pl-10">
                            @if ($canManageUsers)
                                <li>
                                    <a href="{{ route('admin.users.index') }}"
                                       class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                        Users
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a href="{{ route('admin.employees.index') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Employees
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/admin/project-managers') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Project Managers
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/admin/vendor-reps') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Vendor Reps
                                </a>
                            </li>
                        </ul>
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

                    {{-- Document Labels --}}
                    <li>
                        <a href="{{ route('admin.opportunity_document_labels.index') }}"
                           class="sidebar-link flex items-center gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h10M7 11h10M7 15h4m-7 5h14a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1Z"/>
                            </svg>
                            <span class="sidebar-label">Document Labels</span>
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

                    {{-- Chart of Accounts accordion --}}
                    <li x-data="{ open: false }">
                        <button @click="open = !open"
                                class="sidebar-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 0 0 5l10 5 10-5L10 0Z"/>
                                    <path d="M0 7l10 5 10-5v8l-10 5L0 15V7Z"/>
                                </svg>
                                <span class="sidebar-label">Chart of Accounts</span>
                            </div>
                            <svg :class="open ? 'rotate-90' : ''" class="sidebar-label h-4 w-4 flex-shrink-0 text-gray-500 transition-transform dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <ul x-show="open" class="mt-1 space-y-1 pl-10">
                            <li>
                                <a href="{{ route('admin.account_types.index') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Account Types
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.detail_types.index') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Detail Types
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.gl_accounts.index') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    GL Accounts
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- Tax Management accordion --}}
                    <li x-data="{ open: false }">
                        <button @click="open = !open"
                                class="sidebar-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V3Z"/>
                                    <path d="M6 6h8M6 10h8M6 14h5" stroke="currentColor" stroke-width="2" fill="none"/>
                                </svg>
                                <span class="sidebar-label">Tax Management</span>
                            </div>
                            <svg :class="open ? 'rotate-90' : ''" class="sidebar-label h-4 w-4 flex-shrink-0 text-gray-500 transition-transform dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <ul x-show="open" class="mt-1 space-y-1 pl-10">
                            <li>
                                <a href="{{ route('admin.tax.index') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Tax Overview
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/admin/tax-agencies') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Tax Agencies
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/admin/tax-rates') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-300 dark:hover:bg-gray-800">
                                    Tax Rates
                                </a>
                            </li>
                            <li>
                                <a href="{{ url('/admin/tax-groups') }}"
                                   class="block rounded-lg px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800">
                                    Tax Groups
                                </a>
                            </li>
                        </ul>
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
                                <a href="{{ route('pages.settings.email-templates.index') }}"
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-600">
                                    Email Templates
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
