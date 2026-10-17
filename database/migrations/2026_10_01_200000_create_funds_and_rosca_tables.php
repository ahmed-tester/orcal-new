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
        // ==========================================
        // 1. Loan Funds (Salifni Fund)
        // ==========================================

        // 1.1 Loan Funds Core Table
        Schema::create('loan_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('fund_number')->nullable()->unique();
            $table->string('fund_name');
            $table->decimal('target_amount', 14, 2)->default(0);
            $table->string('installment_period')->default('monthly'); // monthly, yearly
            $table->unsignedInteger('default_installments_count')->default(12);
            $table->decimal('max_loan_limit', 14, 2)->default(0);
            $table->unsignedInteger('max_repayment_months')->default(12);
            $table->string('status')->default('active'); // active, completed, suspended
            $table->foreignId('trustee_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('trustee_name')->nullable();
            $table->longText('rules_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        // 1.2 Loan Fund Members
        Schema::create('loan_fund_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_fund_id')->constrained('loan_funds')->cascadeOnDelete();
            $table->string('member_type')->default('external'); // employee, external
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('full_name');
            $table->string('national_id', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('installments_count')->default(12);
            $table->decimal('monthly_installment', 14, 2)->default(0);
            $table->decimal('total_annual_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // paid, partial, unpaid
            $table->date('joined_date')->nullable();
            $table->string('status')->default('active'); // active, inactive
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_fund_id', 'payment_status']);
            $table->index(['national_id', 'phone']);
        });

        // 1.3 Loan Fund Member Subscription Payments
        Schema::create('loan_fund_member_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_fund_id')->constrained('loan_funds')->cascadeOnDelete();
            $table->foreignId('loan_fund_member_id')->constrained('loan_fund_members')->cascadeOnDelete();
            $table->string('voucher_number')->nullable()->unique();
            $table->date('payment_date');
            $table->date('period_month')->nullable();
            $table->unsignedInteger('installment_order')->default(1);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, bank_transfer, payroll_deduction
            $table->string('payment_status')->default('paid'); // paid, partial, unpaid
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_fund_id', 'period_month']);
            $table->index(['loan_fund_member_id', 'payment_date']);
        });

        // 1.4 Loan Fund Loans
        Schema::create('loan_fund_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_fund_id')->constrained('loan_funds')->cascadeOnDelete();
            $table->foreignId('loan_fund_member_id')->constrained('loan_fund_members')->cascadeOnDelete();
            $table->string('loan_number')->nullable()->unique();
            $table->decimal('loan_amount', 14, 2)->default(0);
            $table->date('loan_date');
            $table->date('start_due_date');
            $table->unsignedInteger('installments_count')->default(1);
            $table->decimal('monthly_installment', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->string('status')->default('active'); // active, completed, defaulted
            $table->boolean('is_closed')->default(false);
            $table->date('closure_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_fund_id', 'loan_fund_member_id']);
            $table->index(['status', 'is_closed']);
        });

        // 1.5 Loan Fund Loan Repayments
        Schema::create('loan_fund_loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_fund_loan_id')->constrained('loan_fund_loans')->cascadeOnDelete();
            $table->foreignId('loan_fund_member_id')->constrained('loan_fund_members')->cascadeOnDelete();
            $table->string('voucher_number')->nullable()->unique();
            $table->unsignedInteger('installment_order')->default(1);
            $table->date('installment_date');
            $table->decimal('scheduled_amount', 14, 2)->default(0);
            $table->decimal('collected_amount', 14, 2)->default(0);
            $table->decimal('remaining_balance', 14, 2)->default(0);
            $table->boolean('is_closed')->default(false);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['loan_fund_loan_id', 'installment_date']);
        });

        // ==========================================
        // 2. ROSCA Funds (صندوق جمعية)
        // ==========================================

        // 2.1 ROSCA Funds
        Schema::create('rosca_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('fund_number')->nullable()->unique();
            $table->string('fund_name');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('members_count')->default(0);
            $table->decimal('monthly_installment', 14, 2)->default(0);
            $table->decimal('total_pool_amount', 14, 2)->default(0);
            $table->string('allocation_method')->default('consensus'); // lottery, consensus, prearranged
            $table->string('status')->default('active'); // draft, active, completed, closed
            $table->foreignId('assigned_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('manager_name')->nullable();
            $table->longText('rules_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        // 2.2 ROSCA Fund Members
        Schema::create('rosca_fund_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rosca_fund_id')->constrained('rosca_funds')->cascadeOnDelete();
            $table->string('member_type')->default('external'); // employee, external
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('full_name');
            $table->string('national_id', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->unsignedInteger('turn_order')->default(1);
            $table->date('payout_month')->nullable();
            $table->unsignedInteger('installments_count')->default(1);
            $table->decimal('monthly_installment', 14, 2)->default(0);
            $table->decimal('total_final_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('remaining_amount', 14, 2)->default(0);
            $table->boolean('has_received_payout')->default(false);
            $table->date('payout_date')->nullable();
            $table->string('status')->default('active'); // active, completed, dropped
            $table->timestamps();

            $table->index(['rosca_fund_id', 'turn_order']);
            $table->index(['national_id', 'phone']);
        });

        // 2.3 ROSCA Fund Payments
        Schema::create('rosca_fund_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rosca_fund_id')->constrained('rosca_funds')->cascadeOnDelete();
            $table->foreignId('rosca_fund_member_id')->constrained('rosca_fund_members')->cascadeOnDelete();
            $table->date('period_month');
            $table->unsignedInteger('installment_order')->default(1);
            $table->decimal('scheduled_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // paid, partial, unpaid
            $table->boolean('is_bond_closed')->default(false);
            $table->date('payment_date')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rosca_fund_id', 'period_month']);
            $table->index(['rosca_fund_member_id', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rosca_fund_payments');
        Schema::dropIfExists('rosca_fund_members');
        Schema::dropIfExists('rosca_funds');
        Schema::dropIfExists('loan_fund_loan_payments');
        Schema::dropIfExists('loan_fund_loans');
        Schema::dropIfExists('loan_fund_member_payments');
        Schema::dropIfExists('loan_fund_members');
        Schema::dropIfExists('loan_funds');
    }
};
