<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables with dates requiring metadata JSON column for Hijri dates.
     */
    protected array $tables = [
        'companies',
        'company_attachments',
        'employees',
        'payroll_records',
        'payroll_payments',
        'employee_rewards',
        'leaves',
        'attendance',
        'supervision_shifts',
        'violations',
        'violation_transactions',
        'daily_journals',
        'treasury_transactions',
        'invoices',
        'contract_vouchers',
        'installments',
        'installment_schedules',
        'installment_payments',
        'loan_fund_loans',
        'loan_fund_loan_payments',
        'loan_fund_members',
        'loan_fund_member_payments',
        'rosca_funds',
        'rosca_fund_members',
        'rosca_fund_payments',
        'offers',
        'task_assignments',
        'task_assignment_items',
        'weekly_notes',
        'owners',
        'tickets',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'metadata')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->json('metadata')->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'metadata')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('metadata');
                });
            }
        }
    }
};
