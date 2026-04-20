<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FM') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
		<style>
  body.sidebar-collapsed .sidebar-label { display: none; }
  body.sidebar-collapsed #app-sidebar .sidebar-link { justify-content: center; }
  [x-cloak] { display: none !important; }
</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.sidebar')

            <!-- Page Heading -->
       

            <!-- Page Content -->
            <main class="sm:ml-64 p-4 max-w-none">
            @auth
                @php
                    $msDisconnected = \App\Models\MicrosoftAccount::where('user_id', auth()->id())
                        ->where('is_connected', false)
                        ->whereNotNull('connected_at')
                        ->exists();
                @endphp
                @if ($msDisconnected)
                    <div class="mb-4 flex items-center gap-3 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-200">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <span>Your Microsoft 365 calendar connection has expired. Calendar events will not be created until you reconnect.</span>
                        <a href="{{ route('pages.settings.integrations.microsoft.index') }}"
                           class="ml-auto shrink-0 font-semibold underline hover:no-underline">
                            Reconnect
                        </a>
                    </div>
                @endif

                @php $nasOffline = \App\Models\Setting::get('nas_status') === 'offline'; @endphp
                @if ($nasOffline)
                    <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200">
                        <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        <span>
                            <strong>NAS Storage is offline.</strong>
                            File uploads are unavailable. Last checked: {{ \App\Models\Setting::get('nas_last_checked', 'unknown') }}.
                        </span>
                    </div>
                @endif
            @endauth
    @isset($header)
        <div class="mb-6">
            {{ $header }}
        </div>
    @endisset

    @if(session('success'))
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-medium mb-1">Please fix the following errors:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{ $slot }}
</main>
        </div>
		        <script>
        document.addEventListener('DOMContentLoaded', () => {
          const sidebar = document.getElementById('app-sidebar');
          const btn = document.getElementById('sidebar-collapse-btn');
          const main = document.querySelector('main');

          if (!sidebar || !btn || !main) return;

          function apply(collapsed) {
            sidebar.classList.toggle('w-64', !collapsed);
            sidebar.classList.toggle('w-16', collapsed);

            main.classList.toggle('sm:ml-64', !collapsed);
            main.classList.toggle('sm:ml-16', collapsed);

            document.body.classList.toggle('sidebar-collapsed', collapsed);

            localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
          }

          const saved = localStorage.getItem('sidebarCollapsed') === '1';
          apply(saved);

          btn.addEventListener('click', () => {
            const collapsed = sidebar.classList.contains('w-16');
            apply(!collapsed);
          });
        });
        </script>
		<script src="{{ asset('assets/js/phone-format.js') }}"></script>
		<script src="{{ asset('assets/js/postal-format.js') }}"></script>
		<script src="{{ asset('assets/js/email-validate.js') }}"></script>
    </body>
</html>