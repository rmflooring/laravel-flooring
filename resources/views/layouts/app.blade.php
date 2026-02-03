<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
		<style>
  body.sidebar-collapsed .sidebar-label { display: none; }
  body.sidebar-collapsed #app-sidebar .sidebar-link { justify-content: center; }
</style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.sidebar')

            <!-- Page Heading -->
       

            <!-- Page Content -->
            <main class="sm:ml-64 p-4 max-w-none">
    @isset($header)
        <div class="mb-6">
            {{ $header }}
        </div>
    @endisset

    {{ $slot }}
</main>
        </div>
		        <!-- Flowbite JavaScript (CDN) - enables all interactive components -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.5.2/flowbite.min.js"></script>

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
    </body>
</html>