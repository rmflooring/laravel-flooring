<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conditions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Migrate existing flooring sign-off conditions into the new general table
        $existing = DB::table('flooring_sign_off_conditions')->orderBy('sort_order')->get();
        foreach ($existing as $row) {
            DB::table('conditions')->insert([
                'id'         => $row->id,
                'title'      => $row->title,
                'body'       => $row->body,
                'sort_order' => $row->sort_order,
                'is_active'  => $row->is_active,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        // Re-point flooring_sign_offs.condition_id FK to the new conditions table
        Schema::table('flooring_sign_offs', function (Blueprint $table) {
            $table->dropForeign(['condition_id']);
            $table->foreign('condition_id')->references('id')->on('conditions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flooring_sign_offs', function (Blueprint $table) {
            $table->dropForeign(['condition_id']);
            $table->foreign('condition_id')->references('id')->on('flooring_sign_off_conditions')->nullOnDelete();
        });

        Schema::dropIfExists('conditions');
    }
};
