<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['departments', 'employees', 'job_titles'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->softDeletes();
                });
            }
        }

        if (! Schema::hasTable('department_archive_batches')) {
            Schema::create('department_archive_batches', function (Blueprint $table): void {
                $table->id();
                $table->string('action_type', 40); // archive | transfer_and_archive
                $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('root_department_id')->nullable()->index();
                $table->string('root_department_name');
                $table->string('root_department_code')->nullable();
                $table->unsignedBigInteger('target_department_id')->nullable()->index();
                $table->string('target_department_name')->nullable();
                $table->json('department_ids')->nullable();
                $table->json('summary')->nullable();
                $table->timestamp('restored_at')->nullable()->index();
                $table->foreignId('restored_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('department_archive_items')) {
            Schema::create('department_archive_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('batch_id')->constrained('department_archive_batches')->cascadeOnDelete();
                $table->string('item_type', 40); // department | employee | job_title | user_access
                $table->unsignedBigInteger('item_id');
                $table->unsignedBigInteger('parent_department_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->json('previous_state')->nullable();
                $table->timestamp('restored_at')->nullable();
                $table->timestamps();

                $table->index(['batch_id', 'item_type'], 'dept_arch_items_batch_type_idx');
                $table->index(['item_type', 'item_id'], 'dept_arch_items_type_id_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('department_archive_items');
        Schema::dropIfExists('department_archive_batches');

        foreach (['job_titles', 'employees', 'departments'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};
