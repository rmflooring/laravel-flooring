{{-- resources/views/admin/settings/label-printer.blade.php --}}
<x-app-layout>
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Label Printer</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose the label format used when printing inventory tags.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 dark:border-green-800 dark:bg-green-900/20">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.label-printer.update') }}">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">

            {{-- Standard option --}}
            <label class="flex items-start gap-4 px-6 py-5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                <input type="radio" name="format" value="standard"
                       {{ $format === 'standard' ? 'checked' : '' }}
                       class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Standard</span>
                        <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">4" × 6" portrait</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tall portrait label. Suitable for standard desktop or inkjet printers.</p>
                    {{-- Preview --}}
                    <div class="mt-3 inline-block border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 p-2" style="width:60px; height:90px; font-size:5px; color:#374151; position:relative; overflow:hidden;">
                        <div style="background:#1d4ed8; color:#fff; padding:2px 3px; border-radius:2px; margin-bottom:3px; font-size:4px; font-weight:700;">INVENTORY RECORD</div>
                        <div style="font-size:5.5px; font-weight:700; margin-bottom:2px; line-height:1.2;">Item Name</div>
                        <div style="font-size:4px; color:#555; margin-bottom:1px;">Qty: 24.5 SF</div>
                        <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:2px; padding:2px; margin-bottom:2px;">
                            <div style="font-size:3.5px; color:#166534; font-weight:700;">JOB #</div>
                            <div style="font-size:7px; font-weight:700; color:#15803d; font-family:monospace;">8</div>
                        </div>
                        <div style="position:absolute; bottom:4px; right:4px; width:18px; height:18px; background:#eee; border-radius:1px;"></div>
                    </div>
                </div>
            </label>

            {{-- Zebra option --}}
            <label class="flex items-start gap-4 px-6 py-5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30">
                <input type="radio" name="format" value="zebra"
                       {{ $format === 'zebra' ? 'checked' : '' }}
                       class="mt-0.5 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Zebra Label Printer</span>
                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 text-xs font-medium text-blue-700 dark:text-blue-300">6" × 4" landscape</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Landscape layout optimised for Zebra thermal label printers using 4"×6" stock.</p>
                    {{-- Preview --}}
                    <div class="mt-3 inline-block border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 p-2" style="width:90px; height:60px; font-size:5px; color:#374151; position:relative; overflow:hidden; display:flex; gap:4px;">
                        <div style="flex:1;">
                            <div style="background:#1d4ed8; color:#fff; padding:2px 3px; border-radius:2px; margin-bottom:2px; font-size:3.5px; font-weight:700; display:flex; justify-content:space-between;">
                                <span>INVENTORY</span><span>#42</span>
                            </div>
                            <div style="font-size:5px; font-weight:700; margin-bottom:2px; line-height:1.2;">Item Name</div>
                            <div style="font-size:3.5px; color:#555; margin-bottom:1px;">Qty: 24.5 SF</div>
                            <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:1px; padding:1px 2px;">
                                <div style="font-size:3px; color:#166534; font-weight:700;">JOB #</div>
                                <div style="font-size:6px; font-weight:700; color:#15803d; font-family:monospace;">8</div>
                            </div>
                        </div>
                        <div style="width:20px; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; gap:1px;">
                            <div style="width:18px; height:18px; background:#eee; border-radius:1px;"></div>
                            <div style="font-size:2.5px; color:#aaa;">Scan</div>
                        </div>
                    </div>
                </div>
            </label>

        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-700 dark:hover:bg-blue-800">
                Save
            </button>
        </div>
    </form>

</div>
</x-app-layout>
