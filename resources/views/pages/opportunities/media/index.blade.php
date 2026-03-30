{{-- resources/views/pages/opportunities/media/index.blade.php --}}
<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        {{-- Top Banner --}}
        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6 text-center">
            <h1 class="text-2xl font-semibold text-gray-900">
                Media for Opportunity #{{ $opportunity->id }}
            </h1>

            <div class="mt-4 flex justify-center gap-3">
                <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                    ← Back to Opportunity
                </a>
                <a href="{{ route('pages.opportunities.documents.index', $opportunity->id) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                    Documents
                </a>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-green-800" role="alert">
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 flex items-center rounded-lg border border-red-200 bg-red-50 p-4 text-red-800" role="alert">
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Media Controls --}}
<div x-data="mediaGallery()" x-init="init()" :data-select-mode="selectMode">

<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="text-sm text-gray-600">
        Showing {{ $media->total() }} media file(s)
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-3">
            @if($uploaders->isNotEmpty())
            <select name="uploaded_by"
                    onchange="this.form.submit()"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-1.5 bg-white text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All uploaders</option>
                @foreach($uploaders as $u)
                    <option value="{{ $u->id }}" {{ (string)$uploadedBy === (string)$u->id ? 'selected' : '' }}>
                        {{ $u->name }}
                    </option>
                @endforeach
            </select>
            @endif

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

        {{-- Upload Photos button --}}
        <button type="button"
                id="gallery-upload-toggle"
                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            Upload Photos
        </button>

        {{-- Select toggle --}}
        @if($media->count() > 0)
        <button type="button"
                @click="toggleSelectMode()"
                class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"
                :class="selectMode ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="selectMode ? 'Cancel' : 'Select'"></span>
        </button>
        @endif
    </div>
</div>

{{-- Upload Panel --}}
<div id="gallery-upload-panel"
     class="hidden mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">

    {{-- Drop Zone --}}
    <div id="gallery-drop-zone"
         class="cursor-pointer select-none rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 p-6 text-center dark:border-gray-600 dark:bg-gray-900/30">
        <svg class="mx-auto mb-2 h-8 w-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
        </svg>
        <p class="font-medium text-gray-800 dark:text-gray-100">Drag &amp; drop photos or videos here or click to select</p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Images and videos up to 500 MB each</p>
        <input id="gallery-upload-input" type="file" multiple accept="image/*,video/*" class="hidden">
    </div>

    {{-- Per-file queue --}}
    <div id="gallery-file-queue" class="hidden mt-3 space-y-2"></div>

    {{-- Progress bar --}}
    <div id="gallery-progress-wrap" class="hidden mt-3">
        <div class="mb-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
            <span id="gallery-progress-label">Uploading…</span>
            <span id="gallery-progress-pct">0%</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div id="gallery-progress-bar"
                 class="h-2 rounded-full bg-blue-600 transition-all duration-150"
                 style="width: 0%"></div>
        </div>
    </div>

    {{-- Buttons --}}
    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button type="button"
                id="gallery-upload-submit"
                class="hidden inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
            </svg>
            Upload
        </button>
        <button type="button"
                id="gallery-upload-cancel"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
            Cancel
        </button>
    </div>
</div>

