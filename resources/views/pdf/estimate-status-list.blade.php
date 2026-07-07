<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10px;
        color: #1a1a1a;
        padding: 36px 48px;
        line-height: 1.5;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e5e7eb;
    }

    .company-name {
        font-size: 18px;
        font-weight: bold;
        color: #111827;
        margin-bottom: 2px;
    }

    .company-sub {
        font-size: 9px;
        color: #6b7280;
    }

    .doc-title {
        font-size: 20px;
        font-weight: bold;
        color: #0f766e;
        text-align: right;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .doc-meta {
        font-size: 9px;
        color: #6b7280;
        text-align: right;
        margin-top: 4px;
    }

    .intro {
        font-size: 10px;
        color: #374151;
        margin-bottom: 20px;
        line-height: 1.6;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    thead th {
        background: #f3f4f6;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #6b7280;
        padding: 7px 10px;
        border-bottom: 1px solid #d1d5db;
        text-align: left;
    }

    thead th.right { text-align: right; }

    tbody tr {
        border-bottom: 1px solid #e5e7eb;
    }

    tbody tr:nth-child(even) {
        background: #f9fafb;
    }

    tbody td {
        padding: 7px 10px;
        color: #374151;
        vertical-align: top;
    }

    tbody td.right { text-align: right; }
    tbody td.mono  { font-family: 'DejaVu Sans Mono', monospace; }

    .badge-sent {
        display: inline-block;
        background: #dcfce7;
        color: #166534;
        font-size: 8px;
        padding: 1px 5px;
        border-radius: 3px;
    }

    .badge-unsent {
        display: inline-block;
        background: #f3f4f6;
        color: #6b7280;
        font-size: 8px;
        padding: 1px 5px;
        border-radius: 3px;
    }

    .footer {
        margin-top: 24px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
        font-size: 9px;
        color: #9ca3af;
        display: flex;
        justify-content: space-between;
    }

    .notes-col {
        width: 120px;
        border-left: 2px solid #e5e7eb;
        color: #9ca3af;
        font-style: italic;
    }
</style>
</head>
<body>

    <div class="header">
        <div>
            <div class="company-name">RM Flooring</div>
            <div class="company-sub">Estimate Status Update Request</div>
        </div>
        <div>
            <div class="doc-title">Estimate Status List</div>
            <div class="doc-meta">Generated: {{ now()->format('F j, Y') }} &nbsp;|&nbsp; {{ $estimates->count() }} estimate{{ $estimates->count() !== 1 ? 's' : '' }}</div>
        </div>
    </div>

    <div class="intro">
        The following estimates are currently open and have not yet been converted to a sale. Please review and provide a status update for each job listed below.
    </div>

    <table>
        <thead>
            <tr>
                <th>Estimate #</th>
                <th>Job Name</th>
                <th>Customer</th>
                <th>Homeowner</th>
                <th>Project Manager</th>
                <th>Estimator</th>
                <th class="right">Value</th>
                <th>Sent</th>
                <th>Created</th>
                <th class="notes-col">Notes / Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($estimates as $estimate)
                <tr>
                    <td class="mono">{{ $estimate->estimate_number }}</td>
                    <td>{{ $estimate->job_name ?? '—' }}</td>
                    <td>{{ $estimate->customer_name ?? '—' }}</td>
                    <td>{{ $estimate->homeowner_name ?? '—' }}</td>
                    <td>{{ $estimate->pm_name ?? '—' }}</td>
                    <td>{{ $estimate->creator?->name ?? '—' }}</td>
                    <td class="right">${{ number_format($estimate->grand_total, 2) }}</td>
                    <td>
                        @if($estimate->first_sent_at)
                            <span class="badge-sent">{{ $estimate->first_sent_at->format('M j, Y') }}</span>
                        @else
                            <span class="badge-unsent">Not sent</span>
                        @endif
                    </td>
                    <td>{{ $estimate->created_at->format('M j, Y') }}</td>
                    <td class="notes-col">&nbsp;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align:center; padding: 20px; color: #9ca3af;">No estimates found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <span>RM Flooring &mdash; Confidential</span>
        <span>{{ now()->format('Y-m-d H:i') }}</span>
    </div>

</body>
</html>
