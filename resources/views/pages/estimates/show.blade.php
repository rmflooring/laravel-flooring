<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Estimate {{ $estimate->estimate_number ?? ('#' . $estimate->id) }}
                    </h1>
                    <p class="text-sm text-gray-600 mt-1">Read-only view of the estimate record.</p>
                </div>

                @php $isConverted = (bool) $estimate->sale; @endphp
                <div class="flex items-center gap-2">
                    <a href="{{ route('pages.estimates.index') }}"
                       class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Back
                    </a>

                    @if ($isConverted)
                        {{-- Locked: show disabled Edit + Make Revision --}}
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                            Edit
                        </span>
                        <form method="POST" action="{{ route('pages.estimates.make-revision', $estimate) }}">
                            @csrf
                            <button type="submit"
                                    onclick="return confirm('Create a new editable revision of this estimate?')"
                                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600">
                                Make Revision
                            </button>
                        </form>
                    @else
                        <a href="{{ route('pages.estimates.edit', $estimate) }}"
                           class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg hover:bg-gray-800">
                            Edit
                        </a>
                        @if ($estimate->status === 'approved')
                            <form method="POST" action="{{ route('pages.estimates.convert-to-sale', $estimate) }}"
                                  onsubmit="return confirm('Convert this approved estimate to a Sale?')">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    Convert to Sale
                                </button>
                            </form>
                        @endif
                    @endif

                    @can('create estimates')
                    <form method="POST" action="{{ route('pages.estimates.duplicate', $estimate) }}">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('Copy Estimate {{ $estimate->estimate_number }} to a new estimate?')"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Copy to New Estimate
                        </button>
                    </form>
                    @endcan

                    <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('open-send-email-modal'))"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-purple-700 rounded-lg hover:bg-purple-800">
                        Send Email
                    </button>
                    <div class="relative" x-data="{ pdfOpen: false }">
                        <button type="button" @click="pdfOpen = !pdfOpen"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                            </svg>
                            Print PDF
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="pdfOpen" x-cloak
                             @click.outside="pdfOpen = false"
                             class="absolute left-0 mt-1 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50 py-1">
                            <a href="{{ route('pages.estimates.pdf', $estimate) }}" target="_blank"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Detailed — full pricing
                            </a>
                            <a href="{{ route('pages.estimates.pdf', $estimate) }}?format=simple" target="_blank"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Simple — descriptions only
                            </a>
                            <a href="{{ route('pages.estimates.pdf', $estimate) }}?format=room_totals" target="_blank"
                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Room Totals — totals per room
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Converted-to-sale lock banner --}}
            @if ($isConverted)
                <div class="flex items-start gap-3 p-4 text-amber-800 bg-amber-50 border border-amber-200 rounded-lg">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                    <div class="space-y-1.5">
                        <p>This estimate has been <strong>converted to <a href="{{ route('pages.sales.show', $estimate->sale) }}" class="underline hover:text-amber-900">Sale #{{ $estimate->sale->sale_number }}</a></strong> and is locked. Use <strong>Make Revision</strong> to create a new editable copy.</p>
                        @if ($estimate->revisions->isNotEmpty())
                            <p class="text-sm">
                                <span class="font-medium">Revisions:</span>
                                @foreach ($estimate->revisions as $rev)
                                    <a href="{{ route('pages.estimates.show', $rev) }}"
                                       class="inline-flex items-center gap-1 underline hover:text-amber-900">
                                        {{ $rev->estimate_number ?? ('Rev' . str_pad($rev->revision_no, 2, '0', STR_PAD_LEFT)) }}
                                    </a>@unless($loop->last),&nbsp;@endunless
                                @endforeach
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Status</div>
                    <div class="mt-1 font-semibold text-gray-900 capitalize">{{ $estimate->status ?? '—' }}</div>
                    @if($estimate->sale)
                        <div class="mt-1">
                            <a href="{{ route('pages.sales.show', $estimate->sale) }}"
                               class="text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                Converted → Sale #{{ $estimate->sale->sale_number }}
                            </a>
                        </div>
                    @endif
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Estimate #</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $estimate->estimate_number ?? '—' }}</div>
                    @if ($estimate->parentEstimate)
                        <div class="mt-1 text-xs text-gray-500">
                            Original:
                            <a href="{{ route('pages.estimates.show', $estimate->parentEstimate) }}"
                               class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $estimate->parentEstimate->estimate_number ?? ('#' . $estimate->parentEstimate->id) }}
                            </a>
                        </div>
                    @endif
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Grand Total</div>
                    <div class="mt-1 font-semibold text-gray-900">${{ number_format((float) $estimate->grand_total, 2) }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm">
                    <div class="text-xs text-gray-500">Created</div>
                    <div class="mt-1 font-semibold text-gray-900">{{ $estimate->created_at?->format('M j, Y') ?? '—' }}</div>
                </div>
            </div>

            {{-- Details --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900 mb-4">Customer &amp; Job</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <div class="text-gray-500">Customer</div>
                        <div class="font-medium text-gray-900">{{ $estimate->customer_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Homeowner</div>
                        <div class="font-medium text-gray-900">{{ $estimate->homeowner_name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $estimate->homeowner_phone ?? '' }}</div>
                        <div class="text-xs text-gray-500">{{ $estimate->homeowner_email ?? '' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">PM</div>
                        <div class="font-medium text-gray-900">{{ $estimate->pm_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Job</div>
                        <div class="font-medium text-gray-900">{{ $estimate->job_name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $estimate->job_no ?? '' }}</div>
                    </div>
                    @if ($estimate->opportunity)
                        <div>
                            <div class="text-gray-500">Opportunity</div>
                            <a href="{{ route('pages.opportunities.show', $estimate->opportunity) }}"
                               class="font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                #{{ $estimate->opportunity->job_no ?? $estimate->opportunity->id }}
                            </a>
                        </div>
                    @endif
                    <div>
                        <div class="text-gray-500">Job Address</div>
                        <div class="font-medium text-gray-900 whitespace-pre-line">{{ $estimate->job_address ?? '—' }}</div>
                    </div>
                    @if ($estimate->salesperson1Employee)
                        <div>
                            <div class="text-gray-500">Salesperson</div>
                            <div class="font-medium text-gray-900">
                                {{ $estimate->salesperson1Employee->first_name }} {{ $estimate->salesperson1Employee->last_name }}
                            </div>
                        </div>
                    @endif
                    @if ($estimate->notes)
                        <div class="md:col-span-3">
                            <div class="text-gray-500">Notes</div>
                            <div class="font-medium text-gray-900 whitespace-pre-line">{{ $estimate->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rooms --}}
            @if ($estimate->rooms->isNotEmpty())
                <div class="space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Rooms &amp; Items</h2>

                    @foreach ($estimate->rooms as $room)
                        @php
                            $materials = $room->items->where('item_type', 'material');
                            $labour    = $room->items->where('item_type', 'labour');
                            $freight   = $room->items->where('item_type', 'freight');
                            $roomTotal = $room->items->sum('line_total');
                        @endphp

                        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                            <div class="flex items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200">
                                <span class="font-semibold text-gray-800 text-sm">{{ $room->room_name ?: 'Unnamed Room' }}</span>
                                <span class="text-sm font-medium text-gray-600">${{ number_format($roomTotal, 2) }}</span>
                            </div>

                            <div class="divide-y divide-gray-100">

                                {{-- Materials --}}
                                @if ($materials->isNotEmpty())
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Materials</p>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-gray-700">
                                                <thead class="text-xs text-gray-500 border-b border-gray-100">
                                                    <tr>
                                                        <th class="pb-1 pr-4 font-medium">Product Type</th>
                                                        <th class="pb-1 pr-4 font-medium">Manufacturer</th>
                                                        <th class="pb-1 pr-4 font-medium">Style</th>
                                                        <th class="pb-1 pr-4 font-medium">Colour / Item #</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Qty</th>
                                                        <th class="pb-1 pr-4 font-medium">Unit</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Sell Price</th>
                                                        <th class="pb-1 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach ($materials as $item)
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->product_type ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->manufacturer ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->style ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->color_item_number ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">{{ $item->quantity }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->unit ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">${{ number_format($item->sell_price, 2) }}</td>
                                                            <td class="py-1.5 text-right font-medium">${{ number_format($item->line_total, 2) }}</td>
                                                        </tr>
                                                        @if ($item->notes)
                                                            <tr>
                                                                <td colspan="8" class="pb-1.5 text-xs text-gray-400 italic">{{ $item->notes }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                {{-- Labour --}}
                                @if ($labour->isNotEmpty())
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Labour</p>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-gray-700">
                                                <thead class="text-xs text-gray-500 border-b border-gray-100">
                                                    <tr>
                                                        <th class="pb-1 pr-4 font-medium">Type</th>
                                                        <th class="pb-1 pr-4 font-medium">Description</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Qty</th>
                                                        <th class="pb-1 pr-4 font-medium">Unit</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Sell Price</th>
                                                        <th class="pb-1 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach ($labour as $item)
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->labour_type ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->description ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">{{ $item->quantity }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->unit ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">${{ number_format($item->sell_price, 2) }}</td>
                                                            <td class="py-1.5 text-right font-medium">${{ number_format($item->line_total, 2) }}</td>
                                                        </tr>
                                                        @if ($item->notes)
                                                            <tr>
                                                                <td colspan="6" class="pb-1.5 text-xs text-gray-400 italic">{{ $item->notes }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                {{-- Freight --}}
                                @if ($freight->isNotEmpty())
                                    <div class="px-5 py-3">
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Freight</p>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm text-left text-gray-700">
                                                <thead class="text-xs text-gray-500 border-b border-gray-100">
                                                    <tr>
                                                        <th class="pb-1 pr-4 font-medium">Description</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Qty</th>
                                                        <th class="pb-1 pr-4 font-medium">Unit</th>
                                                        <th class="pb-1 pr-4 font-medium text-right">Sell Price</th>
                                                        <th class="pb-1 font-medium text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-50">
                                                    @foreach ($freight as $item)
                                                        <tr>
                                                            <td class="py-1.5 pr-4">{{ $item->freight_description ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">{{ $item->quantity }}</td>
                                                            <td class="py-1.5 pr-4">{{ $item->unit ?: '—' }}</td>
                                                            <td class="py-1.5 pr-4 text-right">${{ number_format($item->sell_price, 2) }}</td>
                                                            <td class="py-1.5 text-right font-medium">${{ number_format($item->line_total, 2) }}</td>
                                                        </tr>
                                                        @if ($item->notes)
                                                            <tr>
                                                                <td colspan="5" class="pb-1.5 text-xs text-gray-400 italic">{{ $item->notes }}</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                    <div class="max-w-xs ml-auto space-y-1.5 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Materials</span>
                            <span>${{ number_format($estimate->subtotal_materials, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Labour</span>
                            <span>${{ number_format($estimate->subtotal_labour, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Freight</span>
                            <span>${{ number_format($estimate->subtotal_freight, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 border-t border-gray-100 pt-1.5">
                            <span>Subtotal</span>
                            <span>${{ number_format($estimate->pretax_total, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax ({{ $estimate->tax_rate_percent }}%)</span>
                            <span>${{ number_format($estimate->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-900 border-t border-gray-200 pt-1.5 text-base">
                            <span>Grand Total</span>
                            <span>${{ number_format($estimate->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>

            @else
                <div class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm text-sm text-gray-500">
                    No rooms or items on this estimate.
                </div>
            @endif

        </div>
    </div>

{{-- Send Email Modal --}}
<div x-data="{ open: false, pdfFormat: 'detailed' }"
     @open-send-email-modal.window="open = true"
     x-show="open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     style="background: rgba(0,0,0,0.5)">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl" @click.outside="open = false">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h5 class="text-base font-semibold text-gray-800">Send Estimate Email</h5>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route('pages.estimates.send-email', $estimate) }}">
            @csrf
            <div class="px-6 py-5 space-y-4 max-h-[70vh] overflow-y-auto">
                @if (! $estimate->homeowner_email)
                    <div class="p-3 text-sm text-yellow-800 bg-yellow-50 border border-yellow-200 rounded-lg">
                        No homeowner email on this estimate. Enter a recipient below or save the estimate with an email first.
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="email" name="to" value="{{ $estimate->homeowner_email }}"
                           placeholder="customer@example.com"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>
                {{-- CC Addresses --}}
                <div x-data="{ ccEmails: [], ccInput: '' }">
                    <label class="block text-sm font-medium text-gray-700 mb-1">CC <span class="text-xs text-gray-400 font-normal">(optional)</span></label>
                    @if($customerContacts->isNotEmpty())
                    <div class="mb-2">
                        <p class="text-xs text-gray-500 mb-1.5">Quick-add from contacts:</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($customerContacts->filter(fn($c) => $c->email) as $contact)
                            <button type="button"
                                    @click="if(!ccEmails.includes('{{ $contact->email }}')) { ccEmails.push('{{ $contact->email }}') }"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-full border border-gray-300 hover:bg-indigo-50 hover:border-indigo-300 hover:text-indigo-700 transition-colors">
                                {{ $contact->name }}@if($contact->title) <span class="text-gray-400">&middot; {{ $contact->title }}</span>@endif
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="ccEmails.length > 0">
                        <template x-for="(email, i) in ccEmails" :key="i">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">
                                <span x-text="email"></span>
                                <input type="hidden" name="cc[]" :value="email">
                                <button type="button" @click="ccEmails.splice(i, 1)" class="text-blue-400 hover:text-blue-600 leading-none ml-1">&times;</button>
                            </span>
                        </template>
                    </div>
                    <div class="flex gap-2">
                        <input type="email" x-model="ccInput"
                               @keydown.enter.prevent="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                               placeholder="cc@example.com"
                               class="flex-1 bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <button type="button"
                                @click="if(ccInput.trim() && !ccEmails.includes(ccInput.trim())) { ccEmails.push(ccInput.trim()); ccInput = ''; }"
                                class="px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                            Add
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input type="text" name="subject" value="{{ $emailSubject }}"
                           class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="body" rows="10"
                              class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm font-mono">{{ $emailBody }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PDF Format</label>
                    <select name="pdf_format" x-model="pdfFormat"
                            class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        <option value="detailed">Detailed — full line-by-line pricing</option>
                        <option value="simple">Simple — descriptions only, grand total</option>
                        <option value="room_totals">Room Totals — descriptions with room subtotals</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <a :href="'{{ route('pages.estimates.pdf', $estimate) }}' + (pdfFormat !== 'detailed' ? '?format=' + pdfFormat : '')"
                       target="_blank"
                       class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-colors">
                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                        </svg>
                        <span>Estimate-{{ $estimate->estimate_number ?? $estimate->id }}.pdf</span>
                        <span class="text-xs text-gray-400 ml-1">— click to preview</span>
                    </a>
                </div>

                <p class="text-xs text-gray-400">
                    @if (auth()->user()->microsoftAccount?->mail_connected)
                        Sending from <strong>{{ auth()->user()->microsoftAccount->email }}</strong> via your personal MS365 account (Track 2).
                    @else
                        Sending from the shared mailbox via Track 1.
                    @endif
                </p>
            </div>
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100">
                <button type="button" @click="open = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-purple-700 rounded-lg hover:bg-purple-800">
                    Send Estimate
                </button>
            </div>
        </form>
    </div>
</div>

</x-admin-layout>
