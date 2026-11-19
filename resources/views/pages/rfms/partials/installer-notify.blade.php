{{-- Installer notify checkbox. Expects $channel ('email'|'sms'), $checked (bool). Hidden until an installer is picked. --}}
@php $isEmail = $channel === 'email'; @endphp
<label class="flex items-start gap-3 cursor-pointer" data-installer-notify="{{ $channel }}" style="display:none">
    <input type="checkbox" id="{{ $isEmail ? 'notify_installer' : 'sms_notify_installer' }}"
           name="{{ $isEmail ? 'notify_installer' : 'sms_notify_installer' }}" value="1" {{ $checked ? 'checked' : '' }}
           class="mt-0.5 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
    <div>
        <span class="text-sm font-medium text-gray-700">{{ $isEmail ? 'Notify installer' : 'SMS installer' }}</span>
        <p class="text-xs text-gray-400 mt-0.5" data-installer-hint="{{ $channel }}"></p>
    </div>
</label>
