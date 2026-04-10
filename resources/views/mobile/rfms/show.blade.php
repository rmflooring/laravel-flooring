{{-- resources/views/mobile/rfms/show.blade.php --}}
<x-mobile-layout :title="'RFM – ' . ($rfm->jobSiteCustomer?->company_name ?: ($rfm->jobSiteCustomer?->name ?: ($rfm->parentCustomer?->company_name ?: ($rfm->parentCustomer?->name ?: 'Measure'))))">

    @php
        $statusColors = [
            'pending'   => 'bg-amber-100 text-amber-800',
            'confirmed' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
        ];
        $statusColor = $statusColors[$rfm->status] ?? 'bg-gray-100 text-gray-800';

        $hasAddr = $rfm->site_address || $rfm->site_address2 || $rfm->site_city || $rfm->site_postal_code;
        $fullAddress = collect([
            $rfm->site_address,
            $rfm->site_address2,
            $rfm->site_city,
            $rfm->site_postal_code,
        ])->filter()->implode(', ');

        $customerName = $rfm->parentCustomer?->company_name
            ?: ($rfm->parentCustomer?->name ?: null);

        $jobSiteName = $rfm->jobSiteCustomer?->company_name
            ?: ($rfm->jobSiteCustomer?->name ?: null);
    @endphp

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800 px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif
    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 px-4 py-3 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <span class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.closest('div').remove()" class="text-red-600 dark:text-red-400 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Back to Opportunity --}}
    <a href="{{ route('mobile.opportunity.show', $opportunity->id) }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
        </svg>
        Back to Opportunity
    </a>

    {{-- RFM Identity card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-0.5">Request for Measure</p>
                @if($customerName)
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $customerName }}</h1>
                @endif
                @if($jobSiteName && $jobSiteName !== $customerName)
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $jobSiteName }}</p>
                @endif
                @if($opportunity->job_name)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $opportunity->job_name }}</p>
                @endif
            </div>
            <span class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusColor }}">
                {{ $rfm->status_label }}
            </span>
        </div>

        {{-- Scheduled date --}}
        <div class="mt-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-1">Scheduled</p>
            <p class="text-base font-bold text-blue-900 dark:text-blue-100">
                {{ $rfm->scheduled_at->format('l, F j, Y') }}
            </p>
            <p class="text-sm text-blue-700 dark:text-blue-300 mt-0.5">
                at {{ $rfm->scheduled_at->format('g:i A') }}
            </p>
        </div>
    </div>

    {{-- Site address card --}}
    @if($fullAddress || $customerName || $jobSiteName)
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500 mb-3">Job Site</p>

        @if($jobSiteName ?? $customerName)
            <div class="flex items-start gap-3 mb-2">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                <span class="text-sm text-gray-800 dark:text-gray-200">{{ $jobSiteName ?? $customerName }}</span>
            </div>
        @endif

        @if($hasAddr)
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                </svg>
                <a href="https://maps.google.com/?q={{ urlencode($fullAddress) }}"
                   target="_blank"
                   class="text-sm text-blue-600 dark:text-blue-400 underline">
                    @if($rfm->site_address)<div>{{ $rfm->site_address }}</div>@endif
                    @if($rfm->site_address2)<div>{{ $rfm->site_address2 }}</div>@endif
                    @if($rfm->site_city || $rfm->site_province || $rfm->site_postal_code)
                        <div>{{ implode(', ', array_filter([$rfm->site_city, $rfm->site_province, $rfm->site_postal_code])) }}</div>
                    @endif
                </a>
            </div>
        @endif
    </div>
    @endif

    {{-- Measure details card --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 p-4 space-y-3">
        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-gray-500">Measure Details</p>

        {{-- Estimator --}}
        @if($rfm->estimator)
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Estimator</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $rfm->estimator->first_name }} {{ $rfm->estimator->last_name }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Flooring types --}}
        @if(!empty($rfm->flooring_type))
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">Flooring Types</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($rfm->flooring_type as $type)
                            <span class="inline-flex items-center rounded-full bg-indigo-50 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:text-indigo-300">
                                {{ $type }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- PM info --}}
        @if($opportunity->projectManager)
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/>
                </svg>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Project Manager</p>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $opportunity->projectManager->name }}</p>
                </div>
            </div>
        @endif
    </div>

    {{-- Special instructions --}}
    @if($rfm->special_instructions)
    <div class="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20 p-4 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400 mb-2">Special Instructions</p>
        <p class="text-sm text-amber-900 dark:text-amber-100 whitespace-pre-line">{{ $rfm->special_instructions }}</p>
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
                    <p class="text-base font-bold text-gray-900 dark:text-white">Add Measure Photos</p>
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

            {{-- Submit + progress area --}}
            <div id="photo-submit-area" style="display:none"
                 class="border-t border-gray-100 dark:border-gray-700 px-5 py-4 space-y-3">

                {{-- Count + upload button row --}}
                <div class="flex items-center justify-between gap-3">
                    <span id="photo-count-label" class="text-sm text-gray-600 dark:text-gray-300"></span>
                    <button type="button" id="photo-upload-btn"
                            onclick="startMobileUpload()"
                            class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 rounded-lg">
                        Upload
                    </button>
                </div>

                {{-- Progress bar (hidden until upload starts) --}}
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

    {{-- View Photos card --}}
    <a href="{{ route('mobile.opportunity.photos', $rfm->opportunity_id) }}"
       class="flex items-center gap-4 rounded-xl border border-indigo-200 bg-white dark:border-indigo-800 dark:bg-gray-800 px-5 py-4 shadow-sm active:scale-95 transition-transform">
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40">
            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-base font-bold text-gray-900 dark:text-white">View Job Photos</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Browse all photos for this job</p>
        </div>
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <script>
    var _uploadUrl  = '{{ route('mobile.rfms.upload-photos', $rfm) }}';
    var _csrfToken  = '{{ csrf_token() }}';

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
                setTimeout(function () { window.location.href = data.redirect; }, 800);
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
    </script>

</x-mobile-layout>
