<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_letters', function (Blueprint $table) {
            $table->id();

            // --- Company Relationship ---
            $table->foreignId('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();

            // --- Basic Letter Info ---
            $table->string('letter_number')->nullable()->unique();
            $table->string('subject')->nullable();
            $table->date('letter_date')->nullable();

            // --- Letter Content ---
            $table->longText('body')->nullable();

            // --- Status & Metadata ---
            $table->string('status')->nullable()->default('draft');
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_letters');
    }
};
