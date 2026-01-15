{{-- resources/views/admin/tax/index.blade.php --}}
<x-admin-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-semibold text-gray-900">Tax</h1>
                <p class="text-sm text-gray-600 mt-1">
                    Manage tax agencies, rates, and groups.
                </p>
            </div>

            {{-- Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('admin.tax_agencies.index') }}"
                   class="block p-5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Tax Agencies</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900">Agencies</div>
                    <div class="mt-2 text-sm text-gray-600">Create and manage agencies.</div>
                </a>

                <a href="{{ route('admin.tax_rates.index') }}"
                   class="block p-5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Tax Rates</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900">Rates</div>
                    <div class="mt-2 text-sm text-gray-600">Create and manage tax rates.</div>
                </a>

                <a href="{{ route('admin.tax_groups.index') }}"
                   class="block p-5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div class="text-sm text-gray-500">Tax Groups</div>
                    <div class="mt-1 text-lg font-semibold text-gray-900">Groups</div>
                    <div class="mt-2 text-sm text-gray-600">Combine rates into groups.</div>
                </a>
            </div>

        </div>
    </div>
</x-admin-layout>
