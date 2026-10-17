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
        Schema::create('service_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->date('transaction_date');
            $table->string('scope_type', 20)->default('internal'); // internal, external
            $table->string('service_target', 20)->nullable(); // company, employee
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('service_name', 255)->nullable();
            $table->string('service_type', 100)->nullable();
            $table->string('first_party', 255)->nullable();
            $table->string('beneficiary_name', 255)->nullable();
            $table->string('beneficiary_phone', 50)->nullable();
            $table->string('beneficiary_national_id', 50)->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid'); // unpaid, partial, paid
            $table->string('service_status', 20)->default('active'); // active, completed, inactive
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'service_status']);
            $table->index(['scope_type', 'service_status']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_transactions');
    }
};
