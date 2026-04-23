{{-- resources/views/pages/purchase-orders/_documents.blade.php --}}
{{-- Usage: @include('pages.purchase-orders._documents', ['purchaseOrder' => $purchaseOrder]) --}}
<div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
     x-data="{ dragging: false }"
     @dragover.prevent="dragging = true"
     @dragleave="if (!$el.contains($event.relatedTarget)) dragging = false"
     @drop.prevent="
         dragging = false;
         const input = document.getElementById('po-doc-upload-{{ $purchaseOrder->id }}');
         const form  = document.getElementById('po-doc-upload-form-{{ $purchaseOrder->id }}');
         if ($event.dataTransfer.files.length && input && form) {
             const dt = new DataTransfer();
             Array.from($event.dataTransfer.files).forEach(f => dt.items.add(f));
             input.files = dt.files;
             form.submit();
         }
     ">

    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                Order Confirmations
            </h2>
            @if ($purchaseOrder->documents->count() > 0)
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ $purchaseOrder->documents->count() }}
                </span>
            @endif
        </div>

        @can('edit purchase orders')
        <label for="po-doc-upload-{{ $purchaseOrder->id }}"
               class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            Upload
        </label>
        <form id="po-doc-upload-form-{{ $purchaseOrder->id }}"
              method="POST"
              action="{{ route('pages.purchase-orders.documents.store', $purchaseOrder) }}"
              enctype="multipart/form-data"
              class="hidden">
            @csrf
            <input type="file"
                   id="po-doc-upload-{{ $purchaseOrder->id }}"
                   name="files[]"
                   multiple
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"
                   class="hidden"
                   onchange="document.getElementById('po-doc-upload-form-{{ $purchaseOrder->id }}').submit()">
        </form>
        @endcan
    </div>

    {{-- Drop zone (visible only when dragging) --}}
    @can('edit purchase orders')
    <div x-show="dragging"
         x-cloak
         class="m-4 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-blue-400 bg-blue-50 px-6 py-10 dark:border-blue-600 dark:bg-blue-900/20">
        <svg class="mb-2 h-8 w-8 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16v-8m0 0l-3 3m3-3l3 3M20.25 14.15v3.075c0 1.035-.84 1.875-1.875 1.875h-12.75c-1.035 0-1.875-.84-1.875-1.875V14.15"/>
        </svg>
        <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Drop files to upload</p>
    </div>
    @endcan

    {{-- Document list --}}
    <div class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse ($purchaseOrder->documents as $doc)
            <div class="flex items-center gap-3 px-6 py-3">

                {{-- Icon based on extension --}}
                @php
                    $iconColor = match(strtolower($doc->extension)) {
                        'pdf'        => 'text-red-500',
                        'jpg','jpeg','png','webp' => 'text-blue-500',
                        'doc','docx' => 'text-blue-700',
                        'xls','xlsx' => 'text-green-600',
                        default      => 'text-gray-400',
                    };
                @endphp
                <svg class="w-8 h-8 shrink-0 {{ $iconColor }}" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>

                {{-- Name + meta --}}
                <div class="min-w-0 flex-1">
                    <a href="{{ route('pages.purchase-orders.documents.download', [$purchaseOrder, $doc]) }}"
                       target="_blank"
                       class="block truncate text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                        {{ $doc->original_name }}
                    </a>
                    <p class="text-xs text-gray-400 dark:text-gray-500">
                        {{ strtoupper($doc->extension) }}
                        @if ($doc->size_bytes)
                            &middot; {{ number_format($doc->size_bytes / 1024, 0) }} KB
                        @endif
                        &middot; {{ $doc->created_at->format('M j, Y') }}
                        @if ($doc->uploader)
                            &middot; {{ $doc->uploader->name }}
                        @endif
                    </p>
                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('pages.purchase-orders.documents.download', [$purchaseOrder, $doc]) }}"
                       target="_blank"
                       class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Open
                    </a>
                    @can('edit purchase orders')
                    <form method="POST"
                          action="{{ route('pages.purchase-orders.documents.destroy', [$purchaseOrder, $doc]) }}"
                          onsubmit="return confirm('Remove this document?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center rounded border border-red-200 bg-white px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:bg-gray-700 dark:text-red-400 dark:hover:bg-gray-600">
                            Remove
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
        @empty
            <div class="px-6 py-8 text-center">
                <svg class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
                <p class="text-sm text-gray-400 dark:text-gray-500">No order confirmations uploaded yet.</p>
                @can('edit purchase orders')
                <label for="po-doc-upload-{{ $purchaseOrder->id }}"
                       class="mt-2 inline-flex cursor-pointer items-center gap-1.5 text-sm text-blue-600 hover:underline dark:text-blue-400">
                    Upload a document
                </label>
                @endcan
            </div>
        @endforelse
    </div>

</div>

