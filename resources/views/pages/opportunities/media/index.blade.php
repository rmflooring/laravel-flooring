{{-- resources/views/pages/opportunities/media/index.blade.php --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Top Banner --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900">
                Media for Opportunity #{{ $opportunity->id }}
            </h1>

            <p class="text-sm text-gray-600 mt-2">
                <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                   class="text-blue-700 hover:underline">
                    ← Back to Opportunity
                </a>
            </p>
        </div>

        {{-- Media Controls --}}
<div class="flex items-center justify-between mb-4">
    <div class="text-sm text-gray-600">
        Showing {{ $media->total() }} media file(s)
    </div>

    <form method="GET" class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox"
                   name="show_archived"
                   value="1"
                   onchange="this.form.submit()"
                   {{ $showArchived ? 'checked' : '' }}
                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
            Show Archived
        </label>
    </form>
</div>

{{-- Thumbnail Grid --}}
<div class="bg-white border border-gray-200 rounded-lg p-4">
    @if ($media->count() < 1)
        <p class="text-sm text-gray-600">No media found for this opportunity.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach ($media as $doc)
                @php
					$absoluteUrl = Storage::disk($doc->disk)->url($doc->path);
					$url = parse_url($absoluteUrl, PHP_URL_PATH) ?: $absoluteUrl; // becomes "/storage/..."
					$isVideo = str_starts_with($doc->mime_type ?? '', 'video/');
				@endphp
                <button type="button"
                        class="group relative aspect-square w-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        data-media-url="{{ $url }}"
                        data-media-type="{{ $isVideo ? 'video' : 'image' }}">
                    @if ($isVideo)
                        <video class="h-full w-full object-cover"
                               muted
                               playsinline
                               preload="metadata">
                            <source src="{{ $url }}">
                        </video>

                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/80 border border-gray-200">
                                ▶
                            </div>
                        </div>
                    @else
                        <img src="{{ $url }}"
                             alt="{{ $doc->original_name }}"
                             class="h-full w-full object-cover group-hover:scale-105 transition-transform">
                    @endif

                    <div class="absolute inset-x-0 bottom-0 bg-black/50 text-white text-[11px] p-1">
				    <div class="truncate">{{ $doc->original_name }}</div>
				    <div class="truncate opacity-80">{{ $url }}</div>
					</div>
                </button>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $media->links() }}
        </div>
    @endif
</div>


    </div>

