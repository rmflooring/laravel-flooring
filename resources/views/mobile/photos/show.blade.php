<x-mobile-layout title="Job Photos">

    {{-- Flash --}}
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 px-4 py-3 flex items-center justify-between gap-3">
            <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
            <button onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400 text-lg leading-none">&times;</button>
        </div>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Job Photos</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-0.5">{{ $media->count() }} photo{{ $media->count() !== 1 ? 's' : '' }}</p>
        </div>
        <button onclick="history.back()"
                class="inline-flex items-center gap-1.5 text-sm text-blue-600 dark:text-blue-400 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </button>
    </div>

    @if($media->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 p-8 text-center">
            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
            <p class="text-sm text-gray-400 dark:text-gray-500">No photos yet.</p>
        </div>
    @else
        {{-- Grid --}}
        <div class="grid grid-cols-2 gap-2">
            @foreach($media as $i => $doc)
                <button type="button"
                        data-idx="{{ $i }}"
                        data-url="{{ $doc->url }}"
                        data-uploader="{{ $doc->creator?->name ?? 'Unknown' }}"
                        data-date="{{ $doc->created_at?->format('M j, Y g:i A') }}"
                        class="relative aspect-square w-full overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800 focus:outline-none active:scale-95 transition-transform"
                        onclick="openPhoto(this)">
                    <img src="{{ $doc->url }}"
                         alt="{{ $doc->original_name }}"
                         loading="lazy"
                         decoding="async"
                         class="h-full w-full object-cover">
                    <div class="absolute inset-x-0 bottom-0 bg-black/50 px-2 py-1">
                        <p class="text-[10px] text-white truncate">{{ $doc->creator?->name ?? 'Unknown' }}</p>
                        <p class="text-[9px] text-white/70 truncate">{{ $doc->created_at?->format('M j') }}</p>
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Fullscreen lightbox --}}
    <div id="photoLightbox"
         style="display:none"
         class="fixed inset-0 z-50 bg-black flex flex-col">

        {{-- Top bar --}}
        <div class="flex items-center justify-between px-4 py-3 bg-black/60">
            <div>
                <p id="lbUploader" class="text-xs text-white/80"></p>
                <p id="lbDate"     class="text-[10px] text-white/50 mt-0.5"></p>
            </div>
            <button onclick="closeLightbox()"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-white/10 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Image --}}
        <div id="lbViewport"
             class="flex-1 flex items-center justify-center overflow-hidden"
             ontouchstart="lbTouchStart(event)"
             ontouchend="lbTouchEnd(event)">
            <img id="lbImg" src="" alt="" class="max-h-full max-w-full object-contain select-none">
        </div>

        {{-- Bottom nav --}}
        <div class="flex items-center justify-between px-6 py-4 bg-black/60">
            <button onclick="lbPrev()"
                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 text-white active:bg-white/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span id="lbCounter" class="text-sm text-white/60"></span>
            <button onclick="lbNext()"
                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/10 text-white active:bg-white/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    <script>
    var lbTiles = Array.from(document.querySelectorAll('[data-idx]'));
    var lbIndex = 0;
    var lbSwipeX = null;

    function openPhoto(el) {
        lbIndex = parseInt(el.dataset.idx);
        showPhoto();
        document.getElementById('photoLightbox').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('photoLightbox').style.display = 'none';
        document.body.style.overflow = '';
    }

    function showPhoto() {
        if (lbIndex < 0) lbIndex = lbTiles.length - 1;
        if (lbIndex >= lbTiles.length) lbIndex = 0;
        var t = lbTiles[lbIndex];
        document.getElementById('lbImg').src      = t.dataset.url;
        document.getElementById('lbUploader').textContent = t.dataset.uploader || '';
        document.getElementById('lbDate').textContent     = t.dataset.date || '';
        document.getElementById('lbCounter').textContent  = (lbIndex + 1) + ' / ' + lbTiles.length;
    }

    function lbNext() { lbIndex++; showPhoto(); }
    function lbPrev() { lbIndex--; showPhoto(); }

    function lbTouchStart(e) { lbSwipeX = e.touches[0]?.clientX ?? null; }
    function lbTouchEnd(e) {
        if (lbSwipeX === null) return;
        var diff = (e.changedTouches[0]?.clientX ?? lbSwipeX) - lbSwipeX;
        lbSwipeX = null;
        if (Math.abs(diff) < 40) return;
        diff < 0 ? lbNext() : lbPrev();
    }
    </script>

</x-mobile-layout>
