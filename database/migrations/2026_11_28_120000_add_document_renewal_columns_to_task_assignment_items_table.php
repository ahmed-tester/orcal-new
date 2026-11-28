<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_assignment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('task_assignment_items', 'renewal_request_id')) {
                $table->foreignId('renewal_request_id')
                    ->nullable()
                    ->after('task_id')
                    ->constrained('unified_deficiency_tasks', indexName: 'tai_renewal_request_fk')
                    ->restrictOnDelete();

                $table->unique('renewal_request_id', 'tai_renewal_request_uq');
            }

            if (! Schema::hasColumn('task_assignment_items', 'company_id')) {
                $table->foreignId('company_id')
                    ->nullable()
                    ->after('renewal_request_id')
                    ->constrained('companies', indexName: 'tai_company_fk')
                    ->nullOnDelete();

                $table->index('company_id', 'tai_company_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_assignment_items', function (Blueprint $table) {
            if (Schema::hasColumn('task_assignment_items', 'company_id')) {
                $table->dropForeign('tai_company_fk');
                $table->dropIndex('tai_company_idx');
                $table->dropColumn('company_id');
            }

            if (Schema::hasColumn('task_assignment_items', 'renewal_request_id')) {
                $table->dropForeign('tai_renewal_request_fk');
                $table->dropUnique('tai_renewal_request_uq');
                $table->dropColumn('renewal_request_id');
            }
        });
    }
};
