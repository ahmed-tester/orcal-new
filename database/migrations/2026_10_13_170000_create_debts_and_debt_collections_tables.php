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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->string('debt_number', 50)->unique();
            $table->date('debt_date');
            $table->date('due_date')->nullable();
            $table->string('debtor_type', 20)->default('internal'); // internal, external
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('external_name', 255)->nullable();
            $table->string('external_national_id', 50)->nullable();
            $table->string('external_phone', 50)->nullable();
            $table->string('external_email', 100)->nullable();
            $table->text('external_address')->nullable();
            $table->foreignId('guarantor_id')->nullable()->constrained('guarantors')->nullOnDelete();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('debt_reason', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending'); // pending, partial, paid, overdue
            $table->foreignId('notify_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('notification_notes')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['debtor_type', 'status']);
            $table->index('due_date');
        });

        Schema::create('debt_collections', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number', 50)->unique();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('collection_date');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method', 30)->default('cash'); // cash, bank_transfer, cheque
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->text('description')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['debt_id', 'collection_date']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_collections');
        Schema::dropIfExists('debts');
    }
};
