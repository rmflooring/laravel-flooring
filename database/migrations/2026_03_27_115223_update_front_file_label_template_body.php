<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $body = <<<'HTML'
<div style="font-size:12px; line-height:1.5; color:#111;">

  <!-- Blue header -->
  <div style="background:#1d4ed8; color:#fff; padding:10px 16px; border-radius:4px 4px 0 0;">
    <div style="font-size:24px; font-weight:700; letter-spacing:0.5px;">Job #{{job_no}}</div>
    <div style="font-size:14px; margin-top:3px; opacity:.9;">{{customer_name}}</div>
  </div>

  <!-- Job name strip -->
  <div style="background:#eff6ff; border:1px solid #bfdbfe; border-top:none; border-radius:0 0 4px 4px; padding:6px 16px; font-size:12px; color:#1e40af; font-weight:600; margin-bottom:14px;">
    {{job_name}}
  </div>

  <!-- Job Site + PM -->
  <table style="width:100%; border-collapse:collapse;">
    <tr>
      <td style="width:50%; vertical-align:top; padding-right:8px;">
        <p style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin:0 0 5px;">Job Site</p>
        <div style="border:1px solid #e5e7eb; border-radius:4px; padding:10px 12px;">
          <div style="font-weight:600; margin-bottom:3px;">{{job_site_name}}</div>
          <div style="white-space:pre-line; color:#374151; margin-bottom:3px;">{{job_site_address}}</div>
          <div style="color:#6b7280;">{{job_site_phone}}</div>
          <div style="color:#6b7280;">{{job_site_email}}</div>
        </div>
      </td>
      <td style="width:50%; vertical-align:top; padding-left:8px;">
        <p style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin:0 0 5px;">Project Manager</p>
        <div style="border:1px solid #e5e7eb; border-radius:4px; padding:10px 12px;">
          <div style="font-weight:600; margin-bottom:3px;">{{pm_name}}</div>
          <div style="color:#6b7280;">{{pm_phone}}</div>
          <div style="color:#6b7280;">{{pm_email}}</div>
        </div>
      </td>
    </tr>
  </table>

  <!-- Measure Details -->
  <div style="margin-top:12px; border:1px solid #e5e7eb; border-radius:4px; padding:10px 12px;">
    <p style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin:0 0 8px;">Measure Details</p>
    <table style="width:100%; border-collapse:collapse;">
      <tr>
        <td style="width:50%; vertical-align:top; padding-bottom:8px; padding-right:8px;">
          <div style="font-size:10px; color:#6b7280; margin-bottom:2px;">Estimator</div>
          <div style="border-bottom:1px solid #d1d5db; min-height:18px;">&nbsp;</div>
        </td>
        <td style="width:50%; vertical-align:top; padding-bottom:8px; padding-left:8px;">
          <div style="font-size:10px; color:#6b7280; margin-bottom:2px;">Flooring Type</div>
          <div style="border-bottom:1px solid #d1d5db; min-height:18px;">&nbsp;</div>
        </td>
      </tr>
      <tr>
        <td style="vertical-align:top; padding-right:8px;">
          <div style="font-size:10px; color:#6b7280; margin-bottom:2px;">Scheduled Date &amp; Time</div>
          <div style="border-bottom:1px solid #d1d5db; min-height:18px;">&nbsp;</div>
        </td>
        <td style="vertical-align:top; padding-left:8px;">
          <div style="font-size:10px; color:#6b7280; margin-bottom:2px;">Completed Date</div>
          <div style="border-bottom:1px solid #d1d5db; min-height:18px;">&nbsp;</div>
        </td>
      </tr>
    </table>
  </div>

  <!-- Special Instructions -->
  <div style="margin-top:12px; border:1px solid #fde68a; background:#fffbeb; border-radius:4px; padding:10px 12px; min-height:50px;">
    <p style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#92400e; margin:0 0 4px;">Special Instructions</p>
  </div>

  <!-- Notes -->
  <div style="margin-top:12px; border:1px solid #e5e7eb; border-radius:4px; padding:10px 12px; min-height:70px;">
    <p style="font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6b7280; margin:0 0 4px;">Notes</p>
  </div>

  <div style="margin-top:10px; font-size:10px; color:#9ca3af; text-align:right;">Generated {{date}} &nbsp;&middot;&nbsp; {{generated_by}}</div>
</div>
HTML;

        DB::table('document_templates')
            ->where('name', 'Front File Label')
            ->update(['body' => $body, 'updated_at' => now()]);
    }

    public function down(): void
    {
        $body = "<div style=\"text-align:center; padding:20px; border:2px solid #000;\">\n<div style=\"font-size:28px; font-weight:bold; margin-bottom:10px;\">{{customer_name}}</div>\n<div style=\"font-size:18px; margin-bottom:6px;\">Job #{{job_no}}</div>\n<div style=\"font-size:14px; margin-bottom:6px;\">{{job_name}}</div>\n<hr style=\"margin:12px 0;\">\n<div style=\"font-size:13px;\">{{job_site_address}}</div>\n<div style=\"font-size:13px; margin-top:6px;\">PM: {{pm_name}}</div>\n<div style=\"font-size:11px; color:#555; margin-top:12px;\">{{date}}</div>\n</div>";

        DB::table('document_templates')
            ->where('name', 'Front File Label')
            ->update(['body' => $body, 'updated_at' => now()]);
    }
};
