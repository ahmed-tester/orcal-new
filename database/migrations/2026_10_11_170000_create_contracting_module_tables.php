<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. جدول تصنيفات العقود وعروض الأسعار (Contract Categories)
        Schema::create('contract_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->longText('default_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });

        // إضافة ربط اختياري بتصنيف العقد في جدول عروض الأسعار
        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('contract_category_id')
                ->nullable()
                ->after('offer_type')
                ->constrained('contract_categories')
                ->nullOnDelete();
        });

        // 2. جدول الفواتير والمستخلصات (Contract Vouchers: القبض، الصرف، طلب الدفعة)
        Schema::create('contract_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('voucher_number')->unique();
            $table->string('voucher_type')->default('receipt'); // receipt, payment, request
            $table->date('voucher_date');
            $table->decimal('amount', 14, 2)->default(0);

            // العلاقات
            $table->foreignId('offer_id')->nullable()->constrained('offers')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            // طريقة الدفع
            $table->string('payment_method')->nullable(); // cash, check, bank_transfer

            // بيانات الدفع النقدي
            $table->string('delivered_by')->nullable();
            $table->string('received_by')->nullable();

            // بيانات الشيك
            $table->string('check_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('due_date')->nullable();

            // بيانات الحوالة البنكية
            $table->string('transfer_bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('reference_number')->nullable();

            // خانات مرنة (طلب الدفعة والعقود)
            $table->string('first_party_name')->nullable();
            $table->string('second_party_name')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('contract_date')->nullable();
            $table->text('contract_subject')->nullable();
            $table->text('payment_reason')->nullable();
            $table->text('notes')->nullable();

            // الحالة والربط بالخزينة
            $table->string('status')->default('draft'); // draft, approved, cancelled
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['company_id', 'voucher_type']);
            $table->index(['company_id', 'voucher_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_vouchers');

        Schema::table('offers', function (Blueprint $table) {
            $table->dropForeign(['contract_category_id']);
            $table->dropColumn('contract_category_id');
        });

        Schema::dropIfExists('contract_categories');
    }
};
