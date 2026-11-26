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
        // 1. Directive categories.
        Schema::create('directive_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('name_ar', 120);
            $table->string('name_en', 120);
            $table->string('color', 32)->default('info');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Directives.
        Schema::create('directives', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->foreignId('category_id')
                ->constrained('directive_categories')
                ->restrictOnDelete();
            $table->text('description');
            $table->string('target_type', 20)->default('all')->index();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // 3. Directive recipients and acknowledgment tracking.
        Schema::create('directive_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directive_id')
                ->constrained('directives')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['directive_id', 'user_id']);
            $table->index(['user_id', 'acknowledged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directive_recipients');
        Schema::dropIfExists('directives');
        Schema::dropIfExists('directive_categories');
    }
};
