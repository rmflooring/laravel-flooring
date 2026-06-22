<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $share->label ?? 'Shared Files' }} — RM Flooring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
    </style>
</head>
<body class="min-h-full bg-gray-50">

    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center gap-4">
            <div class="flex-shrink-0">
                <div class="h-10 w-10 rounded-lg bg-blue-700 flex items-center justify-center">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">RM Flooring</p>
                <h1 class="text-lg font-bold text-gray-900 leading-tight">
                    {{ $share->label ?? 'Shared Files' }}
                </h1>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 py-8">

        {{-- Meta info --}}
        <div class="mb-6 flex flex-wrap items-center gap-3 text-sm text-gray-500">
            <span>
                Shared by <strong class="text-gray-700">{{ $share->createdBy?->name ?? 'RM Flooring' }}</strong>
            </span>
            <span class="text-gray-300">·</span>
            <span>{{ $share->documents->count() }} file{{ $share->documents->count() !== 1 ? 's' : '' }}</span>
            @if ($share->expires_at)
                <span class="text-gray-300">·</span>
                <span>Expires {{ $share->expires_at->format('M j, Y') }}</span>
            @endif
        </div>

        @if ($share->documents->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-12 text-center">
                <p class="text-gray-500">No files in this share.</p>
            </div>
        @else
            {{-- Image grid --}}
            @php
                $images = $share->documents->filter(fn($d) => str_starts_with($d->mime_type ?? '', 'image/'));
                $others = $share->documents->filter(fn($d) => !str_starts_with($d->mime_type ?? '', 'image/'));
            @endphp

            @if ($images->isNotEmpty())
                <div class="mb-6">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Photos</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        @foreach ($images as $doc)
                            <div class="group relative rounded-xl overflow-hidden border border-gray-200 bg-white shadow-sm aspect-square">
                                <img src="{{ $doc->url }}"
                                     alt="{{ $doc->original_name }}"
                                     loading="lazy"
                                     class="w-full h-full object-cover cursor-pointer"
                                     onclick="openLightbox('{{ addslashes($doc->url) }}', '{{ addslashes($doc->original_name) }}')">
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-2 py-2 translate-y-full group-hover:translate-y-0 transition-transform">
                                    <p class="text-white text-xs font-medium truncate">{{ $doc->original_name }}</p>
                                </div>
                                <a href="{{ $doc->url }}"
                                   download="{{ $doc->original_name }}"
                                   class="absolute top-2 right-2 inline-flex items-center justify-center h-7 w-7 rounded-lg bg-white/90 text-gray-700 hover:bg-white shadow opacity-0 group-hover:opacity-100 transition-opacity"
                                   title="Download">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($others->isNotEmpty())
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">Documents</h2>
                    <div class="space-y-2">
                        @foreach ($others as $doc)
                            @php
                                $isVideo = str_starts_with($doc->mime_type ?? '', 'video/');
                                $isPdf   = $doc->extension === 'pdf' || $doc->mime_type === 'application/pdf';
                            @endphp
                            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm">
                                <div class="flex-shrink-0 h-9 w-9 rounded-lg flex items-center justify-center {{ $isPdf ? 'bg-red-50' : ($isVideo ? 'bg-purple-50' : 'bg-gray-50') }}">
                                    @if ($isPdf)
                                        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V5a2 2 0 00-2-2zm-9.5 8.5h1v2h-1v-2zm1.5-1.5H9.5V8.5H11a1.5 1.5 0 010 3zm4.5 4h-1.5V9H15v5z"/>
                                        </svg>
                                    @elseif ($isVideo)
                                        <svg class="h-5 w-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"/>
                                        </svg>
                                    @else
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->original_name }}</p>
                                    @if ($doc->description)
                                        <p class="text-xs text-gray-500 truncate">{{ $doc->description }}</p>
                                    @endif
                                </div>
                                <a href="{{ $doc->url }}"
                                   target="_blank"
                                   class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        {{-- Footer --}}
        <div class="mt-10 pt-6 border-t border-gray-200 text-center text-xs text-gray-400">
            Shared by RM Flooring &middot; <a href="https://rmflooring.ca" class="hover:underline">rmflooring.ca</a>
        </div>
    </div>

    {{-- Lightbox --}}
    <div id="lightbox" class="hidden fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4"
         onclick="closeLightbox()">
        <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white/80 hover:text-white">
            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <img id="lightboxImg" src="" alt="" class="max-h-full max-w-full object-contain rounded-lg" onclick="event.stopPropagation()">
        <div class="absolute bottom-4 inset-x-4 text-center">
            <p id="lightboxCaption" class="text-white text-sm font-medium drop-shadow"></p>
            <a id="lightboxDownload" href="" download
               class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-white/20 hover:bg-white/30 px-3 py-1.5 text-xs font-medium text-white backdrop-blur"
               onclick="event.stopPropagation()">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Download
            </a>
        </div>
    </div>

    <script>
    function openLightbox(url, name) {
        document.getElementById('lightboxImg').src = url;
        document.getElementById('lightboxCaption').textContent = name;
        document.getElementById('lightboxDownload').href = url;
        document.getElementById('lightboxDownload').download = name;
        document.getElementById('lightbox').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        document.getElementById('lightbox').classList.add('hidden');
        document.getElementById('lightboxImg').src = '';
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
    </script>
</body>
</html>
