<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('body');
            $table->boolean('needs_sale')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Seed three starter templates
        $now = now();

        DB::table('document_templates')->insert([
            [
                'name'        => 'Front File Label',
                'description' => 'Printed label for the front of physical job folders.',
                'body'        => "<div style=\"text-align:center; padding:20px; border:2px solid #000;\">\n<div style=\"font-size:28px; font-weight:bold; margin-bottom:10px;\">{{customer_name}}</div>\n<div style=\"font-size:18px; margin-bottom:6px;\">Job #{{job_no}}</div>\n<div style=\"font-size:14px; margin-bottom:6px;\">{{job_name}}</div>\n<hr style=\"margin:12px 0;\">\n<div style=\"font-size:13px;\">{{job_site_address}}</div>\n<div style=\"font-size:13px; margin-top:6px;\">PM: {{pm_name}}</div>\n<div style=\"font-size:11px; color:#555; margin-top:12px;\">{{date}}</div>\n</div>",
                'needs_sale'  => false,
                'is_active'   => true,
                'sort_order'  => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Flooring Selection Sign-Off',
                'description' => 'Lists all material items from a sale by room for the customer to sign off on.',
                'body'        => "<h2 style=\"margin-bottom:4px;\">Flooring Selection Sign-Off</h2>\n<p style=\"color:#555; margin-bottom:16px;\">Sale #{{sale_number}} &mdash; {{customer_name}}</p>\n\n<table style=\"width:100%; margin-bottom:8px;\">\n  <tr><td><strong>Job Site:</strong> {{job_site_name}}</td><td><strong>Address:</strong> {{job_site_address}}</td></tr>\n  <tr><td><strong>PM:</strong> {{pm_name}}</td><td><strong>Date:</strong> {{date}}</td></tr>\n</table>\n\n<p style=\"margin:12px 0 8px;\">The following flooring products have been selected for this project:</p>\n\n{{flooring_items_table}}\n\n<div style=\"margin-top:40px;\">\n<table style=\"width:100%;\">\n  <tr>\n    <td style=\"width:45%; border-top:1px solid #000; padding-top:6px;\">Customer Signature</td>\n    <td style=\"width:10%;\"></td>\n    <td style=\"width:45%; border-top:1px solid #000; padding-top:6px;\">Date</td>\n  </tr>\n</table>\n<p style=\"margin-top:24px; font-size:10px; color:#777;\">By signing above, the customer confirms the flooring selections listed are correct and approved to proceed.</p>\n</div>",
                'needs_sale'  => true,
                'is_active'   => true,
                'sort_order'  => 2,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'name'        => 'Work Authorization Form',
                'description' => 'Authorization form for the customer to approve work to proceed.',
                'body'        => "<h2 style=\"margin-bottom:4px;\">Work Authorization</h2>\n<p style=\"color:#555; margin-bottom:16px;\">{{date}}</p>\n\n<table style=\"width:100%; margin-bottom:16px;\">\n  <tr><td style=\"width:50%;\"><strong>Customer:</strong> {{customer_name}}</td><td><strong>Job #:</strong> {{job_no}}</td></tr>\n  <tr><td><strong>Job Site:</strong> {{job_site_name}}</td><td><strong>PM:</strong> {{pm_name}}</td></tr>\n  <tr><td colspan=\"2\"><strong>Address:</strong> {{job_site_address}}</td></tr>\n  <tr><td><strong>Phone:</strong> {{job_site_phone}}</td><td><strong>Email:</strong> {{job_site_email}}</td></tr>\n</table>\n\n<p style=\"margin-bottom:8px;\">I, the undersigned, hereby authorize RM Flooring to proceed with the flooring installation work as discussed and agreed upon for the above-referenced property.</p>\n\n<p style=\"margin-bottom:16px;\">I understand that this authorization confirms my agreement to the scope of work, materials selected, and associated costs as outlined in the accompanying documentation.</p>\n\n<div style=\"margin-top:40px;\">\n<table style=\"width:100%;\">\n  <tr>\n    <td style=\"width:45%; border-top:1px solid #000; padding-top:6px;\">Customer Signature</td>\n    <td style=\"width:10%;\"></td>\n    <td style=\"width:45%; border-top:1px solid #000; padding-top:6px;\">Date</td>\n  </tr>\n  <tr style=\"margin-top:24px;\">\n    <td style=\"padding-top:20px; border-top:1px solid #000;\">Print Name</td>\n    <td></td>\n    <td style=\"padding-top:20px; border-top:1px solid #000;\">RM Flooring Representative</td>\n  </tr>\n</table>\n</div>",
                'needs_sale'  => false,
                'is_active'   => true,
                'sort_order'  => 3,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
