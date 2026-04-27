<x-app-layout>
    <div class="py-6">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Back --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.payments.show', $payment) }}"
                   class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Payment Detail
                </a>
            </div>

            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Payment</h1>
                @php $invoice = $payment->invoice; $sale = $invoice?->sale; @endphp
                @if ($invoice)
                    <p class="text-sm text-gray-500 mt-1">
                        Invoice {{ $invoice->invoice_number }}
                        @if ($sale) &mdash; Sale #{{ $sale->sale_number }} @endif
                    </p>
                @endif
            </div>

            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <ul class="list-disc list-inside space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-gray-50">
                    <h2 class="text-base font-semibold text-gray-900">Payment Details</h2>
                </div>
                <form action="{{ route('admin.payments.update', $payment) }}" method="POST" class="px-6 py-5 space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 text-sm pointer-events-none">$</span>
                                <input type="number" name="amount" step="0.01" min="0.01"
                                       value="{{ old('amount', $payment->amount) }}"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 p-2.5"
                                       required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="payment_date"
                                   value="{{ old('payment_date', $payment->payment_date?->format('Y-m-d')) }}"
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                required>
                            @foreach ($paymentMethods as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_method', $payment->payment_method) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference #</label>
                        <input type="text" name="reference_number"
                               value="{{ old('reference_number', $payment->reference_number) }}"
                               placeholder="Cheque #, transaction ID, etc."
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" rows="3"
                                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">{{ old('notes', $payment->notes) }}</textarea>
                    </div>

                    <div class="pt-2 border-t border-gray-100 flex items-center gap-3">
                        <button type="submit"
                                class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                            Save Changes
                        </button>
                        <a href="{{ route('admin.payments.show', $payment) }}"
                           class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>

            {{-- Recorded by (read-only context) --}}
            <p class="text-xs text-gray-400 text-center">
                Originally recorded by {{ $payment->recordedBy?->name ?? 'unknown' }}
                on {{ $payment->created_at?->format('M j, Y g:i A') }}
            </p>

        </div>
    </div>
</x-app-layout>
