<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Polymorphic subject: department, employee, branch or supervisor.
            $table->string('subject_type', 30);
            $table->unsignedBigInteger('subject_id');
            $table->string('rule_key', 50);
            $table->string('status', 20)->default('open');
            $table->decimal('score_at_open', 5, 2);
            $table->unsignedTinyInteger('threshold')->default(65);
            $table->string('rules_version', 50);
            $table->timestamp('opened_at');
            $table->unsignedTinyInteger('open_slot')->nullable()->default(1);
            $table->text('management_action')->nullable();
            $table->foreignId('management_action_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('management_action_at')->nullable();
            $table->string('closure_reason', 30)->nullable();
            $table->text('close_reason')->nullable();
            $table->decimal('close_score', 5, 2)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // One open case per company/subject/rule. Closed rows keep NULL so
            // MySQL allows any number of historical rows for the same subject.
            $table->unique(
                ['company_id', 'subject_type', 'subject_id', 'rule_key', 'open_slot'],
                'perf_escalations_open_unique'
            );
            $table->index(['company_id', 'status'], 'perf_escalations_company_status_idx');
            $table->index(['subject_type', 'subject_id', 'rule_key'], 'perf_escalations_subject_idx');
            $table->index('opened_at', 'perf_escalations_opened_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_escalations');
    }
};
