<x-signing-layout :title="$signingRequest->document_type === 'flooring_selection' ? 'Flooring Selection' : 'Work Authorization'">

    <div x-data="signingApp()" x-init="init()">

        <h1 class="text-2xl font-bold text-gray-900 mb-1">
            {{ $signingRequest->document_type === 'flooring_selection' ? 'Flooring Selection' : 'Work Authorization' }}
        </h1>
        <p class="text-sm text-gray-500 mb-6">
            Prepared for <strong>{{ $signingRequest->client_name }}</strong> &mdash;
            please review the document below, then add your signature.
        </p>

        {{-- PDF viewer --}}
        <div class="rounded-xl border border-gray-300 overflow-hidden mb-8 bg-white">
            <iframe src="{{ route('sign.document', $signingRequest->uuid) }}"
                    class="w-full"
                    style="height: 520px;"
                    title="Document to sign">
                <p class="p-4 text-sm text-gray-500">
                    Your browser cannot display the PDF inline.
                    <a href="{{ route('sign.document', $signingRequest->uuid) }}" target="_blank" class="text-blue-600 underline">Open PDF</a>
                </p>
            </iframe>
        </div>

        {{-- Signature capture --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Your Signature</h2>

            {{-- Tabs --}}
            <div class="flex gap-1 mb-5 border-b border-gray-200">
                <button type="button"
                        @click="switchTab('draw')"
                        :class="tab === 'draw' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 text-sm font-medium -mb-px">
                    Draw
                </button>
                <button type="button"
                        @click="switchTab('type')"
                        :class="tab === 'type' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                        class="px-4 py-2 text-sm font-medium -mb-px">
                    Type
                </button>
            </div>

            {{-- Draw tab --}}
            <div x-show="tab === 'draw'" x-cloak>
                <p class="text-xs text-gray-500 mb-2">Draw your signature in the box below.</p>
                <div class="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 relative" style="height: 140px;">
                    <canvas id="draw-canvas" class="w-full h-full rounded-lg cursor-crosshair"></canvas>
                </div>
                <button type="button" @click="clearDraw()"
                        class="mt-2 text-xs text-gray-500 hover:text-red-600 underline">
                    Clear
                </button>
            </div>

            {{-- Type tab --}}
            <div x-show="tab === 'type'" x-cloak>
                <p class="text-xs text-gray-500 mb-2">Type your name — it will be rendered as your signature.</p>
                <input type="text"
                       x-model="typedName"
                       placeholder="Your full name"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 mb-3">
                <div class="border border-gray-200 rounded-lg bg-gray-50 flex items-center justify-center" style="height: 80px;">
                    <span class="text-4xl text-gray-800" style="font-family: 'Dancing Script', cursive;" x-text="typedName || 'Preview'"></span>
                </div>
                <canvas id="type-canvas" class="hidden"></canvas>
            </div>
        </div>

        {{-- Agreement + submit --}}
        <form method="POST" action="{{ route('sign.submit', $signingRequest->uuid) }}"
              @submit.prevent="submitForm($event)">
            @csrf
            <input type="hidden" id="signature_data" name="signature_data">
            <input type="hidden" id="signature_type" name="signature_type">

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <label class="flex items-start gap-3 mb-6 cursor-pointer">
                <input type="checkbox" name="agreed" value="1"
                       class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                       x-model="agreed">
                <span class="text-sm text-gray-700">
                    I agree to sign this document electronically. I understand this electronic signature is legally binding.
                </span>
            </label>

            <div x-show="errorMsg" x-cloak
                 class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700"
                 x-text="errorMsg"></div>

            <button type="submit"
                    :disabled="!agreed"
                    class="w-full rounded-lg bg-blue-700 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed">
                Submit Signature
            </button>
        </form>

        <p class="mt-4 text-xs text-center text-gray-400">
            This link expires {{ $signingRequest->expires_at->timezone('America/Vancouver')->format('F j, Y') }}.
            If you have questions, contact RM Flooring &amp; Cabinetry.
        </p>
    </div>

    <script>
    function signingApp() {
        return {
            tab: 'draw',
            typedName: '',
            agreed: false,
            errorMsg: '',
            signaturePad: null,

            init() {
                this.$nextTick(() => this.initPad());
            },

            initPad() {
                const canvas = document.getElementById('draw-canvas');
                if (!canvas) return;
                const ratio  = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width  = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                this.signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(249,250,251)' });
            },

            switchTab(tab) {
                this.tab      = tab;
                this.errorMsg = '';
                if (tab === 'draw') {
                    this.$nextTick(() => this.initPad());
                }
            },

            clearDraw() {
                if (this.signaturePad) this.signaturePad.clear();
            },

            getSignatureData() {
                if (this.tab === 'draw') {
                    if (!this.signaturePad || this.signaturePad.isEmpty()) return null;
                    return { data: this.signaturePad.toDataURL('image/png'), type: 'drawn' };
                }
                const name = this.typedName.trim();
                if (!name) return null;
                const canvas  = document.getElementById('type-canvas');
                canvas.width  = 600;
                canvas.height = 120;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = 'rgb(249,250,251)';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle    = '#1a1a1a';
                ctx.font         = '64px "Dancing Script"';
                ctx.textBaseline = 'middle';
                ctx.fillText(name, 20, 65);
                return { data: canvas.toDataURL('image/png'), type: 'typed' };
            },

            submitForm(event) {
                this.errorMsg = '';
                const sig = this.getSignatureData();
                if (!sig) {
                    this.errorMsg = this.tab === 'draw'
                        ? 'Please draw your signature before submitting.'
                        : 'Please type your name before submitting.';
                    return;
                }
                document.getElementById('signature_data').value = sig.data;
                document.getElementById('signature_type').value = sig.type;
                event.target.submit();
            }
        }
    }
    </script>

</x-signing-layout>
