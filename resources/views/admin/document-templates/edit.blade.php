<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Template</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $documentTemplate->name }}</p>
                </div>
                <a href="{{ route('admin.document-templates.index') }}"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                    Back
                </a>
            </div>

            {{-- Flash --}}
            @if (session('success'))
                <div class="mb-4 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                    <span class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</span>
                    <button onclick="this.closest('div').remove()" class="text-green-600 dark:text-green-400">✕</button>
                </div>
            @endif

            @if ($usageCount > 0)
                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                    This template has been used to generate <strong>{{ $usageCount }}</strong> document(s). Changes will only affect future generations.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.document-templates.update', $documentTemplate) }}" class="space-y-6">
                @csrf
                @method('PUT')
                @include('admin.document-templates._form', ['template' => $documentTemplate])

                <div class="flex items-center justify-end gap-3">
                    <button type="button" id="preview-btn"
                            class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        Preview
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>

    {{-- Preview Modal --}}
    <div id="preview-modal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 p-4">
        <div class="relative flex flex-col w-full max-w-4xl max-h-[90vh] bg-white rounded-xl shadow-xl overflow-hidden dark:bg-gray-800">

            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                <div>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">Template Preview</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Merge tags replaced with sample values</p>
                </div>
                <button type="button" id="preview-close"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-700 dark:hover:text-white">
                    ✕
                </button>
            </div>

            {{-- Body --}}
            <div class="flex-1 overflow-y-auto bg-gray-100 dark:bg-gray-900 p-6">
                {{-- Simulated page --}}
                <div class="max-w-2xl mx-auto bg-white shadow rounded-lg overflow-hidden">

                    {{-- Simulated PDF header --}}
                    <div style="border-bottom:2px solid #1d4ed8; padding:14px 24px 12px; display:flex; justify-content:space-between; align-items:flex-start;">
                        <div>
                            <div style="font-size:18px; font-weight:700; letter-spacing:.3px;">RM Flooring</div>
                        </div>
                        <div style="text-align:right;">
                            <div id="preview-template-name" style="font-size:15px; font-weight:700;"></div>
                            <div style="font-size:11px; color:#6b7280; margin-top:2px;">Generated {{ now()->format('M j, Y') }} &nbsp;&middot;&nbsp; Job #JOB-001</div>
                        </div>
                    </div>

                    {{-- Rendered body --}}
                    <div id="preview-body" style="padding:24px; font-family:sans-serif; font-size:13px; line-height:1.6; color:#1a1a1a;"></div>

                    {{-- Simulated PDF footer --}}
                    <div style="border-top:1px solid #e5e7eb; padding:8px 24px; display:flex; justify-content:space-between; font-size:10px; color:#9ca3af;">
                        <span>RM Flooring &nbsp;&middot;&nbsp; (604) 555-0100</span>
                        <span id="preview-footer-name"></span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
    const _previewDate = '{{ now()->format("F j, Y") }}';
    </script>
    @verbatim
    <script>
    (function () {
        const PLACEHOLDERS = {
            '{{customer_name}}':      'John Smith',
            '{{job_name}}':           'Master Bedroom Renovation',
            '{{job_no}}':             'JOB-2024-001',
            '{{job_site_name}}':      'Smith Residence',
            '{{job_site_address}}':   '123 Main Street\nAnytown, BC  V1V 1V1',
            '{{job_site_phone}}':     '(604) 555-0123',
            '{{job_site_email}}':     'john.smith@example.com',
            '{{pm_name}}':            'Sarah Johnson',
            '{{pm_first_name}}':      'Sarah',
            '{{pm_phone}}':           '(604) 555-0199',
            '{{pm_email}}':           'sarah@rmflooring.ca',
            '{{insurance_company}}':  'Intact Insurance',
            '{{adjuster}}':           'Mike Johnson',
            '{{policy_number}}':      'POL-2024-98765',
            '{{claim_number}}':       'CLM-2024-00123',
            '{{dol}}':                'Jan 15, 2024',
            '{{date}}':               _previewDate,
            '{{generated_by}}':       'Admin User',
            '{{special_instructions}}': 'Check subfloor condition before installation. Use moisture barrier in bathroom areas.',
            '{{notes}}':              'Customer prefers afternoon appointments.\nDog on premises — please confirm entry.',
            '{{opportunity_photos_qr}}': '<div style="display:inline-block;width:100px;height:100px;border:2px solid #d1d5db;border-radius:4px;background:#f9fafb;display:flex;align-items:center;justify-content:center;text-align:center;font-size:10px;color:#6b7280;padding:8px;">📷 Photo Gallery QR</div>',
            '{{opportunity_qr}}': '<div style="display:inline-block;width:100px;height:100px;border:2px solid #d1d5db;border-radius:4px;background:#f9fafb;display:flex;align-items:center;justify-content:center;text-align:center;font-size:10px;color:#6b7280;padding:8px;">📋 Opportunity QR</div>',
            '{{sale_number}}':        '2024-0042',
            '{{flooring_items_table}}': `<table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead>
                    <tr style="background:#1d4ed8;color:#fff;">
                        <th style="padding:6px 10px;text-align:left;border:1px solid #1e40af;">Room</th>
                        <th style="padding:6px 10px;text-align:left;border:1px solid #1e40af;">Product</th>
                        <th style="padding:6px 10px;text-align:center;border:1px solid #1e40af;">Qty</th>
                        <th style="padding:6px 10px;text-align:left;border:1px solid #1e40af;">Unit</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;" rowspan="2">Master Bedroom</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">Hardwood — Mirage — Maple — Natural #4201</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;text-align:center;">45</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">sq ft</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">Underlay — Roberts — 500 Series</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;text-align:center;">45</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">sq ft</td>
                    </tr>
                    <tr>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">Hallway</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">Hardwood — Mirage — Maple — Natural #4201</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;text-align:center;">12</td>
                        <td style="padding:6px 10px;border:1px solid #e5e7eb;">sq ft</td>
                    </tr>
                </tbody>
            </table>`,
        };

        function renderPreview() {
            const nameInput = document.querySelector('input[name="name"]');
            const bodyTextarea = document.querySelector('textarea[name="body"]');

            let body = bodyTextarea ? bodyTextarea.value : '';
            const name = nameInput ? nameInput.value : '';

            // Replace all known tags
            Object.entries(PLACEHOLDERS).forEach(([tag, val]) => {
                body = body.split(tag).join(val);
            });

            // Replace any remaining unknown {{tags}} with a styled placeholder
            body = body.replace(/\{\{(\w+)\}\}/g, (_, tag) =>
                `<span style="background:#fef9c3;border:1px solid #fcd34d;border-radius:3px;padding:0 4px;font-size:11px;color:#92400e;">[${tag}]</span>`
            );

            document.getElementById('preview-body').innerHTML = body;
            document.getElementById('preview-template-name').textContent = name;
            document.getElementById('preview-footer-name').textContent = name;
        }

        const modal  = document.getElementById('preview-modal');
        const openBtn  = document.getElementById('preview-btn');
        const closeBtn = document.getElementById('preview-close');

        openBtn.addEventListener('click', () => {
            renderPreview();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
    })();
    </script>
    @endverbatim

</x-app-layout>
