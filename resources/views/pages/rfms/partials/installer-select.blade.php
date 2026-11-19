{{-- Installer (optional) — shared by RFM create + edit. Expects $installers, $selectedInstallerId --}}
<div>
    <label for="installer_id" class="block text-sm font-medium text-gray-700 mb-1">
        Installer <span class="text-xs font-normal text-gray-400">(optional)</span>
    </label>
    <select id="installer_id" name="installer_id"
            class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 text-sm @error('installer_id') border-red-500 @enderror">
        <option value="">— No Installer —</option>
        @foreach ($installers as $i)
            <option value="{{ $i->id }}" {{ (string) old('installer_id', $selectedInstallerId) === (string) $i->id ? 'selected' : '' }}>
                {{ $i->company_name }}{{ $i->contact_name && $i->contact_name !== $i->company_name ? ' — ' . $i->contact_name : '' }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-400">Added to the calendar invite, notifications and reminders.</p>
    @error('installer_id')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
