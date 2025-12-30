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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Card 1 -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">Active Jobs</h4>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">42</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">+8 this month</p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">Pending Estimates</h4>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">19</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">-3 from last week</p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">Completed This Month</h4>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">28</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">+12% vs last month</p>
                </div>

                <!-- Card 4 -->
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                    <h4 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">Revenue</h4>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">$124k</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">YTD</p>
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