<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-3xl font-bold mb-6">Add New GL Account</h1>

                    <form method="POST" action="{{ route('admin.gl_accounts.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Number *</label>
                                <input type="text" name="account_number" value="{{ old('account_number') }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('account_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Name *</label>
                                <input type="text" name="name" value="{{ old('name') }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Type *</label>
                                <select name="account_type_id" id="account_type_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Account Type</option>
                                    @foreach($accountTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('account_type_id') == $type->id ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('account_type_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Detail Type *</label>
                                <select name="detail_type_id" id="detail_type_id" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Detail Type</option>
                                </select>
                                @error('detail_type_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" id="is_subaccount" name="is_subaccount" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <label for="is_subaccount" class="ml-2 text-sm text-gray-700">Make this a subaccount</label>
                            </div>

                            <div id="parent_account_field" class="md:col-span-2 hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Parent Account</label>
                                <select name="parent_id" id="parent_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Parent Account</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                <select name="status" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="4" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                        </div>

                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('admin.gl_accounts.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-3 px-8 rounded-lg">
                                Cancel
                            </a>
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-8 rounded-lg">
                                Create GL Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Inline script at the very bottom -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    console.log('Inline script loaded - checking if ready');

    $(function() {
        console.log('jQuery DOM ready');

        if ($('#account_type_id').length === 0) {
            console.error('ERROR: #account_type_id NOT found in DOM');
        } else {
            console.log('SUCCESS: #account_type_id found');
        }

        $('#account_type_id').on('change', function() {
            var id = $(this).val();
            console.log('CHANGE EVENT FIRED - Account Type ID:', id);

            $('#detail_type_id').html('<option value="">Loading...</option>');
            $('#parent_id').html('<option value="">Select Parent Account</option>');

            if (id) {
                console.log('Making Ajax call to detail-types route');

                $.get('{{ route('gl_accounts.detail_types') }}', { account_type_id: id })
                    .done(function(data) {
                        console.log('AJAX SUCCESS - Detail Types:', data);
                        $('#detail_type_id').html('<option value="">Select Detail Type</option>');
                        $.each(data, function(i, item) {
                            $('#detail_type_id').append('<option value="' + item.id + '">' + item.name + '</option>');
                        });
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX FAIL - Detail Types:', textStatus, errorThrown, jqXHR.responseText);
                        $('#detail_type_id').html('<option value="">Error loading types (check console)</option>');
                    });

                console.log('Making Ajax call to parent-accounts route');

                $.get('{{ route('gl_accounts.parent_accounts') }}', { account_type_id: id })
                    .done(function(data) {
                        console.log('AJAX SUCCESS - Parent Accounts:', data);
                        $('#parent_id').html('<option value="">Select Parent Account</option>');
                        $.each(data, function(i, item) {
                            $('#parent_id').append('<option value="' + item.id + '">' + item.account_number + ' - ' + item.name + '</option>');
                        });
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX FAIL - Parent Accounts:', textStatus, errorThrown, jqXHR.responseText);
                    });
            } else {
                console.log('No Account Type selected - resetting dropdowns');
            }
        });

        $('#is_subaccount').on('change', function () {
            if ($(this).is(':checked')) {
                $('#parent_account_field').removeClass('hidden');
            } else {
                $('#parent_account_field').addClass('hidden');
                $('#parent_id').val('');
            }
        });
    });
</script>
</x-app-layout>