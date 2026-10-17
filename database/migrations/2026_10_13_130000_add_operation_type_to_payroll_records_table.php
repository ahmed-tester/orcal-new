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
        if (Schema::hasTable('payroll_records')) {
            Schema::table('payroll_records', function (Blueprint $table) {
                if (!Schema::hasColumn('payroll_records', 'operation_type')) {
                    $table->string('operation_type')->default('payroll')->after('employee_id');
                }
            });

            // تحديث قيد التفرّد بأمان
            try {
                Schema::table('payroll_records', function (Blueprint $table) {
                    $table->dropUnique('payroll_records_company_id_employee_id_payroll_month_unique');
                });
            } catch (\Throwable $e) {
                // تم حذفه مسبقاً أو غير موجود
            }

            try {
                Schema::table('payroll_records', function (Blueprint $table) {
                    $table->unique(
                        ['company_id', 'employee_id', 'payroll_month', 'operation_type'],
                        'payroll_records_comp_emp_month_op_unique'
                    );
                });
            } catch (\Throwable $e) {
                // موجود مسبقاً
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payroll_records') && Schema::hasColumn('payroll_records', 'operation_type')) {
            Schema::table('payroll_records', function (Blueprint $table) {
                try {
                    $table->dropUnique('payroll_records_comp_emp_month_op_unique');
                } catch (\Throwable $e) {
                }

                $table->dropColumn('operation_type');
            });
        }
    }
};
