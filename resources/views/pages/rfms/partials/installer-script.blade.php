{{-- Keeps the installer notify checkboxes in sync with the installer dropdown. Expects $installers. --}}
@php
    $installerContacts = $installers->mapWithKeys(fn ($i) => [$i->id => [
        'email' => $i->email,
        'phone' => $i->mobile ?: $i->phone,
    ]])->toArray();
@endphp
<script>
    (function () {
        const contacts = @json($installerContacts);
        const select   = document.getElementById('installer_id');
        if (!select) return;

        function refreshInstallerNotify() {
            const c = contacts[select.value] || null;
            document.querySelectorAll('[data-installer-notify]').forEach(row => {
                const channel = row.dataset.installerNotify;
                const value   = c ? (channel === 'email' ? c.email : c.phone) : null;
                const box     = row.querySelector('input[type=checkbox]');
                const hint    = row.querySelector('[data-installer-hint]');
                row.style.display = c ? '' : 'none';
                if (!c) { box.checked = false; return; }
                box.disabled = !value;
                if (!value) box.checked = false;
                row.style.opacity = value ? '' : '0.5';
                hint.textContent = value
                    ? 'Will be sent to: ' + value
                    : 'This installer has no ' + (channel === 'email' ? 'email address' : 'mobile number') + ' on record.';
            });
        }

        select.addEventListener('change', function () {
            // Newly picked installer → default both notifications on
            document.querySelectorAll('[data-installer-notify] input[type=checkbox]').forEach(b => b.checked = true);
            refreshInstallerNotify();
        });
        refreshInstallerNotify();
    })();
</script>
