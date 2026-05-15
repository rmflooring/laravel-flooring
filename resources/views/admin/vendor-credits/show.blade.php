<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ route('admin.vendor-credits.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Vendor Credits</a>
                <span>/</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $vendorCredit->credit_memo_number }}</span>
            </nav>

            @if (session('success'))
                <div class="flex items-center rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-900 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="flex items-center rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-gray-800 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $statusBadge = match($vendorCredit->status) {
                    'open'   => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'voided' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                    default  => 'bg-gray-100 text-gray-600',
                };
            @endphp

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                {{-- Main --}}
                <div class="lg:col-span-2 space-y-4">

                    {{-- Header card --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <h1 class="text-lg font-bold text-gray-900 dark:text-white font-mono">{{ $vendorCredit->credit_memo_number }}</h1>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge }}">
                                    {{ $vendorCredit->status_label }}
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($vendorCredit->status !== 'voided')
                                    @can('edit vendor credits')
                                        <a href="{{ route('admin.vendor-credits.edit', $vendorCredit) }}"
                                           class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                            Edit
                                        </a>
                                    @endcan
                                    @can('create vendor credits')
                                        <button type="button"
                                                onclick="document.getElementById('void-modal').classList.remove('hidden')"
                                                class="inline-flex items-center rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-700 dark:text-red-400">
                                            Void
                                        </button>
                                    @endcan
                                @endif
                                @if (app(\App\Services\QuickBooksService::class)->isConnected() && $vendorCredit->status !== 'voided')
                                    <form method="POST" action="{{ route('admin.vendor-credits.push-to-qbo', $vendorCredit) }}">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition
                                                    {{ $vendorCredit->qbo_id
                                                        ? 'border-green-300 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-400'
                                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            {{ $vendorCredit->qbo_id ? 'Re-sync to QBO' : 'Push to QBO' }}
                                        </button>
                                    </form>
                                @endif
                                @can('delete vendor credits')
                                    <form method="POST" action="{{ route('admin.vendor-credits.destroy', $vendorCredit) }}"
                                          onsubmit="return confirm('Delete this credit memo? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                                            Delete
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        {{-- Financial breakdown --}}
                        <div class="p-6 space-y-3">
                            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="text-gray-500">Subtotal</dt>
                                    <dd class="font-medium text-gray-900 dark:text-white">${{ number_format($vendorCredit->subtotal, 2) }}</dd>
                                </div>
                                @if ($vendorCredit->gst_amount > 0)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="text-gray-500">
                                        GST
                                        @if (!$vendorCredit->tax_manual)
                                            <span class="text-xs text-gray-400">({{ number_format($vendorCredit->gst_rate * 100, 3) }}%)</span>
                                        @endif
                                    </dt>
                                    <dd class="text-gray-700 dark:text-gray-300">${{ number_format($vendorCredit->gst_amount, 2) }}</dd>
                                </div>
                                @endif
                                @if ($vendorCredit->pst_amount > 0)
                                <div class="flex justify-between py-2 text-sm">
                                    <dt class="text-gray-500">
                                        PST
                                        @if (!$vendorCredit->tax_manual)
                                            <span class="text-xs text-gray-400">({{ number_format($vendorCredit->pst_rate * 100, 3) }}%)</span>
                                        @endif
                                    </dt>
                                    <dd class="text-gray-700 dark:text-gray-300">${{ number_format($vendorCredit->pst_amount, 2) }}</dd>
                                </div>
                                @endif
                                <div class="flex justify-between py-3 text-base">
                                    <dt class="font-semibold text-green-700 dark:text-green-400">Total Credit</dt>
                                    <dd class="font-bold text-green-700 dark:text-green-400">−${{ number_format($vendorCredit->grand_total, 2) }}</dd>
                                </div>
                            </dl>

                            @if ($vendorCredit->tax_manual)
                                <p class="text-xs text-amber-600 dark:text-amber-400">Tax amounts were entered manually (override).</p>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if ($vendorCredit->notes)
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Notes</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ $vendorCredit->notes }}</p>
                        </div>
                    @endif

                    {{-- Void info --}}
                    @if ($vendorCredit->status === 'voided')
                        <div class="rounded-lg border border-red-200 bg-red-50 p-5 shadow-sm dark:border-red-800 dark:bg-red-900/20">
                            <h3 class="mb-2 text-sm font-semibold text-red-700 dark:text-red-400">Voided</h3>
                            <p class="text-sm text-red-600 dark:text-red-400">
                                Voided {{ $vendorCredit->voided_at?->format('M j, Y g:ia') }}
                                @if ($vendorCredit->void_reason) — {{ $vendorCredit->void_reason }} @endif
                            </p>
                        </div>
                    @endif

                </div>

                {{-- Sidebar --}}
                <div class="space-y-4">

                    {{-- Details --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Details</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Credit Memo #</dt>
                                <dd class="font-mono font-semibold text-green-700 dark:text-green-400">{{ $vendorCredit->credit_memo_number }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Status</dt>
                                <dd><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadge }}">{{ $vendorCredit->status_label }}</span></dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Vendor</dt>
                                <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $vendorCredit->vendor?->company_name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Date</dt>
                                <dd class="text-gray-900 dark:text-white">{{ $vendorCredit->date->format('M j, Y') }}</dd>
                            </div>
                            @if ($vendorCredit->reference_number)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Vendor ref #</dt>
                                <dd class="font-mono text-gray-900 dark:text-white">{{ $vendorCredit->reference_number }}</dd>
                            </div>
                            @endif
                            @if ($vendorCredit->inventoryReturn)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">RTV</dt>
                                <dd>
                                    <a href="{{ route('pages.inventory.rtv.show', $vendorCredit->inventoryReturn) }}"
                                       class="text-blue-600 hover:underline dark:text-blue-400">
                                        {{ $vendorCredit->inventoryReturn->return_number }}
                                    </a>
                                </dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                    {{-- QuickBooks status --}}
                    @if (app(\App\Services\QuickBooksService::class)->isConnected())
                        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">QuickBooks Online</h3>
                            @if ($vendorCredit->qbo_id)
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Synced</span>
                                </div>
                                <dl class="space-y-2 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">QBO ID</dt>
                                        <dd class="font-mono text-gray-700 dark:text-gray-300">{{ $vendorCredit->qbo_id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Synced</dt>
                                        <dd class="text-gray-700 dark:text-gray-300">{{ $vendorCredit->qbo_synced_at?->format('M j, Y g:i a') ?? '—' }}</dd>
                                    </div>
                                </dl>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Not synced</span>
                                @if ($vendorCredit->status !== 'voided')
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Use the "Push to QBO" button above to sync this credit memo.</p>
                                @endif
                            @endif
                        </div>
                    @endif

                    {{-- Audit --}}
                    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Audit</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Created by</dt>
                                <dd class="text-gray-900 dark:text-white text-right">{{ $vendorCredit->creator?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Created at</dt>
                                <dd class="text-gray-400 text-xs text-right">{{ $vendorCredit->created_at->format('M j, Y g:ia') }}</dd>
                            </div>
                            @if ($vendorCredit->updater && $vendorCredit->updated_at != $vendorCredit->created_at)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500 shrink-0">Updated by</dt>
                                <dd class="text-gray-900 dark:text-white text-right">{{ $vendorCredit->updater->name }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Void modal --}}
    @if ($vendorCredit->status !== 'voided')
    <div id="void-modal"
         class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         onclick="if(event.target===this) this.classList.add('hidden')">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Void Credit Memo</h2>
                <button onclick="document.getElementById('void-modal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.vendor-credits.void', $vendorCredit) }}">
                @csrf
                <div class="px-6 py-5 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Voiding <strong>{{ $vendorCredit->credit_memo_number }}</strong> will remove it from the vendor's AP credit balance.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Reason <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <input type="text" name="void_reason" placeholder="e.g. Duplicate entry"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30 rounded-b-xl">
                    <button type="button" onclick="document.getElementById('void-modal').classList.add('hidden')"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                        Void Credit Memo
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</x-app-layout>
