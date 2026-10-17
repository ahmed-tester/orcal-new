<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('avatar_url')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('user_type')->default('employee');
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->unsignedBigInteger('job_title_id')->nullable()->index();
            $table->string('employee_number')->nullable()->unique();
            $table->string('employment_type')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('bank_iban')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('daily_work_hours', 4, 1)->nullable();
            $table->decimal('weekly_work_hours', 5, 1)->nullable();
            $table->string('contract_duration')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('contract_file')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('employee_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('nationality')->nullable();
            $table->string('national_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            $table->string('iqama_number')->nullable();
            $table->date('iqama_expiry')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('work_permit_expiry')->nullable();
            $table->string('work_permit_number')->nullable();
            $table->string('sponsor_name')->nullable();
            $table->date('last_leave_return_date')->nullable();

            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();

            $table->string('iqama_file')->nullable();
            $table->string('passport_file')->nullable();

            $table->timestamps();
        });

        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('nationality')->nullable();
            $table->string('national_id')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('employment_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('image')->nullable();
            $table->text('address')->nullable();
            $table->decimal('ownership_percentage', 5, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owners');
        Schema::dropIfExists('employee_details');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
