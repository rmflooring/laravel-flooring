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

    {{-- Add Photos card --}}
    <div class="rounded-xl border border-emerald-200 bg-white dark:border-emerald-800 dark:bg-gray-800 shadow-sm overflow-hidden">
        <form id="photo-upload-form" enctype="multipart/form-data">
            @csrf
            <button type="button" id="photo-pick-btn"
                    onclick="document.getElementById('photo-file-input').click()"
                    class="w-full flex items-center gap-4 px-5 py-4 active:scale-95 transition-transform text-left">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-base font-bold text-gray-900 dark:text-white">Add Photos</p>
                    <p id="photo-upload-label" class="text-xs text-gray-500 dark:text-gray-400">Tap to take or choose photos</p>
                </div>
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <input type="file" id="photo-file-input" name="files[]"
                   multiple accept="image/*"
                   class="hidden"
                   onchange="handlePhotoSelection(this)">

            <div id="photo-submit-area" style="display:none"
                 class="border-t border-gray-100 dark:border-gray-700 px-5 py-4 space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <span id="photo-count-label" class="text-sm text-gray-600 dark:text-gray-300"></span>
                    <button type="button" id="photo-upload-btn"
                            onclick="startMobileUpload()"
                            class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 rounded-lg">
                        Upload
                    </button>
                </div>
                <div id="photo-progress-wrap" style="display:none">
                    <div class="mb-1.5 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span id="photo-progress-label">Uploading…</span>
                        <span id="photo-progress-pct">0%</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div id="photo-progress-bar"
                             class="h-3 rounded-full bg-emerald-500 transition-all duration-150"
                             style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

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
    var _uploadUrl = '{{ route('mobile.opportunity.photos.upload', $opportunity) }}';
    var _csrfToken = '{{ csrf_token() }}';

    function handlePhotoSelection(input) {
        var count = input.files.length;
        if (count === 0) return;
        var label = count + ' photo' + (count !== 1 ? 's' : '') + ' selected';
        document.getElementById('photo-count-label').textContent = label;
        document.getElementById('photo-upload-label').textContent = label + ' — ready to upload';
        document.getElementById('photo-submit-area').style.display = 'block';
    }

    function startMobileUpload() {
        var input = document.getElementById('photo-file-input');
        if (!input.files.length) return;

        var fd = new FormData();
        fd.append('_token', _csrfToken);
        for (var i = 0; i < input.files.length; i++) {
            fd.append('files[]', input.files[i]);
        }

        var progressWrap  = document.getElementById('photo-progress-wrap');
        var progressBar   = document.getElementById('photo-progress-bar');
        var progressPct   = document.getElementById('photo-progress-pct');
        var progressLabel = document.getElementById('photo-progress-label');
        var uploadBtn     = document.getElementById('photo-upload-btn');
        var pickBtn       = document.getElementById('photo-pick-btn');

        progressWrap.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading…';
        pickBtn.disabled = true;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', _uploadUrl);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-TOKEN', _csrfToken);

        xhr.upload.addEventListener('progress', function (e) {
            if (!e.lengthComputable) return;
            var pct = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = pct + '%';
            progressPct.textContent = pct + '%';
            progressLabel.textContent = 'Uploading ' + input.files.length + ' photo' + (input.files.length !== 1 ? 's' : '') + '…';
        });

        xhr.addEventListener('load', function () {
            var data = null;
            try { data = JSON.parse(xhr.responseText); } catch (e) {}

            if (data && data.success) {
                progressBar.style.width = '100%';
                progressPct.textContent = '100%';
                progressLabel.textContent = (data.count || input.files.length) + ' photo' + ((data.count || input.files.length) !== 1 ? 's' : '') + ' uploaded!';
                setTimeout(function () { window.location.reload(); }, 800);
            } else {
                var msg = (data && data.message) || 'Upload failed (HTTP ' + xhr.status + ').';
                progressLabel.textContent = msg;
                progressBar.style.backgroundColor = '#ef4444';
                uploadBtn.disabled = false;
                uploadBtn.textContent = 'Retry';
                pickBtn.disabled = false;
            }
        });

        xhr.addEventListener('error', function () {
            progressLabel.textContent = 'Upload failed — network error.';
            progressBar.style.backgroundColor = '#ef4444';
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Retry';
            pickBtn.disabled = false;
        });

        xhr.send(fd);
    }

    window.addEventListener('pageshow', function () {
        var input = document.getElementById('photo-file-input');
        if (input) input.value = '';
        document.getElementById('photo-submit-area').style.display = 'none';
        document.getElementById('photo-progress-wrap').style.display = 'none';
        var bar = document.getElementById('photo-progress-bar');
        if (bar) { bar.style.width = '0%'; bar.style.backgroundColor = ''; }
        document.getElementById('photo-upload-label').textContent = 'Tap to take or choose photos';
        var btn = document.getElementById('photo-upload-btn');
        if (btn) { btn.disabled = false; btn.textContent = 'Upload'; }
        var pick = document.getElementById('photo-pick-btn');
        if (pick) pick.disabled = false;
    });

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
