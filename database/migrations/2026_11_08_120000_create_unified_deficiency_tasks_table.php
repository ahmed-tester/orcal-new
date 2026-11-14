<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('unified_deficiency_tasks');

        Schema::create('unified_deficiency_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->nullableMorphs('taskable', 'def_tasks_taskable_idx');
            $table->foreignId('service_transaction_id')->nullable()->constrained('service_transactions')->nullOnDelete();
            $table->string('task_code', 50)->unique();
            $table->string('deficiency_type', 50);
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('responsible_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('open');
            $table->date('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Explicit short index names strictly adhering to MySQL 64-char limit
            $table->index(['company_id', 'status'], 'def_tasks_co_status_idx');
            $table->index(['service_transaction_id', 'status'], 'def_tasks_tx_status_idx');
            $table->index(['responsible_employee_id', 'status'], 'def_tasks_emp_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_deficiency_tasks');
    }
};
