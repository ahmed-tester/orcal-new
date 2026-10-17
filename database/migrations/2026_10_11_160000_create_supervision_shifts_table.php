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
        Schema::create('supervision_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->nullable()->default('supervision'); // 'supervision' or 'shift'
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('employees')->nullOnDelete(); // Primary supervisor or shift leader
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete(); // Associated task from tasks bank
            $table->string('title')->nullable(); // Supervision/shift title or topic
            $table->text('tasks')->nullable(); // Assigned duties, responsibilities, or task description
            $table->json('assigned_employees')->nullable(); // Array of assigned employee IDs (team members)
            $table->date('start_date')->nullable(); // Start date
            $table->date('end_date')->nullable(); // End date
            $table->time('start_time')->nullable(); // Daily start time
            $table->time('end_time')->nullable(); // Daily end time
            $table->string('status', 20)->nullable()->default('active'); // 'active', 'completed', 'suspended', 'cancelled'
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'type']);
            $table->index(['supervisor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervision_shifts');
    }
};
