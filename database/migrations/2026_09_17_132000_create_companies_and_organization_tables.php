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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('company_number')->nullable()->unique();
            $table->string('trade_name');
            $table->string('trade_name_en')->nullable();
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->text('address_en')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('additional_info')->nullable();

            $table->string('commercial_registration_number')->nullable()->unique();
            $table->date('commercial_registration_issue_date')->nullable();
            $table->date('commercial_registration_expiry')->nullable();

            $table->string('municipality_license_number')->nullable();
            $table->date('municipality_license_issue_date')->nullable();
            $table->date('municipality_license_expiry')->nullable();

            $table->string('civil_defense_license_number')->nullable();
            $table->date('civil_defense_license_issue_date')->nullable();
            $table->date('civil_defense_license_expiry')->nullable();

            $table->string('interior_ministry_number')->nullable();
            $table->string('labor_ministry_number')->nullable();
            $table->string('social_insurance_number')->nullable();
            $table->string('zakat_number')->nullable();
            $table->string('chamber_of_commerce_number')->nullable();

            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('branch_code')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
            $table->json('attendance_settings')->nullable();
            
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'branch_code']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('name');
            $table->string('department_code')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'department_code']);
        });

        Schema::create('job_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('job_titles')->nullOnDelete();
            $table->string('name');
            $table->string('job_code')->nullable();
            $table->text('job_description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'job_code']);
        });

        Schema::create('company_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('attachment_type')->default('other');
            $table->string('title');
            $table->string('document_number')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('file_path');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_attachments');
        Schema::dropIfExists('job_titles');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');
    }
};
