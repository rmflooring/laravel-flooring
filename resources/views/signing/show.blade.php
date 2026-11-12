@php
    $docLabel = $signingRequest->document_type === 'flooring_selection' ? 'Flooring Selection' : 'Work Authorization';
    $docUrl   = route('sign.document', $signingRequest->uuid);
@endphp
<x-signing-layout :title="$docLabel">

    {{--
        DocuSign-style signing flow:
          1. The PDF is rendered into the page itself with PDF.js (no iframe — iOS Chrome
             renders embedded PDFs so they swallow the rest of the page).
          2. A sticky bottom bar with a "Sign" button is always visible.
          3. "Sign" opens a bottom sheet: typed signature pre-filled with the client's
             name (or draw), one agreement checkbox, one "Finish" button.
        Server side is unchanged: POSTs signature_data / signature_type / agreed to sign.submit.
    --}}

    <div x-data="signingApp()" x-init="init()" class="pb-32">

        {{-- Header --}}
        <div class="mb-5 max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-900 mb-1">{{ $docLabel }}</h1>
            <p class="text-sm text-gray-600">
                Prepared for <strong>{{ $signingRequest->client_name }}</strong>.
                Please review the document below, then tap <strong>Sign</strong>.
            </p>
        </div>

        {{-- Document --}}
        <div class="max-w-3xl mx-auto">
            <div x-show="loading" class="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500">
                Loading document…
            </div>

            <div x-show="loadFailed" x-cloak class="rounded-xl border border-gray-200 bg-white p-6 text-center">
                <p class="text-sm text-gray-700 mb-4">We couldn't display the document on this page.</p>
                <a href="{{ $docUrl }}" target="_blank" rel="noopener"
                   class="inline-block rounded-lg bg-blue-50 border border-blue-200 px-5 py-3 text-sm font-semibold text-blue-700">
                    Open the document (PDF) ↗
                </a>
                <p class="mt-3 text-xs text-gray-500">When you're done reviewing, come back here and tap Sign.</p>
            </div>

            <div id="pdf-pages" class="space-y-4"></div>

            <p x-show="pageCount" x-cloak class="mt-4 text-center text-xs text-gray-400">
                End of document &middot; <span x-text="pageCount"></span> page<span x-show="pageCount !== 1">s</span>
                &middot; <a href="{{ $docUrl }}" target="_blank" rel="noopener" class="underline">Download PDF</a>
            </p>
        </div>

        {{-- Sticky bottom bar --}}
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white/95 backdrop-blur"
             style="padding-bottom: env(safe-area-inset-bottom);">
            <div class="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
                <p class="flex-1 text-sm text-gray-600 leading-snug">
                    Review the document, then sign.
                    <span class="block text-xs text-gray-400">Link expires {{ $signingRequest->expires_at->timezone('America/Vancouver')->format('M j, Y') }}</span>
                </p>
                <button type="button" @click="openSheet()"
                        class="shrink-0 rounded-lg bg-blue-700 px-8 py-3 text-base font-semibold text-white shadow hover:bg-blue-800 active:bg-blue-900">
                    Sign
                </button>
            </div>
        </div>

        {{-- Sign sheet --}}
        <div x-show="sheetOpen" x-cloak class="fixed inset-0 z-50" @keydown.escape.window="closeSheet()">
            <div class="absolute inset-0 bg-black/50" @click="closeSheet()"
                 x-show="sheetOpen" x-transition.opacity></div>

            <div class="absolute inset-x-0 bottom-0 sm:inset-0 sm:flex sm:items-center sm:justify-center sm:p-4 pointer-events-none">
                <div class="pointer-events-auto bg-white w-full sm:max-w-lg rounded-t-2xl sm:rounded-2xl shadow-xl max-h-[92vh] overflow-y-auto"
                     style="padding-bottom: env(safe-area-inset-bottom);"
                     x-show="sheetOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="translate-y-full sm:translate-y-0 sm:opacity-0"
                     x-transition:enter-end="translate-y-0 sm:opacity-100">

                    <div class="flex items-center justify-between px-5 pt-5 pb-3">
                        <h2 class="text-lg font-semibold text-gray-900">Sign {{ $docLabel }}</h2>
                        <button type="button" @click="closeSheet()" class="p-1 text-gray-400 hover:text-gray-600" aria-label="Close">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('sign.submit', $signingRequest->uuid) }}"
                          @submit.prevent="submitForm($event)" class="px-5 pb-5">
                        @csrf
                        <input type="hidden" id="signature_data" name="signature_data">
                        <input type="hidden" id="signature_type" name="signature_type">

                        {{-- Type / Draw --}}
                        <div class="flex gap-1 mb-4 rounded-lg bg-gray-100 p-1">
                            <button type="button" @click="switchTab('type')"
                                    :class="tab === 'type' ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                                    class="flex-1 rounded-md py-2 text-sm font-medium">Type</button>
                            <button type="button" @click="switchTab('draw')"
                                    :class="tab === 'draw' ? 'bg-white shadow text-gray-900' : 'text-gray-500'"
                                    class="flex-1 rounded-md py-2 text-sm font-medium">Draw</button>
                        </div>

                        <div x-show="tab === 'type'">
                            <label class="block text-xs font-medium text-gray-500 mb-1" for="typed-name">Full name</label>
                            <input id="typed-name" type="text" x-model="typedName" autocomplete="name"
                                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-base focus:border-blue-500 focus:ring-blue-500 mb-3">
                            <div class="rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden px-3" style="height: 96px;">
                                <span class="text-gray-800 whitespace-nowrap" style="font-family: 'Dancing Script', cursive; font-size: 2.5rem;"
                                      x-text="typedName.trim() || 'Your signature'"></span>
                            </div>
                        </div>

                        <div x-show="tab === 'draw'" x-cloak>
                            <div class="rounded-lg border-2 border-dashed border-gray-300 bg-gray-50" style="height: 160px;">
                                <canvas id="draw-canvas" class="w-full h-full rounded-lg" style="touch-action: none;"></canvas>
                            </div>
                            <div class="mt-1 flex justify-between text-xs text-gray-500">
                                <span>Sign with your finger or mouse</span>
                                <button type="button" @click="clearDraw()" class="underline hover:text-red-600">Clear</button>
                            </div>
                        </div>
                        <canvas id="type-canvas" class="hidden"></canvas>

                        <label class="mt-5 flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="agreed" value="1" x-model="agreed"
                                   class="mt-0.5 h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">
                                I have reviewed this document and agree to sign it electronically. I understand this electronic signature is legally binding.
                            </span>
                        </label>

                        @if ($errors->any())
                            <div class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
                        @endif
                        <div x-show="errorMsg" x-cloak class="mt-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700" x-text="errorMsg"></div>

                        <button type="submit" :disabled="!agreed || submitting"
                                class="mt-5 w-full rounded-lg bg-blue-700 px-6 py-3.5 text-base font-semibold text-white hover:bg-blue-800 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-text="submitting ? 'Signing…' : 'Finish'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
    function signingApp() {
        return {
            tab: 'type',
            typedName: @json($signingRequest->client_name),
            agreed: false,
            errorMsg: '',
            submitting: false,
            sheetOpen: @json($errors->any()),
            signaturePad: null,
            loading: true,
            loadFailed: false,
            pageCount: 0,

            init() {
                this.renderPdf();
            },

            async renderPdf() {
                try {
                    if (!window.pdfjsLib) throw new Error('PDF.js not loaded');
                    pdfjsLib.GlobalWorkerOptions.workerSrc =
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

                    const pdf       = await pdfjsLib.getDocument(@json($docUrl)).promise;
                    const container = document.getElementById('pdf-pages');
                    const width     = container.clientWidth;
                    const ratio     = Math.min(window.devicePixelRatio || 1, 3);

                    for (let n = 1; n <= pdf.numPages; n++) {
                        const page     = await pdf.getPage(n);
                        const base     = page.getViewport({ scale: 1 });
                        const viewport = page.getViewport({ scale: (width / base.width) * ratio });

                        const canvas  = document.createElement('canvas');
                        canvas.width  = Math.floor(viewport.width);
                        canvas.height = Math.floor(viewport.height);
                        canvas.style.width   = '100%';
                        canvas.style.display = 'block';
                        canvas.className     = 'rounded-lg border border-gray-200 bg-white shadow-sm';
                        container.appendChild(canvas);

                        await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                        if (n === 1) this.loading = false;
                    }
                    this.pageCount = pdf.numPages;
                } catch (e) {
                    console.error('[signing] PDF render failed', e);
                    this.loadFailed = true;
                } finally {
                    this.loading = false;
                }
            },

            openSheet() {
                this.sheetOpen = true;
                this.errorMsg  = '';
                document.body.style.overflow = 'hidden';
                if (this.tab === 'draw') this.$nextTick(() => this.initPad());
            },

            closeSheet() {
                if (this.submitting) return;
                this.sheetOpen = false;
                document.body.style.overflow = '';
            },

            switchTab(tab) {
                this.tab      = tab;
                this.errorMsg = '';
                // Init once, after the canvas is visible (it has no size while hidden).
                // Re-initialising would wipe a signature already drawn.
                if (tab === 'draw') this.$nextTick(() => this.initPad());
            },

            initPad() {
                if (this.signaturePad) return;
                const canvas = document.getElementById('draw-canvas');
                if (!canvas || !canvas.offsetWidth) return;
                const ratio   = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width  = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(249,250,251)' });
            },

            clearDraw() {
                if (this.signaturePad) this.signaturePad.clear();
            },

            async getSignatureData() {
                if (this.tab === 'draw') {
                    if (!this.signaturePad || this.signaturePad.isEmpty()) return null;
                    return { data: this.signaturePad.toDataURL('image/png'), type: 'drawn' };
                }
                const name = this.typedName.trim();
                if (!name) return null;

                // Make sure the script font is loaded before drawing it onto the canvas.
                try { await document.fonts.load('64px "Dancing Script"'); } catch (e) {}

                const canvas  = document.getElementById('type-canvas');
                canvas.width  = 600;
                canvas.height = 120;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = 'rgb(249,250,251)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                let size = 64;
                ctx.font = size + 'px "Dancing Script"';
                while (ctx.measureText(name).width > canvas.width - 40 && size > 24) {
                    size -= 4;
                    ctx.font = size + 'px "Dancing Script"';
                }
                ctx.fillStyle    = '#1a1a1a';
                ctx.textBaseline = 'middle';
                ctx.fillText(name, 20, 65);
                return { data: canvas.toDataURL('image/png'), type: 'typed' };
            },

            async submitForm(event) {
                if (this.submitting) return;
                this.errorMsg = '';
                const sig = await this.getSignatureData();
                if (!sig) {
                    this.errorMsg = this.tab === 'draw'
                        ? 'Please draw your signature in the box.'
                        : 'Please type your full name.';
                    return;
                }
                document.getElementById('signature_data').value = sig.data;
                document.getElementById('signature_type').value = sig.type;
                this.submitting = true;
                event.target.submit();
            },
        }
    }
    </script>

</x-signing-layout>
