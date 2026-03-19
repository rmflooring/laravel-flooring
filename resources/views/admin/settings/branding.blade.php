<x-admin-layout>
    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Branding</h1>
                    <p class="text-sm text-gray-600 mt-1">Company name, logo, and contact details used on PDFs and documents.</p>
                </div>
                <a href="{{ route('admin.settings') }}"
                   class="text-sm text-gray-500 hover:text-gray-700">&larr; Back to Settings</a>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-4 text-red-800 bg-red-100 border border-red-200 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Logo --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-800">Company Logo</h2>
                <p class="text-sm text-gray-500">Used in the header of PDF estimates, sales, and invoices. PNG or JPG recommended, max 2 MB.</p>

                @if ($logo_path)
                    <div class="flex items-center gap-6">
                        <div class="border border-gray-200 rounded-lg p-3 bg-gray-50">
                            <img src="{{ Storage::url($logo_path) }}" alt="Company logo" class="h-16 max-w-xs object-contain">
                        </div>
                        <form method="POST" action="{{ route('admin.settings.branding.remove-logo') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Remove the current logo?')"
                                    class="text-sm text-red-600 hover:text-red-800 underline">
                                Remove logo
                            </button>
                        </form>
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">No logo uploaded yet. Upload one below.</p>
                @endif

                <form method="POST" action="{{ route('admin.settings.branding.upload-logo') }}"
                      enctype="multipart/form-data"
                      class="flex items-center gap-3">
                    @csrf
                    <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/svg+xml,image/webp"
                           class="block text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-lg hover:bg-gray-700">
                        Upload
                    </button>
                </form>
                @error('logo')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Company Details --}}
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4">Company Details</h2>

                <form method="POST" action="{{ route('admin.settings.branding.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Company Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="company_name"
                                   value="{{ old('company_name', $company_name) }}"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('company_name') border-red-500 @enderror">
                            @error('company_name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tagline / Subtitle</label>
                            <input type="text" name="tagline"
                                   value="{{ old('tagline', $tagline) }}"
                                   placeholder="e.g. rmflooring.ca"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Shown below the company name when no logo is uploaded.</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                            <input type="text" name="address"
                                   value="{{ old('address', $address) }}"
                                   placeholder="e.g. 123 Main St"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="city"
                                   value="{{ old('city', $city) }}"
                                   placeholder="e.g. Calgary"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <input type="text" name="province"
                                   value="{{ old('province', $province) }}"
                                   placeholder="e.g. AB"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                            <input type="text" name="postal"
                                   value="{{ old('postal', $postal) }}"
                                   placeholder="e.g. T2A 1B3"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <input type="text" name="phone"
                                   value="{{ old('phone', $phone) }}"
                                   placeholder="e.g. (555) 123-4567"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email"
                                   value="{{ old('email', $email) }}"
                                   placeholder="e.g. info@rmflooring.ca"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm email-input">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                            <input type="text" name="website"
                                   value="{{ old('website', $website) }}"
                                   placeholder="e.g. rmflooring.ca"
                                   class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800">
                            Save Branding
                        </button>
                    </div>
                </form>
            </div>

            {{-- Preview note --}}
            <p class="text-xs text-gray-400 text-center">
                Changes take effect immediately on all newly generated PDFs.
            </p>

        </div>
    </div>
</x-admin-layout>
