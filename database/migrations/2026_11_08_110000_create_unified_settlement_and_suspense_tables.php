<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('unified_batch_deposit_items');
        Schema::dropIfExists('unified_batch_deposits');
        Schema::dropIfExists('unified_suspense_amounts');
        Schema::enableForeignKeyConstraints();

        // 1. Unified batch bank deposits table
        Schema::create('unified_batch_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('deposit_number', 50)->unique();
            $table->date('deposit_date');
            $table->string('bank_name', 100);
            $table->string('reference_number', 100);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('receipt_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'deposit_date'], 'batch_dep_co_date_idx');
        });

        // 2. Pivot items linking transactions to batch deposits
        Schema::create('unified_batch_deposit_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_deposit_id')
                ->constrained('unified_batch_deposits')
                ->cascadeOnDelete();
            $table->nullableMorphs('depositable', 'bdep_items_depositable_idx');
            $table->foreignId('service_transaction_id')
                ->nullable()
                ->constrained('service_transactions')
                ->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            // Explicit short index name to adhere strictly to MySQL 64-char limit
            $table->index(['batch_deposit_id', 'service_transaction_id'], 'bdep_items_dep_tx_idx');
        });

        // 3. Suspense and unidentified amounts table
        Schema::create('unified_suspense_amounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('suspense_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->date('received_date');
            $table->string('bank_name', 100)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('depositor_name')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 30)->default('pending');
            $table->nullableMorphs('allocatable', 'susp_allocatable_idx');
            $table->unsignedBigInteger('allocated_service_transaction_id')->nullable();
            $table->foreign('allocated_service_transaction_id', 'susp_allocated_tx_fk')
                ->references('id')
                ->on('service_transactions')
                ->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'susp_co_status_idx');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('unified_suspense_amounts');
        Schema::dropIfExists('unified_batch_deposit_items');
        Schema::dropIfExists('unified_batch_deposits');
        Schema::enableForeignKeyConstraints();
    }
};
