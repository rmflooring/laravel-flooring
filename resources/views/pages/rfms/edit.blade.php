<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Edit RFM</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Job #{{ $opportunity->job_no ?? '—' }} &mdash;
                        {{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}
                    </p>
                </div>
                <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
            </div>

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="mb-4 p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg flex items-center justify-between">
                    <div>{{ session('success') }}</div>
                    <button type="button" onclick="this.closest('div').remove()"
                            class="text-green-900 hover:text-green-700 text-sm font-medium">✕</button>
                </div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-100">

                {{-- Status --}}
                <div class="p-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Status</h2>
                    <div class="flex flex-wrap gap-2">
                        @php
                            $statusColors = [
                                'pending'   => ['active' => 'bg-yellow-100 text-yellow-800 ring-yellow-400', 'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                                'confirmed' => ['active' => 'bg-blue-100 text-blue-800 ring-blue-400',   'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                                'completed' => ['active' => 'bg-green-100 text-green-800 ring-green-400', 'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                                'cancelled' => ['active' => 'bg-red-100 text-red-800 ring-red-400',     'inactive' => 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50'],
                            ];
                        @endphp
                        @foreach(\App\Models\Rfm::STATUSES as $s)
                            <form method="POST"
                                  action="{{ route('pages.opportunities.rfms.updateStatus', [$opportunity->id, $rfm->id]) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $s }}">
                                <button type="submit"
                                        class="px-4 py-1.5 rounded-full text-sm font-medium
                                            {{ $rfm->status === $s
                                                ? $statusColors[$s]['active'] . ' ring-2'
                                                : $statusColors[$s]['inactive'] }}">
                                    {{ ucfirst($s) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>

                {{-- Job Info (read-only) --}}
                <div class="p-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Job Info</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Customer</label>
                            <input type="text" disabled
                                   value="{{ $opportunity->parentCustomer?->company_name ?: $opportunity->parentCustomer?->name ?? '—' }}"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-600">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Job Site</label>
                            <input type="text" disabled
                                   value="{{ $opportunity->jobSiteCustomer?->company_name ?: $opportunity->jobSiteCustomer?->name ?? '—' }}"
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-sm text-gray-600">
                        </div>

                    </div>
                </div>

                {{-- Measure Details --}}
                <div class="p-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Measure Details</h2>
                    <form method="POST"
                          action="{{ route('pages.opportunities.rfms.update', [$opportunity->id, $rfm->id]) }}"
                          id="rfm-edit-form">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            {{-- Estimator --}}
                            <div>
                                <label for="estimator_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Estimator <span class="text-red-500">*</span>
                                </label>
                                <select id="estimator_id" name="estimator_id"
                                        class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('estimator_id') border-red-500 @enderror">
                                    <option value="">— Select Estimator —</option>
                                    @foreach ($estimators as $e)
                                        <option value="{{ $e->id }}"
                                            {{ old('estimator_id', $rfm->estimator_id) == $e->id ? 'selected' : '' }}>
                                            {{ $e->first_name }} {{ $e->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('estimator_id')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Flooring Type --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Flooring Type <span class="text-red-500">*</span>
                                </label>
                                <div class="space-y-2">
                                    @foreach ($flooringTypes as $type)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="flooring_type[]" value="{{ $type }}"
                                                   {{ in_array($type, old('flooring_type', $rfm->flooring_type ?? [])) ? 'checked' : '' }}
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">{{ $type }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('flooring_type')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Scheduled Date/Time --}}
                            <div>
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">
                                    Scheduled Date & Time <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at"
                                       value="{{ old('scheduled_at', $rfm->scheduled_at->format('Y-m-d\TH:i')) }}"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('scheduled_at') border-red-500 @enderror">
                                @error('scheduled_at')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Site Address --}}
                            <div>
                                <label for="site_address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Site Address
                                </label>
                                <input type="text" id="site_address" name="site_address"
                                       value="{{ old('site_address', $rfm->site_address) }}"
                                       class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('site_address') border-red-500 @enderror">
                                @error('site_address')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>

                        {{-- Special Instructions --}}
                        <div class="mt-4">
                            <label for="special_instructions" class="block text-sm font-medium text-gray-700 mb-1">
                                Special Instructions
                            </label>
                            <textarea id="special_instructions" name="special_instructions" rows="4"
                                      class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('special_instructions') border-red-500 @enderror">{{ old('special_instructions', $rfm->special_instructions) }}</textarea>
                            @error('special_instructions')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                    </form>
                </div>

                {{-- Actions --}}
                <div class="p-6 flex justify-end gap-3">
                    <a href="{{ route('pages.opportunities.show', $opportunity->id) }}"
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" form="rfm-edit-form"
                            class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                        Save Changes
                    </button>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
