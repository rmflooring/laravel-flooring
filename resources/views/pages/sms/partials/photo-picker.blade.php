{{-- Attach-pictures control for the SMS portal's reply and compose forms (the form needs
     enctype="multipart/form-data"). Pictures go up as photos[]. --}}
<div x-data="smsPhotoPicker({{ \App\Services\VoipMsSmsService::MAX_MEDIA }})" class="contents">
    <input type="file" name="photos[]" multiple x-ref="files" class="hidden">
    <input type="file" accept="image/*" multiple x-ref="picker" class="hidden" @change="add($event)">

    <div x-show="photos.length" x-cloak class="mt-2 flex flex-wrap gap-2">
        <template x-for="(photo, i) in photos" :key="photo.url">
            <div class="relative">
                <img :src="photo.url" alt="Attached picture" class="h-16 w-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600">
                <button type="button" @click="remove(i)" title="Remove picture"
                        class="absolute -top-1.5 -right-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-gray-800 text-xs text-white hover:bg-red-600">&times;</button>
            </div>
        </template>
    </div>
    <p x-show="error" x-cloak class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="error"></p>

    <button type="button" @click="$refs.picker.click()" :disabled="photos.length >= max || busy"
            class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Zm10.5-11.25h.008v.008h-.008V9.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
        </svg>
        <span x-text="busy ? 'Preparing…' : (photos.length ? 'Add another picture' : 'Add pictures')"></span>
        <span class="text-gray-400" x-text="'(' + photos.length + '/' + max + ')'"></span>
    </button>
</div>

@once
    <script>
        function smsPhotoPicker(max) {
            return {
                photos: [], // { file, url }
                max,
                busy: false,
                error: '',

                async add(event) {
                    const files = [...event.target.files];
                    event.target.value = '';
                    this.error = '';
                    this.busy = true;

                    for (const original of files) {
                        if (this.photos.length >= this.max) {
                            this.error = `Up to ${this.max} pictures per message.`;
                            break;
                        }
                        const file = await this.shrink(original);
                        this.photos.push({ file, url: URL.createObjectURL(file) });
                    }

                    this.busy = false;
                    this.sync();
                },

                remove(i) {
                    URL.revokeObjectURL(this.photos[i].url);
                    this.photos.splice(i, 1);
                    this.error = '';
                    this.sync();
                },

                // The named file input is what actually submits, so mirror the list into it
                sync() {
                    const dt = new DataTransfer();
                    this.photos.forEach((p) => dt.items.add(p.file));
                    this.$refs.files.files = dt.files;
                },

                // Phone photos are often 3-5 MB; shrink them before upload so sending is
                // quick. The server re-encodes for MMS anyway. GIFs keep their animation.
                shrink(file) {
                    if (!file.type.startsWith('image/') || file.type === 'image/gif') {
                        return Promise.resolve(file);
                    }

                    return new Promise((resolve) => {
                        const img = new Image();
                        const src = URL.createObjectURL(file);
                        img.onload = () => {
                            URL.revokeObjectURL(src);
                            const scale = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(img.naturalWidth * scale);
                            canvas.height = Math.round(img.naturalHeight * scale);
                            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                            canvas.toBlob((blob) => resolve(blob
                                ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })
                                : file), 'image/jpeg', 0.85);
                        };
                        // A format the browser can't draw (e.g. HEIC on desktop) goes up as-is
                        img.onerror = () => { URL.revokeObjectURL(src); resolve(file); };
                        img.src = src;
                    });
                },
            };
        }
    </script>
@endonce
