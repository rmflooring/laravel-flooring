{{-- Box Quantity Prompt Modal --}}
{{-- Shown when a material qty doesn't fill complete boxes and use_box_qty = true --}}
<div id="box-qty-modal"
     class="hidden fixed inset-0 z-50 items-center justify-center bg-black bg-opacity-50"
     style="display:none">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">

        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">Use Box Quantity?</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="font-medium text-gray-900 dark:text-white" data-box-style-name></span>
                    comes in boxes of <span class="font-medium" data-box-units-per></span>.
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Your qty of <span class="font-medium text-gray-900 dark:text-white" data-box-current-qty></span>
                    requires <span class="font-medium" data-box-count></span>.
                    Round up to <span class="font-semibold text-blue-600 dark:text-blue-400 text-base" data-box-suggested-qty></span>?
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-5">
            <button id="box-qty-cancel" type="button"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg
                           hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                No, keep <span data-box-current-qty></span>
            </button>
            <button id="box-qty-confirm" type="button"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-700 rounded-lg
                           hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700">
                Yes, use <span data-box-suggested-qty></span>
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('box-qty-modal');
    if (!modal) return;

    function close() {
        modal.style.display = 'none';
        window._boxQtyPendingInput = null;
        window._boxQtyPendingValue = null;
    }

    document.getElementById('box-qty-confirm').addEventListener('click', function () {
        if (window._boxQtyPendingInput != null && window._boxQtyPendingValue != null) {
            window._boxQtyPendingInput.value = window._boxQtyPendingValue;
            window._boxQtyPendingInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        close();
    });

    document.getElementById('box-qty-cancel').addEventListener('click', close);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });
})();
</script>
