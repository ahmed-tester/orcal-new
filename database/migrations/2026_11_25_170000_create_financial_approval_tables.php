<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->string('source_type', 40)->default('payroll');
            $table->foreignId('parent_request_id')->nullable()->constrained('financial_approval_requests')->nullOnDelete();
            $table->string('payroll_month', 7)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 30)->default('pending_treasury');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('submitted_at');
            $table->foreignId('treasury_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('treasury_reviewer_department_id')
                ->nullable()
                ->constrained('departments', indexName: 'far_rev_dept_fk')
                ->nullOnDelete();
            $table->dateTime('treasury_reviewed_at')->nullable();
            $table->foreignId('manager_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('manager_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('manager_decided_at')->nullable();
            $table->dateTime('executed_at')->nullable();
            $table->unsignedInteger('employees_count')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('attempt_key', 120)->nullable();
            $table->string('input_fingerprint', 64)->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['submitted_by', 'attempt_key'], 'far_submitter_attempt_unique');
            $table->index(['source_type', 'status'], 'far_source_status_idx');
            $table->index(['treasury_reviewer_id', 'status'], 'far_reviewer_status_idx');
            $table->index(['manager_user_id', 'status'], 'far_manager_status_idx');
            $table->index(['submitted_by', 'status'], 'far_submitter_status_idx');
        });

        Schema::create('financial_approval_request_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('financial_approval_requests')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();

            $table->unique(['request_id', 'company_id'], 'far_co_unique');
        });

        Schema::create('financial_approval_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('financial_approval_requests')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('payroll_month', 7);
            $table->string('employee_name_snapshot');
            $table->string('employee_number_snapshot', 50)->nullable();
            $table->decimal('net_amount', 14, 2);
            $table->decimal('previously_paid_amount', 14, 2)->default(0);
            $table->decimal('requested_amount', 14, 2);
            $table->string('payment_method', 20)->default('cash');
            $table->string('destination_reference', 150)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->string('proof_path')->nullable();
            $table->text('reason_description')->nullable();
            $table->string('data_fingerprint', 64);
            $table->string('idempotency_key', 120)->unique();
            $table->string('status', 30)->default('pending');
            $table->foreignId('payroll_payment_id')->nullable()->constrained('payroll_payments')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();

            $table->index(['request_id', 'version'], 'fari_req_ver_idx');
            $table->index(['payroll_record_id', 'status'], 'fari_rec_status_idx');
            $table->index(['company_id', 'status'], 'fari_co_status_idx');
        });

        Schema::create('financial_approval_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('financial_approval_requests')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('action', 40);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_role', 40)->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('reason')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['request_id', 'created_at'], 'farl_req_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_approval_request_logs');
        Schema::dropIfExists('financial_approval_request_items');
        Schema::dropIfExists('financial_approval_request_companies');
        Schema::dropIfExists('financial_approval_requests');
    }
};
