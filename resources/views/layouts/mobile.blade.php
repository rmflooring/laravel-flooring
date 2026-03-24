<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="theme-color" content="#1d4ed8">
    <title>{{ $title ?? 'Floor Manager' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900">

    {{-- Mobile header --}}
    <header class="sticky top-0 z-10 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between shadow-sm">
        <a href="{{ auth()->user()->hasRole('installer') ? route('installer.dashboard') : route('dashboard') }}"
           class="flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <span class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Floor Manager</span>
        </a>
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
                    class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 font-medium">
                <span>{{ auth()->user()->name }}</span>
                <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-cloak
                 class="absolute right-0 mt-2 w-40 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg overflow-hidden z-50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2 px-4 py-3 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Content --}}
    <main class="max-w-lg mx-auto px-4 py-6 space-y-4">
        {{ $slot }}
    </main>

</body>
</html>
