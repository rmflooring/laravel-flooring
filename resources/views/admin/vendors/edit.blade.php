<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Edit Vendor: {{ $vendor->company_name }}</h1>

                    <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}"
                          x-data="{ vendorType: '{{ old('vendor_type', $vendor->vendor_type) }}' }">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                                <input type="text" name="company_name" value="{{ old('company_name', $vendor->company_name) }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('company_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                                <input type="text" name="contact_name" value="{{ old('contact_name', $vendor->contact_name) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 email-input">
                            </div>

                            <div>
								<label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
								<input type="text" name="phone"
									value="{{ old('phone', $vendor->phone) }}"
									class="phone-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
							</div>

                            <div>
								<label class="block text-sm font-medium text-gray-700 mb-2">Mobile</label>
								<input type="text" name="mobile"
									value="{{ old('mobile', $vendor->mobile) }}"
									class="phone-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
							</div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vendor Type</label>
                                <select name="vendor_type" x-model="vendorType" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($vendorTypes as $value => $label)
                                        <option value="{{ $value }}" {{ old('vendor_type', $vendor->vendor_type) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <input type="text" name="address" value="{{ old('address', $vendor->address) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address 2</label>
                                <input type="text" name="address2" value="{{ old('address2', $vendor->address2) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" name="city" value="{{ old('city', $vendor->city) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                <select name="province" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($provinces as $code => $name)
                                        <option value="{{ $code }}" {{ old('province', $vendor->province) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $vendor->postal_code) }}" class="postal-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input type="url" name="website" value="{{ old('website', $vendor->website) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                                <input type="text" name="account_number" value="{{ old('account_number', $vendor->account_number) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Terms</label>
                                <input type="text" name="terms" value="{{ old('terms', $vendor->terms) }}" placeholder="e.g., Net 30" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            {{-- Vendor Reps — shown for all types except Subcontractor --}}
                            <div class="md:col-span-2" x-show="vendorType !== 'Subcontractor'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Vendor Reps</label>
                                <select name="reps[]" multiple class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 h-32">
                                    @foreach($reps as $id => $name)
                                        <option value="{{ $id }}" {{ in_array($id, $selectedReps ?? []) ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Hold Ctrl (Windows) or Cmd (Mac) to select multiple reps.</p>
                            </div>

                            {{-- Installer — shown only for Subcontractor --}}
                            <div class="md:col-span-2" x-show="vendorType === 'Subcontractor'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Installer</label>
                                <select name="installer_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">— No installer linked —</option>
                                    @foreach($installers as $installer)
                                        <option value="{{ $installer->id }}"
                                            {{ old('installer_id', $linkedInstallerId) == $installer->id ? 'selected' : '' }}>
                                            {{ $installer->contact_name ?: '(no contact name)' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Link this subcontractor vendor to an installer record.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active" {{ old('status', $vendor->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status', $vendor->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        {{-- Return policy shown on rmflooring.ca for this vendor's products --}}
                        @php $ra = old('returns_accepted', $vendor->returns_accepted === null ? '' : (int) $vendor->returns_accepted); @endphp
                        <div class="mt-8 rounded-lg border border-gray-200 p-5" x-data="{ accepts: '{{ $ra }}' }">
                            <h3 class="text-base font-semibold text-gray-900">Return policy (online shop)</h3>
                            <p class="mt-1 text-sm text-gray-500">Shown to customers on rmflooring.ca for products from this vendor, at checkout and on the Returns page. The vendor’s name is never shown — customers see the brand.</p>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Returns</label>
                                    <select name="returns_accepted" x-model="accepts" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="" {{ $ra === '' ? 'selected' : '' }}>Not set up yet</option>
                                        <option value="1" {{ (string) $ra === '1' ? 'selected' : '' }}>Returns accepted</option>
                                        <option value="0" {{ (string) $ra === '0' ? 'selected' : '' }}>No returns (final sale)</option>
                                    </select>
                                </div>
                                <div x-show="accepts === '1'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Return window (days)</label>
                                    <input type="number" name="return_days" min="0" value="{{ old('return_days', $vendor->return_days) }}" placeholder="e.g., 30" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div x-show="accepts === '1'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Condition</label>
                                    <input type="text" name="return_condition" value="{{ old('return_condition', $vendor->return_condition) }}" placeholder="e.g., Unopened, full boxes only" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div x-show="accepts === '1'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Restocking fee (%)</label>
                                    <input type="number" name="restocking_fee_percent" min="0" max="100" step="0.01" value="{{ old('restocking_fee_percent', $vendor->restocking_fee_percent) }}" placeholder="e.g., 15" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div class="md:col-span-2" x-show="accepts !== ''">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="hidden" name="special_orders_final_sale" value="0">
                                        <input type="checkbox" name="special_orders_final_sale" value="1" {{ old('special_orders_final_sale', $vendor->special_orders_final_sale ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                        Special orders are final sale
                                    </label>
                                </div>
                                <div class="md:col-span-2" x-show="accepts !== ''">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Extra notes for customers (optional)</label>
                                    <textarea name="return_policy_notes" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('return_policy_notes', $vendor->return_policy_notes) }}</textarea>
                                </div>
                            </div>
                            @if ($vendor->returnPolicy())
                                <p class="mt-4 text-sm text-gray-700"><span class="font-medium">Customers currently see:</span> {{ $vendor->returnPolicy()['summary'] }}</p>
                            @endif
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea name="notes" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $vendor->notes) }}</textarea>
                        </div>

                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('admin.vendors.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg">
                                Update Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
