<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Document Signing' }} — RM Flooring</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|dancing-script:400,600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">

    <header class="bg-white border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="text-lg font-semibold text-gray-800">RM Flooring & Cabinetry</div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} RM Flooring & Cabinetry. This is a secure document signing page.
    </footer>

</body>
</html>
