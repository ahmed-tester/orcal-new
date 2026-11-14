<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unified practical financial workflow tables.
 *
 * The backbone is shared between modules through a polymorphic source
 * (source_type, source_id). Services is the first registered source.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Unified collection receipts.
        Schema::create('financial_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('receipt_number', 50)->unique();
            $table->string('request_key', 100)->unique();
            $table->foreignId('collector_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('payer_name')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->string('status', 30)->default('pending_confirmation');
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_path')->nullable();
            $table->string('destination_type', 30)->nullable();
            $table->string('destination_reference', 100)->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('received_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'fin_rec_co_status_idx');
            $table->index(['collector_employee_id', 'status'], 'fin_rec_collector_idx');
            $table->index(['source_type', 'source_id'], 'fin_rec_source_idx');
        });

        // 2. Cash handover vouchers to the treasury.
        Schema::create('cash_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('handover_number', 50)->unique();
            $table->string('request_key', 100)->unique();
            $table->string('confirmation_key', 100)->nullable()->unique();
            $table->foreignId('collector_employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('delivered_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('total_requested_amount', 14, 2)->default(0);
            $table->decimal('total_confirmed_amount', 14, 2)->nullable();
            $table->decimal('difference_amount', 14, 2)->nullable();
            $table->string('status', 30)->default('submitted');
            $table->foreignId('treasury_transaction_id')->nullable()->unique()->constrained('treasury_transactions')->nullOnDelete();
            $table->dateTime('requested_at');
            $table->dateTime('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'fin_handover_co_status_idx');
            $table->index(['collector_employee_id', 'status'], 'fin_handover_collector_idx');
        });

        // 3. Handover items: requested vs confirmed per receipt.
        Schema::create('cash_handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_handover_id')->constrained('cash_handovers')->cascadeOnDelete();
            $table->foreignId('financial_receipt_id')->constrained('financial_receipts')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->decimal('requested_amount', 14, 2)->default(0);
            $table->decimal('confirmed_amount', 14, 2)->default(0);
            $table->dateTime('confirmed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['cash_handover_id', 'financial_receipt_id'], 'fin_handover_item_uq');
            $table->index('financial_receipt_id', 'fin_handover_item_rec_idx');
            $table->index('company_id', 'fin_handover_item_co_idx');
        });

        // 4. Fee settlement / employee personal payment claims.
        Schema::create('financial_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('disbursement_number', 50)->unique();
            $table->string('request_key', 100)->unique();
            $table->string('disbursement_type', 20);
            $table->decimal('amount', 14, 2);
            $table->string('beneficiary_name')->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('receipt_proof')->nullable();
            $table->string('status', 30)->default('paid');
            $table->foreignId('paid_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('treasury_transaction_id')->nullable()->unique()->constrained('treasury_transactions')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id'], 'fin_disb_source_idx');
            $table->index(['company_id', 'status'], 'fin_disb_co_status_idx');
            $table->index(['company_id', 'disbursement_type'], 'fin_disb_co_type_idx');
        });

        // 5. Partial reimbursement payments for approved personal claims.
        Schema::create('financial_disbursement_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_disbursement_id')->constrained('financial_disbursements', indexName: 'fin_disb_pay_disb_fk')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('payment_number', 50)->unique();
            $table->string('request_key', 100)->unique();
            $table->decimal('amount', 14, 2);
            $table->foreignId('treasury_transaction_id')->nullable()->unique()->constrained('treasury_transactions')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['financial_disbursement_id', 'paid_at'], 'fin_disb_pay_disb_idx');
            $table->index(['company_id', 'paid_at'], 'fin_disb_pay_co_idx');
        });

        // 6. Stable task keys prevent duplicated deficiency tasks per source.
        if (! Schema::hasColumn('unified_deficiency_tasks', 'task_key')) {
            Schema::table('unified_deficiency_tasks', function (Blueprint $table) {
                $table->string('task_key', 191)->nullable()->unique('def_tasks_task_key_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasColumn('unified_deficiency_tasks', 'task_key')) {
            Schema::table('unified_deficiency_tasks', function (Blueprint $table) {
                $table->dropUnique('def_tasks_task_key_uq');
                $table->dropColumn('task_key');
            });
        }

        Schema::dropIfExists('financial_disbursement_payments');
        Schema::dropIfExists('financial_disbursements');
        Schema::dropIfExists('cash_handover_items');
        Schema::dropIfExists('cash_handovers');
        Schema::dropIfExists('financial_receipts');

        Schema::enableForeignKeyConstraints();
    }
};
