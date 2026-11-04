<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. asset_funds ───────────────────────────────────────────────
        Schema::create('asset_funds', function (Blueprint $table) {
            $table->id();
            $table->string('fund_number')->unique();
            $table->string('fund_name');
            $table->date('established_date');
            $table->text('description')->nullable();
            $table->longText('rules_and_conditions')->nullable();
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('manager_name')->nullable();
            $table->foreignId('accountant_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('accountant_name')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // ─── 2. asset_fund_vouchers ───────────────────────────────────────
        Schema::create('asset_fund_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_fund_id')->constrained('asset_funds')->cascadeOnDelete();
            $table->string('voucher_number')->unique();
            $table->string('type');           // deposit | withdrawal
            $table->date('voucher_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('source_type');    // company | free
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('party_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('asset_fund_id');
            $table->index('type');
            $table->index('voucher_date');
        });

        // ─── 3. asset_fund_voucher_items ─────────────────────────────────
        Schema::create('asset_fund_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_fund_voucher_id')->constrained('asset_fund_vouchers')->cascadeOnDelete();
            $table->string('description')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('extra_1', 15, 2)->nullable();
            $table->decimal('extra_2', 15, 2)->nullable();
            $table->decimal('row_total', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('asset_fund_voucher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_fund_voucher_items');
        Schema::dropIfExists('asset_fund_vouchers');
        Schema::dropIfExists('asset_funds');
    }
};