{{-- Selection action bar --}}
<div x-show="selectMode" x-cloak
     class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5">
    <div class="flex items-center gap-3">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-blue-800 cursor-pointer">
            <input type="checkbox"
                   @change="toggleAll($event.target.checked)"
                   :checked="selectedCount > 0 && selectedCount === totalTiles"
                   :indeterminate="selectedCount > 0 && selectedCount < totalTiles"
                   class="w-4 h-4 text-blue-600 border-gray-300 rounded">
            Select All
        </label>
        <span class="text-sm text-blue-700" x-text="selectedCount + ' selected'"></span>
    </div>

    <div class="flex items-center gap-2" x-show="selectedCount > 0">
        {{-- Soft delete (archive) --}}
        <form method="POST"
              action="{{ route('pages.opportunities.documents.bulkDestroy', $opportunity) }}"
              @submit.prevent="submitBulk($el)">
            @csrf
            @method('DELETE')
            <input type="hidden" name="redirect_to" value="media">
            <template x-for="id in getSelectedIds()" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-sm font-medium text-amber-700 hover:bg-amber-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                </svg>
                Archive
            </button>
        </form>

        {{-- Force delete (admin only) --}}
        @role('admin')
        <form method="POST"
              action="{{ route('pages.opportunities.documents.bulkForceDestroy', $opportunity) }}"
              @submit.prevent="confirmForceDelete($el)">
            @csrf
            @method('DELETE')
            <input type="hidden" name="redirect_to" value="media">
            <template x-for="id in getSelectedIds()" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                </svg>
                Delete Permanently
            </button>
        </form>
        @endrole
    </div>
</div>

{{-- Thumbnail Grid --}}
<div class="bg-white border border-gray-200 rounded-lg p-4">
    @if ($media->count() < 1)
        <p class="text-sm text-gray-600">No media found for this opportunity.</p>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach ($media as $doc)
                @php
                    $url = parse_url($doc->url, PHP_URL_PATH) ?: $doc->url;
                    $isVideo      = str_starts_with($doc->mime_type ?? '', 'video/');
                    $uploaderName = $doc->creator?->name ?? 'Unknown';
                    $uploadedAt   = $doc->created_at?->format('M j, Y g:i A') ?? '';
                @endphp
                <div class="relative group aspect-square">
                    {{-- Selection checkbox (shown in select mode) --}}
                    <div x-show="selectMode"
                         class="absolute top-1.5 left-1.5 z-10"
                         @click.stop>
                        <input type="checkbox"
                               value="{{ $doc->id }}"
                               class="media-item-checkbox w-5 h-5 rounded border-2 border-white text-blue-600 shadow cursor-pointer"
                               @change="toggleItem($event.target.value, $event.target.checked)"
                               :checked="isSelected('{{ $doc->id }}')">
                    </div>

                    {{-- Selected overlay --}}
                    <div x-show="selectMode && isSelected('{{ $doc->id }}')"
                         class="absolute inset-0 rounded-lg ring-2 ring-blue-500 bg-blue-500/10 z-10 pointer-events-none"></div>

                    <button type="button"
                            class="w-full h-full overflow-hidden rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            data-media-url="{{ $url }}"
                            data-media-type="{{ $isVideo ? 'video' : 'image' }}"
                            data-uploader="{{ $uploaderName }}"
                            data-uploaded-at="{{ $uploadedAt }}"
                            data-filename="{{ $doc->original_name }}"
                            @click="selectMode ? toggleItem('{{ $doc->id }}') : null">
                        @if ($isVideo)
                            <video class="h-full w-full object-cover" muted playsinline preload="metadata">
                                <source src="{{ $url }}">
                            </video>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/80 border border-gray-200">▶</div>
                            </div>
                        @else
                            <img src="{{ $url }}"
                                 alt="{{ $doc->original_name }}"
                                 loading="lazy"
                                 decoding="async"
                                 class="h-full w-full object-cover group-hover:scale-105 transition-transform">
                        @endif

                        <div class="absolute inset-x-0 bottom-0 bg-black/60 text-white text-[10px] px-1.5 py-1">
                            <div class="truncate font-medium">{{ $doc->original_name }}</div>
                            <div class="truncate opacity-80">{{ $uploaderName }} &middot; {{ $doc->created_at?->format('M j, Y') }}</div>
                        </div>
                    </button>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $media->links() }}
        </div>
    @endif
</div>

</div>{{-- end x-data --}}


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

        {{-- Caption bar --}}
        <div id="lightboxCaption"
             class="hidden sm:flex absolute bottom-0 inset-x-0 z-10 items-center justify-between gap-4 px-4 py-2 bg-black/60 text-white text-xs">
            <span id="lightboxCaptionFilename" class="truncate font-medium"></span>
            <span id="lightboxCaptionMeta" class="shrink-0 opacity-75 text-right"></span>
        </div>
    </div>
