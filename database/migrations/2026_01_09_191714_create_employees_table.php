<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Your manual “Employee ID”
            $table->string('employee_number')->unique(); // you enter this

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();

            $table->date('date_of_birth')->nullable();   // yyyy-mm-dd

            // Address (split = easier searching later)
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();

            // SIN (encrypted storage) + helper fields for UX
            $table->text('sin_encrypted')->nullable();
            $table->string('sin_last4', 4)->nullable();
            $table->boolean('sin_on_file')->default(false);

            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            $table->date('hire_date')->nullable();       // yyyy-mm-dd
            $table->string('job_title')->nullable();

            // Admin-manageable lists
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('employee_role_id')->nullable()->constrained('employee_roles')->nullOnDelete();

            // If role is “Other”
            $table->string('role_other')->nullable();

            // Status
            $table->enum('status', ['active','inactive','terminated','on_leave','archived'])->default('active');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