{{-- Lightbox Modal --}}
<div id="mediaLightbox"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-0 sm:p-6"
     aria-hidden="true">
    <div class="relative w-full h-full sm:h-auto sm:max-w-5xl sm:w-full sm:rounded-xl sm:bg-black sm:shadow-xl overflow-hidden">
        {{-- Close --}}
        <button type="button"
                id="lightboxClose"
                class="absolute top-3 right-3 z-10 inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white/90 text-gray-900 hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            ✕
        </button>

        {{-- Top Controls (desktop) --}}
        <div class="hidden sm:flex absolute top-3 left-3 z-10 gap-2">
            <button type="button" id="lightboxFirst"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-white/90 text-gray-900 hover:bg-white">
                First
            </button>
            <button type="button" id="lightboxPrev"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-white/90 text-gray-900 hover:bg-white">
                ← Prev
            </button>
            <button type="button" id="lightboxNext"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-white/90 text-gray-900 hover:bg-white">
                Next →
            </button>
            <button type="button" id="lightboxLast"
                    class="inline-flex items-center justify-center px-3 py-2 text-sm font-medium rounded-lg bg-white/90 text-gray-900 hover:bg-white">
                Last
            </button>
        </div>

        {{-- Mobile Controls --}}
		<div class="sm:hidden absolute bottom-0 inset-x-0 z-10 flex items-center justify-between gap-2 p-3 bg-black/40">
			<button type="button" id="lightboxFirstMobile"
					class="inline-flex items-center justify-center px-3 py-3 text-sm font-medium rounded-lg bg-white/90 text-gray-900">
				First
			</button>

			<button type="button" id="lightboxPrevMobile"
					class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium rounded-lg bg-white/90 text-gray-900">
				← Prev
			</button>

			<div id="lightboxCounter"
				 class="text-sm text-white px-2">
				—
			</div>

			<button type="button" id="lightboxNextMobile"
					class="inline-flex items-center justify-center px-4 py-3 text-sm font-medium rounded-lg bg-white/90 text-gray-900">
				Next →
			</button>

			<button type="button" id="lightboxLastMobile"
					class="inline-flex items-center justify-center px-3 py-3 text-sm font-medium rounded-lg bg-white/90 text-gray-900">
				Last
			</button>
		</div>

        {{-- Viewer --}}
        <div id="lightboxViewport"
             class="w-full h-full sm:h-[80vh] flex items-center justify-center bg-black">
            <img id="lightboxImage"
                 src=""
                 alt=""
                 class="hidden max-h-full max-w-full object-contain select-none" />

            <video id="lightboxVideo"
                   class="hidden max-h-full max-w-full"
                   controls
                   playsinline>
                <source id="lightboxVideoSource" src="">
            </video>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tiles = Array.from(document.querySelectorAll('[data-media-url]'));
    const modal = document.getElementById('mediaLightbox');

    const imgEl = document.getElementById('lightboxImage');
    const vidEl = document.getElementById('lightboxVideo');
    const vidSrc = document.getElementById('lightboxVideoSource');

    const btnClose = document.getElementById('lightboxClose');
    const btnFirst = document.getElementById('lightboxFirst');
    const btnPrev  = document.getElementById('lightboxPrev');
    const btnNext  = document.getElementById('lightboxNext');
    const btnLast  = document.getElementById('lightboxLast');

    const btnPrevM = document.getElementById('lightboxPrevMobile');
    const btnNextM = document.getElementById('lightboxNextMobile');
	
	const btnFirstM = document.getElementById('lightboxFirstMobile');
	const btnLastM  = document.getElementById('lightboxLastMobile');
	const counterEl = document.getElementById('lightboxCounter');

    let currentIndex = -1;

    function openModal(index) {
        if (index < 0 || index >= tiles.length) return;
        currentIndex = index;
		if (counterEl) counterEl.textContent = `${index + 1} / ${tiles.length}`;

        const url = tiles[index].dataset.mediaUrl;
        const type = tiles[index].dataset.mediaType;

        // stop any previous video
        try { vidEl.pause(); } catch (e) {}

        if (type === 'video') {
            imgEl.classList.add('hidden');
            vidEl.classList.remove('hidden');
            vidSrc.src = url;
            vidEl.load();
        } else {
            vidEl.classList.add('hidden');
            imgEl.classList.remove('hidden');
            imgEl.src = url;
            imgEl.alt = tiles[index].getAttribute('title') || 'Media';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.documentElement.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.documentElement.classList.remove('overflow-hidden');

        // clear sources
        imgEl.src = '';
        try { vidEl.pause(); } catch (e) {}
        vidSrc.src = '';
    }

    function goNext() {
        if (tiles.length < 1) return;
        openModal((currentIndex + 1) % tiles.length);
    }

    function goPrev() {
        if (tiles.length < 1) return;
        openModal((currentIndex - 1 + tiles.length) % tiles.length);
    }

    function goFirst() { if (tiles.length) openModal(0); }
    function goLast()  { if (tiles.length) openModal(tiles.length - 1); }

    // Tile clicks
    tiles.forEach((tile, idx) => {
        tile.addEventListener('click', () => openModal(idx));
    });

    // Buttons
    btnClose?.addEventListener('click', closeModal);
    btnPrev?.addEventListener('click', goPrev);
    btnNext?.addEventListener('click', goNext);
    btnFirst?.addEventListener('click', goFirst);
    btnLast?.addEventListener('click', goLast);
    btnPrevM?.addEventListener('click', goPrev);
    btnNextM?.addEventListener('click', goNext);
	btnFirstM?.addEventListener('click', goFirst);
	btnLastM?.addEventListener('click', goLast);

    // Click backdrop to close (but not when clicking inside viewer box)
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (modal.classList.contains('hidden')) return;

        if (e.key === 'Escape') closeModal();
        if (e.key === 'ArrowRight') goNext();
        if (e.key === 'ArrowLeft') goPrev();
        if (e.key === 'Home') goFirst();
        if (e.key === 'End') goLast();
    });

    // Basic swipe for mobile (left/right)
    const viewport = document.getElementById('lightboxViewport');
    let startX = null;

    viewport.addEventListener('touchstart', (e) => {
        if (!e.touches || e.touches.length !== 1) return;
        startX = e.touches[0].clientX;
    }, { passive: true });

    viewport.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const endX = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].clientX : startX;
        const diff = endX - startX;
        startX = null;

        // threshold
        if (Math.abs(diff) < 40) return;
        if (diff < 0) goNext();
        else goPrev();
    }, { passive: true });
});
</script>

</x-app-layout>
