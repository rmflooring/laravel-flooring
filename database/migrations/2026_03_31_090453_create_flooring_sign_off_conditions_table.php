<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flooring_sign_off_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();

        DB::table('flooring_sign_off_conditions')->insert([
            [
                'title'      => 'Standard Installation Terms',
                'body'       => "All flooring materials and installation work are subject to the following conditions:\n\n1. Prices are valid for 30 days from the date of this document.\n2. A deposit of 50% is required to confirm the order. The remaining balance is due upon completion.\n3. RM Flooring is not responsible for pre-existing subfloor conditions that may affect installation quality.\n4. Customer is responsible for removing all furniture prior to installation unless otherwise agreed in writing.\n5. Minor variations in colour, texture, or shade are inherent characteristics of natural flooring materials and are not considered defects.",
                'sort_order' => 1,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title'      => 'Payment Terms',
                'body'       => "Payment is due as follows:\n\n• 50% deposit required at time of order confirmation.\n• Remaining balance due upon completion of installation.\n• Accepted payment methods: cheque, e-transfer, or cash.\n• Overdue accounts are subject to a 2% monthly interest charge.\n\nAll materials remain the property of RM Flooring until payment is received in full.",
                'sort_order' => 2,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title'      => 'Insurance / Damage Claim Terms',
                'body'       => "This work order is being completed as part of an insurance or damage claim. The following conditions apply:\n\n1. All materials selected are subject to insurer approval. RM Flooring is not responsible for adjustments required by the insurer after selection has been made.\n2. Payment is to be arranged directly between the insurer and the customer. RM Flooring invoices the customer; the customer is responsible for ensuring insurance proceeds are applied.\n3. Scope of work is limited to areas identified in the claim. Any additional work identified during installation will be quoted separately.",
                'sort_order' => 3,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('flooring_sign_off_conditions');
    }
};
