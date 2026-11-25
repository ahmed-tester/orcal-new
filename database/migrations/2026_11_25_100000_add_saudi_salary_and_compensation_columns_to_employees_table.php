<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'allowances_amount')) {
                $table->decimal('allowances_amount', 12, 2)->default(0)->after('basic_salary');
            }
            if (! Schema::hasColumn('employees', 'bonuses_amount')) {
                $table->decimal('bonuses_amount', 12, 2)->default(0)->after('allowances_amount');
            }
            if (! Schema::hasColumn('employees', 'other_allowances_amount')) {
                $table->decimal('other_allowances_amount', 12, 2)->default(0)->after('bonuses_amount');
            }
            if (! Schema::hasColumn('employees', 'overtime_hourly_rate')) {
                $table->decimal('overtime_hourly_rate', 10, 2)->nullable()->after('other_allowances_amount');
            }
            if (! Schema::hasColumn('employees', 'compensation_hourly_rate')) {
                $table->decimal('compensation_hourly_rate', 10, 2)->nullable()->after('overtime_hourly_rate');
            }
            if (! Schema::hasColumn('employees', 'deduction_basis')) {
                $table->string('deduction_basis', 32)->default('basic')->after('compensation_hourly_rate');
            }
            if (! Schema::hasColumn('employees', 'is_policy_complete')) {
                $table->boolean('is_policy_complete')->default(false)->after('deduction_basis');
            }
            if (! Schema::hasColumn('employees', 'compensation_is_eligible')) {
                $table->boolean('compensation_is_eligible')->default(false)->after('is_policy_complete');
            }
            if (! Schema::hasColumn('employees', 'compensation_effective_date')) {
                $table->date('compensation_effective_date')->nullable()->after('compensation_is_eligible');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $columns = array_values(array_filter([
                'allowances_amount',
                'bonuses_amount',
                'other_allowances_amount',
                'overtime_hourly_rate',
                'compensation_hourly_rate',
                'deduction_basis',
                'is_policy_complete',
                'compensation_is_eligible',
                'compensation_effective_date',
            ], fn (string $col) => Schema::hasColumn('employees', $col)));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
