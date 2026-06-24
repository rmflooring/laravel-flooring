<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How was your experience? — RM Flooring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .star-btn { cursor: pointer; transition: transform 0.1s; }
        .star-btn:hover, .star-btn.active { transform: scale(1.15); }
        .star-btn svg { fill: #d1d5db; transition: fill 0.15s; }
        .star-btn.active svg, .star-btn.hovered svg { fill: #f59e0b; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4 py-12">

    <div class="w-full max-w-md">

        {{-- Logo / Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-600 mb-4">
                <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">RM Flooring</h1>
            <p class="text-gray-500 text-sm mt-1">Coquitlam, BC</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-xl font-semibold text-gray-900 text-center">How was your experience?</h2>
            <p class="text-gray-500 text-center mt-2 text-sm">
                Hi {{ $review->customer_name }}, we'd love to hear how your flooring project went!
            </p>

            <form id="review-form" method="POST" action="{{ url('/review/' . $review->token) }}" class="mt-8">
                @csrf

                {{-- Star Rating --}}
                <div class="flex justify-center gap-3 mb-6" id="stars">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" class="star-btn" data-value="{{ $i }}" onclick="selectRating({{ $i }})">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-12 h-12">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        </button>
                    @endfor
                </div>

                <input type="hidden" name="rating" id="rating-input" value="">

                {{-- Rating label --}}
                <p id="rating-label" class="text-center text-sm font-medium text-gray-500 mb-6 h-5"></p>

                {{-- Feedback (shown for low ratings) --}}
                <div id="feedback-section" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        We're sorry to hear that. What could we have done better?
                    </label>
                    <textarea name="feedback" rows="4"
                        class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Your feedback helps us improve..."></textarea>
                </div>

                <button type="submit" id="submit-btn" disabled
                    class="mt-6 w-full bg-blue-600 text-white font-semibold py-3 rounded-xl opacity-40 cursor-not-allowed transition-opacity">
                    Submit Review
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 mt-6">
            Your feedback is sent directly to RM Flooring.
        </p>
    </div>

    <script>
        const labels = ['', 'Poor', 'Fair', 'Good', 'Great', 'Excellent!'];
        const colors = ['', 'text-red-500', 'text-orange-500', 'text-yellow-500', 'text-green-500', 'text-green-600'];
        let selected = 0;

        function selectRating(val) {
            selected = val;
            document.getElementById('rating-input').value = val;
            document.getElementById('rating-label').textContent = labels[val];
            document.getElementById('rating-label').className = 'text-center text-sm font-medium mb-6 h-5 ' + colors[val];

            // Fill stars
            document.querySelectorAll('.star-btn').forEach((btn, idx) => {
                btn.classList.toggle('active', idx < val);
            });

            // Show feedback for 1-3, hide for 4-5
            document.getElementById('feedback-section').classList.toggle('hidden', val >= 4);

            // Enable submit
            const btn = document.getElementById('submit-btn');
            btn.disabled = false;
            btn.classList.remove('opacity-40', 'cursor-not-allowed');
        }

        // Hover effect
        document.querySelectorAll('.star-btn').forEach((btn, idx) => {
            btn.addEventListener('mouseenter', () => {
                document.querySelectorAll('.star-btn').forEach((b, i) => {
                    b.classList.toggle('hovered', i <= idx && i >= selected);
                    if (i < selected) b.classList.add('active');
                });
            });
            btn.addEventListener('mouseleave', () => {
                document.querySelectorAll('.star-btn').forEach(b => b.classList.remove('hovered'));
            });
        });
    </script>

</body>
</html>
