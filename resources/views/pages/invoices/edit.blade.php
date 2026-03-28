<x-app-layout>
<div class="py-8">
<div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">&larr; Invoice {{ $invoice->invoice_number }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Invoice {{ $invoice->invoice_number }}</h1>

    @if (session('success'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <form action="{{ route('pages.sales.invoices.update', [$sale, $invoice]) }}" method="POST" class="space-y-5">
            @csrf @method('PUT')

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @foreach(['draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid', 'overdue' => 'Overdue', 'partially_paid' => 'Partially Paid'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('status', $invoice->status) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Payment Terms</label>
                <select name="payment_term_id"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">— None —</option>
                    @foreach ($paymentTerms as $term)
                        <option value="{{ $term->id }}" {{ old('payment_term_id', $invoice->payment_term_id) == $term->id ? 'selected' : '' }}>
                            {{ $term->name }}{{ $term->days ? ' (Net ' . $term->days . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Due Date</label>
                <input type="date" name="due_date"
                    value="{{ old('due_date', $invoice->due_date?->format('Y-m-d')) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Customer PO #</label>
                <input type="text" name="customer_po_number"
                    value="{{ old('customer_po_number', $invoice->customer_po_number) }}"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            </div>

            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                <textarea name="notes" rows="3"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ old('notes', $invoice->notes) }}</textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Save Changes
                </button>
                <a href="{{ route('pages.sales.invoices.show', [$sale, $invoice]) }}"
                    class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</div>
</div>
</x-app-layout>
