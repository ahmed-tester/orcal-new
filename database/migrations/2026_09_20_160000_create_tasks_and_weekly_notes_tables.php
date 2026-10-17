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
        // 1. Master Tasks Catalog
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code')->nullable()->unique();
            $table->string('name');
            $table->decimal('default_weight', 5, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 2. Task Assignments Header
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->date('assignment_date');
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->decimal('total_weight', 5, 2)->default(0.00);
            $table->decimal('total_score', 5, 2)->default(0.00);
            $table->decimal('overall_percentage', 5, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Task Assignment Items Details
        Schema::create('task_assignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')->constrained('task_assignments')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('weight', 5, 2)->default(0.00);
            $table->decimal('score', 5, 2)->default(0.00);
            $table->decimal('score_percentage', 5, 2)->default(0.00);
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Weekly Notes
        Schema::create('weekly_notes', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->nullable()->unique();
            $table->date('note_date');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('title');
            $table->string('status')->default('pending');
            $table->longText('content');
            $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_notes');
        Schema::dropIfExists('task_assignment_items');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
    }
};
