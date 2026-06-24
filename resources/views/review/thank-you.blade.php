<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You — RM Flooring</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4 py-12">

    <div class="w-full max-w-md text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Thank you, {{ $review->customer_name }}!</h1>
        <p class="text-gray-500 mt-3">
            We appreciate your honest feedback. A member of our team will be in touch shortly to see how we can make things right.
        </p>
        <p class="text-gray-400 text-sm mt-8">— RM Flooring, Coquitlam BC</p>
    </div>

</body>
</html>