</div>

<script>
function mediaGallery() {
    return {
        selectMode: false,
        selectedMap: {},   // { "id": true } — O(1) lookup instead of array.includes()
        selectedCount: 0,
        totalTiles: 0,

        init() {
            this.totalTiles = document.querySelectorAll('.media-item-checkbox').length;
        },

        isSelected(id) {
            return !!this.selectedMap[String(id)];
        },

        getSelectedIds() {
            return Object.keys(this.selectedMap);
        },

        toggleSelectMode() {
            this.selectMode = !this.selectMode;
            if (!this.selectMode) {
                this.selectedMap = {};
                this.selectedCount = 0;
            }
        },

        toggleItem(id, checked) {
            id = String(id);
            if (checked === undefined) checked = !this.selectedMap[id];
            const map = { ...this.selectedMap };
            if (checked) {
                map[id] = true;
            } else {
                delete map[id];
            }
            this.selectedMap = map;
            this.selectedCount = Object.keys(map).length;
        },

        toggleAll(checked) {
            if (checked) {
                const map = {};
                document.querySelectorAll('.media-item-checkbox').forEach(cb => { map[cb.value] = true; });
                this.selectedMap = map;
                this.selectedCount = Object.keys(map).length;
            } else {
                this.selectedMap = {};
                this.selectedCount = 0;
            }
        },

        submitBulk(form) {
            if (this.selectedCount === 0) return;
            if (!confirm(`Archive ${this.selectedCount} photo(s)? They can be restored from the Documents page.`)) return;
            form.submit();
        },

        confirmForceDelete(form) {
            if (this.selectedCount === 0) return;
            if (!confirm(`Permanently delete ${this.selectedCount} photo(s)? This cannot be undone.`)) return;
            form.submit();
        },
    };
}

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

    const btnFirstM  = document.getElementById('lightboxFirstMobile');
    const btnLastM   = document.getElementById('lightboxLastMobile');
    const counterEl  = document.getElementById('lightboxCounter');
    const captionEl  = document.getElementById('lightboxCaption');
    const capFile    = document.getElementById('lightboxCaptionFilename');
    const capMeta    = document.getElementById('lightboxCaptionMeta');

    let currentIndex = -1;

    function openModal(index) {
        if (index < 0 || index >= tiles.length) return;
        currentIndex = index;
        if (counterEl) counterEl.textContent = `${index + 1} / ${tiles.length}`;

        const tile     = tiles[index];
        const url      = tile.dataset.mediaUrl;
        const type     = tile.dataset.mediaType;
        const uploader = tile.dataset.uploader || '';
        const uploadedAt = tile.dataset.uploadedAt || '';
        const filename = tile.dataset.filename || '';

        if (captionEl) captionEl.classList.remove('hidden');
        if (capFile)   capFile.textContent = filename;
        if (capMeta)   capMeta.textContent  = uploader + (uploadedAt ? '  ·  ' + uploadedAt : '');

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
            imgEl.alt = filename || 'Media';
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

    // Tile clicks — skip lightbox when in select mode
    const galleryRoot = document.querySelector('[data-select-mode]') ?? document.querySelector('[x-data]');
    tiles.forEach((tile, idx) => {
        tile.addEventListener('click', () => {
            if (galleryRoot?.dataset.selectMode === 'true') return;
            openModal(idx);
        });
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

    // =========================================================
    // Gallery upload panel
    // =========================================================
    const gUploadToggle  = document.getElementById('gallery-upload-toggle');
    const gUploadPanel   = document.getElementById('gallery-upload-panel');
    const gDropZone      = document.getElementById('gallery-drop-zone');
    const gUploadInput   = document.getElementById('gallery-upload-input');
    const gFileQueue     = document.getElementById('gallery-file-queue');
    const gProgressWrap  = document.getElementById('gallery-progress-wrap');
    const gProgressBar   = document.getElementById('gallery-progress-bar');
    const gProgressPct   = document.getElementById('gallery-progress-pct');
    const gProgressLabel = document.getElementById('gallery-progress-label');
    const gSubmitBtn     = document.getElementById('gallery-upload-submit');
    const gCancelBtn     = document.getElementById('gallery-upload-cancel');

    const gUploadUrl  = '{{ route('pages.opportunities.documents.store', $opportunity->id) }}';
    const gCsrfToken  = '{{ csrf_token() }}';

    let gQueue = []; // [{ file: File, description: '' }]

    function gFmtBytes(b) {
        if (b < 1024) return b + ' B';
        if (b < 1048576) return Math.round(b / 1024) + ' KB';
        return (b / 1048576).toFixed(1) + ' MB';
    }

    function gRenderQueue() {
        if (!gFileQueue) return;
        gFileQueue.innerHTML = '';

        const count = gQueue.length;
        gFileQueue.classList.toggle('hidden', count === 0);
        if (gSubmitBtn) {
            gSubmitBtn.classList.toggle('hidden', count === 0);
            gSubmitBtn.innerHTML = count === 0 ? 'Upload'
                : `<svg class="h-4 w-4 inline-block mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg> Upload ${count} File${count !== 1 ? 's' : ''}`;
        }
        if (count === 0) return;

        gQueue.forEach((item, i) => {
            const isImg   = item.file.type.startsWith('image/');
            const isVideo = item.file.type.startsWith('video/');
            const objUrl  = (isImg || isVideo) ? URL.createObjectURL(item.file) : null;

            let thumb;
            if (isImg) {
                thumb = `<img src="${objUrl}" data-obj-url="${objUrl}" class="w-10 h-10 rounded object-cover shrink-0" alt="">`;
            } else if (isVideo) {
                thumb = `<div class="w-10 h-10 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0 text-gray-500 dark:text-gray-300 text-lg">▶</div>`;
            } else {
                thumb = `<div class="w-10 h-10 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18"/>
                    </svg>
                </div>`;
            }

            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-600 dark:bg-gray-700/50';
            row.innerHTML = `
                ${thumb}
                <div class="min-w-0 w-36 lg:w-48 shrink-0">
                    <p class="text-xs font-medium text-gray-900 dark:text-white truncate" title="${item.file.name.replace(/"/g, '&quot;')}">${item.file.name}</p>
                    <p class="text-[11px] text-gray-400">${gFmtBytes(item.file.size)}</p>
                </div>
                <div class="flex-1 min-w-0">
                    <input type="text"
                           class="g-desc block w-full rounded-lg border border-gray-300 bg-white p-1.5 text-xs text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                           placeholder="Description (optional)"
                           value="${item.description.replace(/"/g, '&quot;')}"
                           data-index="${i}">
                </div>
                <button type="button"
                        class="g-remove shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full text-gray-400 hover:bg-red-100 hover:text-red-600 dark:hover:bg-red-900/40 dark:hover:text-red-400"
                        data-index="${i}" title="Remove">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>`;
            gFileQueue.appendChild(row);
        });

        gFileQueue.querySelectorAll('.g-desc').forEach(inp => {
            inp.addEventListener('input', () => { gQueue[inp.dataset.index].description = inp.value; });
        });
        gFileQueue.querySelectorAll('.g-remove').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index);
                if (gQueue[idx]?.file?.type?.startsWith('image/') || gQueue[idx]?.file?.type?.startsWith('video/')) {
                    const img = gFileQueue.querySelectorAll('[data-obj-url]')[idx];
                    if (img) URL.revokeObjectURL(img.dataset.objUrl);
                }
                gQueue.splice(idx, 1);
                gRenderQueue();
            });
        });
    }

    function gAddFiles(newFiles) {
        Array.from(newFiles).forEach(f => {
            if (!gQueue.some(item => item.file.name === f.name && item.file.size === f.size)) {
                gQueue.push({ file: f, description: '' });
            }
        });
        gRenderQueue();
        gUploadInput.value = '';
    }

    function gResetPanel() {
        gFileQueue?.querySelectorAll('[data-obj-url]').forEach(el => URL.revokeObjectURL(el.dataset.objUrl));
        gQueue = [];
        gRenderQueue();
        if (gProgressWrap) gProgressWrap.classList.add('hidden');
        if (gProgressBar) { gProgressBar.style.width = '0%'; gProgressBar.style.backgroundColor = ''; }
        gUploadInput.value = '';
    }

    // Toggle
    gUploadToggle?.addEventListener('click', () => gUploadPanel.classList.toggle('hidden'));
    gCancelBtn?.addEventListener('click', () => { gUploadPanel.classList.add('hidden'); gResetPanel(); });

    // Drop zone
    if (gDropZone && gUploadInput) {
        gDropZone.addEventListener('click', () => gUploadInput.click());
        gUploadInput.addEventListener('change', () => { if (gUploadInput.files.length) gAddFiles(gUploadInput.files); });
        ['dragenter', 'dragover'].forEach(evt => {
            gDropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); gDropZone.classList.add('ring-2', 'ring-blue-400'); });
        });
        ['dragleave', 'drop'].forEach(evt => {
            gDropZone.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); gDropZone.classList.remove('ring-2', 'ring-blue-400'); });
        });
        gDropZone.addEventListener('drop', e => {
            const files = e.dataTransfer?.files;
            if (files?.length) gAddFiles(files);
        });
    }

    // XHR upload
    gSubmitBtn?.addEventListener('click', () => {
        if (gQueue.length === 0 || gSubmitBtn.disabled) return;

        const fd = new FormData();
        fd.append('_token', gCsrfToken);
        gQueue.forEach(item => {
            fd.append('files[]', item.file);
            // Only send description — let server auto-assign Photos label for media
            if (item.description) fd.append('descriptions[]', item.description);
            else fd.append('descriptions[]', '');
        });

        if (gProgressWrap) gProgressWrap.classList.remove('hidden');
        gSubmitBtn.disabled = true;
        gSubmitBtn.innerHTML = '<svg class="h-4 w-4 animate-spin inline-block mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Uploading…';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', gUploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', gCsrfToken);

        xhr.upload.addEventListener('progress', e => {
            if (!e.lengthComputable) return;
            const pct = Math.round((e.loaded / e.total) * 100);
            if (gProgressBar) gProgressBar.style.width = pct + '%';
            if (gProgressPct) gProgressPct.textContent = pct + '%';
            if (gProgressLabel) gProgressLabel.textContent = `Uploading ${gQueue.length} file${gQueue.length !== 1 ? 's' : ''}…`;
        });

        xhr.addEventListener('load', () => {
            let data = null;
            try { data = JSON.parse(xhr.responseText); } catch {}

            if (data?.success) {
                if (gProgressBar) gProgressBar.style.width = '100%';
                if (gProgressPct) gProgressPct.textContent = '100%';
                if (gProgressLabel) gProgressLabel.textContent = `${data.count ?? gQueue.length} photo(s) uploaded!`;
                setTimeout(() => window.location.reload(), 1200);
            } else {
                let msg = data?.message || (data?.errors ? Object.values(data.errors).flat().join(' ') : null) || `Upload failed (HTTP ${xhr.status}).`;
                if (gProgressLabel) gProgressLabel.textContent = msg;
                if (gProgressBar) gProgressBar.style.backgroundColor = '#ef4444';
                gSubmitBtn.disabled = false;
            }
        });

        xhr.addEventListener('error', () => {
            if (gProgressLabel) gProgressLabel.textContent = 'Upload failed — network error.';
            if (gProgressBar) gProgressBar.style.backgroundColor = '#ef4444';
            gSubmitBtn.disabled = false;
        });

        xhr.send(fd);
    });
});
</script>

</x-app-layout>
