{{--
    Request E-Signature modal — shared by flooring sign-offs, signable generated
    documents (Work Authorization Form) and work orders, so they all look alike.
    Open with: document.getElementById('request-signature-modal').classList.remove('hidden')

    Props:
      action       form POST url
      documentName what's being signed, e.g. "Flooring Selection"
      clientName   / clientEmail  defaults for the client fields
      preview      ['subject' => …, 'body' => …] pre-filled email (System Email Template)
      alsoSignable collection for the "Also send for signature" list, or null to hide it
--}}
@props(['action', 'documentName', 'clientName' => '', 'clientEmail' => '', 'preview', 'alsoSignable' => null])

<div id="request-signature-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
     data-doc-label-base="{{ $documentName }}">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Request E-Signature</h3>
            <button type="button" onclick="document.getElementById('request-signature-modal').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl leading-none">&times;</button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            The client will receive an email with a link to review and sign <strong>{{ $documentName }}</strong>. The link expires in 10 days.
        </p>
        <form method="POST" action="{{ $action }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Name</label>
                <input type="text" name="client_name" value="{{ old('client_name', $clientName) }}" required
                       class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client Email</label>
                <input type="email" name="client_email" value="{{ old('client_email', $clientEmail) }}" required
                       class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>

            @if ($alsoSignable !== null)
                <x-signing.also-include :documents="$alsoSignable" :first="$documentName" />
            @endif

            <hr class="border-gray-200 dark:border-gray-700 mb-4">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Subject</label>
                <input type="text" name="subject" value="{{ old('subject', $preview['subject']) }}" required
                       class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
            </div>
            <div class="mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email Body</label>
                <textarea name="body" rows="10" required
                          class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono">{{ old('body', $preview['body']) }}</textarea>
            </div>
            <p class="text-xs text-gray-400 dark:text-gray-500 mb-6">
                <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700">@{{signing_link_button}}</code>,
                <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700">@{{signing_link}}</code> and
                <code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-gray-700">@{{expires_date}}</code>
                are placeholders filled in automatically when the email is sent &mdash; leave them as-is.
            </p>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('request-signature-modal').classList.add('hidden')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    Cancel
                </button>
                <button type="submit"
                        class="rounded-lg bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                    Send Signature Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Keep the email's document name in step with the "Also send" ticks:
// "Flooring Selection" → "Flooring Selection and Work Authorization Form"
document.addEventListener('change', function (e) {
    if (!e.target.matches('#request-signature-modal [data-also-include] input[type=checkbox]')) return;
    const modal   = document.getElementById('request-signature-modal');
    const base    = modal.dataset.docLabelBase;
    const labels  = [base].concat(
        Array.from(modal.querySelectorAll('[data-also-include] input:checked')).map(cb => cb.dataset.docLabel)
    );
    const joined  = labels.length > 1 ? labels.slice(0, -1).join(', ') + ' and ' + labels[labels.length - 1] : labels[0];
    const prev    = modal.dataset.docLabelJoined || base;
    modal.querySelectorAll('input[name="subject"], textarea[name="body"]').forEach(el => {
        el.value = el.value.split(prev).join(joined);
    });
    modal.dataset.docLabelJoined = joined;
});
</script>
