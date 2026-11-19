<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_assignment_items', function (Blueprint $table) {
            $table->boolean('requires_review')->default(true)->after('status');
            $table->timestamp('submitted_at')->nullable()->after('completed_at');
            $table->timestamp('accepted_submission_at')->nullable()->after('submitted_at');
            $table->timestamp('approved_at')->nullable()->after('accepted_submission_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->unsignedInteger('returned_count')->default(0)->after('approved_by');
            $table->unsignedInteger('defect_return_count')->default(0)->after('returned_count');
            $table->text('latest_submission_note')->nullable()->after('defect_return_count');
            $table->text('latest_return_reason')->nullable()->after('latest_submission_note');
            $table->date('original_due_date')->nullable()->after('due_date');
            $table->timestamp('due_date_extended_at')->nullable()->after('latest_return_reason');
            $table->foreignId('due_date_extended_by')->nullable()->after('due_date_extended_at')->constrained('users')->nullOnDelete();
            $table->text('due_date_extension_reason')->nullable()->after('due_date_extended_by');
        });

        // Existing historical items keep legacy direct-completion rules.
        DB::table('task_assignment_items')->update(['requires_review' => false]);

        Schema::create('task_assignment_item_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_item_id')->constrained('task_assignment_items')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at');
            $table->text('submission_note')->nullable();
            $table->string('decision', 32)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('return_category', 32)->nullable();
            $table->text('decision_reason')->nullable();
            $table->decimal('awarded_score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['task_assignment_item_id', 'attempt_number'], 'task_item_reviews_attempt_unique');
            $table->index(['task_assignment_item_id', 'decision'], 'task_item_reviews_decision_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignment_item_reviews');

        Schema::table('task_assignment_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('due_date_extended_by');
            $table->dropColumn([
                'requires_review',
                'submitted_at',
                'accepted_submission_at',
                'approved_at',
                'returned_count',
                'defect_return_count',
                'latest_submission_note',
                'latest_return_reason',
                'original_due_date',
                'due_date_extended_at',
                'due_date_extension_reason',
            ]);
        });
    }
};
