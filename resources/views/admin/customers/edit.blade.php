<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-3xl font-bold">Edit Customer: {{ $customer->name ?? $customer->company_name }}</h1>
                        <a href="{{ route('admin.customers.show', $customer) }}"
                           class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 font-medium rounded-lg text-sm px-4 py-2">
                            Back to Customer
                        </a>
                    </div>

                    <form method="POST" action="{{ route('admin.customers.update', $customer) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                                <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 email-input">
                            </div>

                            <div>
								<label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
								<input type="text" name="phone"
									value="{{ old('phone', $customer->phone) }}"
									class="phone-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
							</div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mobile</label>
                                <input type="text" name="mobile" value="{{ old('mobile', $customer->mobile) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SMS Notifications</label>
                                @if($customer->sms_opted_out)
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-red-100 text-red-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Opted Out{{ $customer->sms_opted_out_at ? ' on ' . $customer->sms_opted_out_at->format('M j, Y') : '' }}
                                        </span>
                                        <span class="text-xs text-gray-400">Use the toggle on the customer page to re-enable.</span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded text-xs font-medium bg-green-100 text-green-700">Active</span>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parent Customer</label>
                                <select name="parent_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">None (Top Level)</option>
                                    @foreach($parents as $id => $name)
                                        <option value="{{ $id }}" {{ old('parent_id', $customer->parent_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                <input type="text" name="address" value="{{ old('address', $customer->address) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Address 2</label>
                                <input type="text" name="address2" value="{{ old('address2', $customer->address2) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                <select name="province" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach($provinces as $code => $name)
                                        <option value="{{ $code }}" {{ old('province', $customer->province) == $code ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code', $customer->postal_code) }}" class="postal-input block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Type</label>
                                <select name="customer_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="individual" {{ old('customer_type', $customer->customer_type) == 'individual' ? 'selected' : '' }}>Individual</option>
                                    <option value="company" {{ old('customer_type', $customer->customer_type) == 'company' ? 'selected' : '' }}>Company</option>
                                    <option value="restoration" {{ old('customer_type', $customer->customer_type) == 'restoration' ? 'selected' : '' }}>Restoration</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Status</label>
                                <select name="customer_status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active" {{ old('customer_status', $customer->customer_status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('customer_status', $customer->customer_status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="lead" {{ old('customer_status', $customer->customer_status) == 'lead' ? 'selected' : '' }}>Lead</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea name="notes" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $customer->notes) }}</textarea>
                        </div>

                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('admin.customers.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg">
                                Update Customer
                            </button>
                        </div>
                    </form>

                    {{-- Contacts section (separate from main form to avoid nesting) --}}
                    <div class="mt-8" x-data="{ showAdd: false }">
                        <div class="flex items-center justify-between mb-3">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Contacts
                                @if($customer->contacts->isNotEmpty())
                                    <span class="ml-2 bg-indigo-100 text-indigo-700 text-xs font-medium px-2 py-0.5 rounded">{{ $customer->contacts->count() }}</span>
                                @endif
                            </h2>
                            <button type="button" @click="showAdd = !showAdd"
                                    class="text-sm font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-lg px-3 py-1.5 hover:bg-blue-100">
                                + Add Contact
                            </button>
                        </div>

                        {{-- Add contact form --}}
                        <div x-show="showAdd" x-cloak class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                            <form method="POST" action="{{ route('admin.customers.contacts.store', $customer) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                                @csrf
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="name" required placeholder="Jane Smith"
                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                    <input type="text" name="title" placeholder="Project Coordinator"
                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                                    <input type="email" name="email" placeholder="jane@example.com"
                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                                    <input type="text" name="phone" placeholder="555-123-4567"
                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit"
                                            class="flex-1 text-sm font-medium text-white bg-blue-700 rounded-lg px-4 py-2 hover:bg-blue-800">
                                        Save
                                    </button>
                                    <button type="button" @click="showAdd = false"
                                            class="text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg px-3 py-2 hover:bg-gray-50">
                                        Cancel
                                    </button>
                                </div>
                                <div class="md:col-span-5">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                                    <input type="text" name="notes" placeholder="e.g. best reached by email"
                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </form>
                        </div>

                        @if($customer->contacts->isNotEmpty())
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3">Title</th>
                                        <th class="px-4 py-3">Email</th>
                                        <th class="px-4 py-3">Phone</th>
                                        <th class="px-4 py-3">Notes</th>
                                        <th class="px-4 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer->contacts as $contact)
                                    <tr class="border-b last:border-0 hover:bg-gray-50" x-data="{ editing: false }">
                                        {{-- View row --}}
                                        <td class="px-4 py-3 font-medium text-gray-900" x-show="!editing">{{ $contact->name }}</td>
                                        <td class="px-4 py-3" x-show="!editing">{{ $contact->title ?? '—' }}</td>
                                        <td class="px-4 py-3" x-show="!editing">
                                            @if($contact->email)
                                                <a href="mailto:{{ $contact->email }}" class="text-blue-600 hover:underline">{{ $contact->email }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3" x-show="!editing">{{ $contact->phone ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-400 italic text-xs" x-show="!editing">{{ $contact->notes ?? '' }}</td>
                                        <td class="px-4 py-3" x-show="!editing">
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="editing = true"
                                                        class="text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                                <form method="POST" action="{{ route('admin.customers.contacts.destroy', [$customer, $contact]) }}"
                                                      onsubmit="return confirm('Remove this contact?')" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-medium text-red-600 hover:underline">Remove</button>
                                                </form>
                                            </div>
                                        </td>

                                        {{-- Edit row --}}
                                        <td colspan="6" class="px-4 py-3 bg-blue-50" x-show="editing" x-cloak>
                                            <form method="POST" action="{{ route('admin.customers.contacts.update', [$customer, $contact]) }}"
                                                  class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                                                @csrf
                                                @method('PUT')
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
                                                    <input type="text" name="name" value="{{ $contact->name }}" required
                                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                                    <input type="text" name="title" value="{{ $contact->title }}"
                                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                                                    <input type="email" name="email" value="{{ $contact->email }}"
                                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                                                    <input type="text" name="phone" value="{{ $contact->phone }}"
                                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                                </div>
                                                <div class="flex gap-2">
                                                    <button type="submit"
                                                            class="flex-1 text-sm font-medium text-white bg-blue-700 rounded-lg px-4 py-2 hover:bg-blue-800">
                                                        Update
                                                    </button>
                                                    <button type="button" @click="editing = false"
                                                            class="text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg px-3 py-2 hover:bg-gray-50">
                                                        Cancel
                                                    </button>
                                                </div>
                                                <div class="md:col-span-5">
                                                    <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
                                                    <input type="text" name="notes" value="{{ $contact->notes }}"
                                                           class="block w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500">
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-sm text-gray-400 italic">No contacts added yet.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
