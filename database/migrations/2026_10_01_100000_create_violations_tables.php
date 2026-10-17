<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Violation Categories
        Schema::create('violation_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Violation Types
        Schema::create('violation_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('violation_category_id')
                  ->constrained('violation_categories')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Violations Register
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->string('violation_number')->nullable();

            // Company
            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // Classification
            $table->foreignId('violation_category_id')
                  ->constrained('violation_categories');
            $table->foreignId('violation_type_id')
                  ->constrained('violation_types');

            // Violation Details
            $table->string('external_violation_number')->nullable();
            $table->date('violation_date');
            $table->string('issuing_authority');
            $table->decimal('violation_amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();

            // Stage Deadlines
            $table->date('correction_deadline')->nullable();
            $table->date('objection_deadline')->nullable();
            $table->date('grace_deadline')->nullable();
            $table->date('legal_deadline')->nullable();
            $table->date('admin_execution_date')->nullable();

            // Current Stage and Status
            $table->string('stage')->default('correction');
            $table->string('payment_status')->default('pending');

            // Payment Amounts
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->date('closure_date')->nullable();

            // Recorder
            $table->foreignId('recorded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();
        });

        // 4. Violation Treasury Transactions
        Schema::create('violation_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->nullable();
            $table->date('transaction_date');
            $table->foreignId('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();
            $table->foreignId('violation_id')
                  ->constrained('violations')
                  ->cascadeOnDelete();
            $table->string('transaction_type');
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();
            $table->foreignId('executed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->timestamps();
        });

        // 5. Violation Regulations
        Schema::create('violation_regulations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->longText('content');
            $table->longText('content_en')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('violation_regulations');
        Schema::dropIfExists('violation_transactions');
        Schema::dropIfExists('violations');
        Schema::dropIfExists('violation_types');
        Schema::dropIfExists('violation_categories');
    }
};
