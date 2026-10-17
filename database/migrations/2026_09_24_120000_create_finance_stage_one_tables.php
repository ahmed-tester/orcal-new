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
        // 1. بنود الدفع والتحصيل (Payment & Collection Items)
        Schema::create('payment_collection_items', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('main_item');
            $table->string('sub_item');
            $table->decimal('service_price', 12, 2)->default(0);
            $table->timestamps();

            $table->index('company_id');
            $table->index('main_item');
            $table->index('sub_item');
        });

        // 2. أجهزة وشبكات نقاط البيع (POS Devices & Networks)
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('commercial_registration_number')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->string('owner_identity_number')->nullable();
            $table->string('owner_phone')->nullable();
            $table->string('device_number');
            $table->string('card_number'); // رقم البطاقة MID
            $table->string('bank_or_wallet_name')->nullable(); // اسم البنك أو المحفظة
            $table->string('account_number');
            $table->foreignId('accountant_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('accountant_name')->nullable();
            $table->string('accountant_identity_number')->nullable();
            $table->string('accountant_email')->nullable();
            $table->string('accountant_phone')->nullable();
            $table->text('accountant_password')->nullable(); // مشفر أو محمي
            $table->string('status')->default('active'); // active, temporarily_disabled, inactive
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('device_number');
            $table->index('card_number');
            $table->index('status');
        });

        // 3. الخزينة ومستندات الصرف والقبض (Treasury Transactions & Vouchers)
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->string('voucher_number')->nullable()->index();
            $table->date('transaction_date');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('transaction_type'); // incoming, outgoing
            $table->foreignId('payment_collection_item_id')->constrained('payment_collection_items')->restrictOnDelete();
            $table->string('recipient_or_payer')->nullable();
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['company_id', 'transaction_date']);
            $table->index(['company_id', 'transaction_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
        Schema::dropIfExists('pos_devices');
        Schema::dropIfExists('payment_collection_items');
    }
};
