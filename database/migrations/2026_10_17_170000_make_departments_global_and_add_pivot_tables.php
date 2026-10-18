<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropUnique(['company_id', 'department_code']);
            $table->foreignId('company_id')->nullable()->change();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->boolean('is_global')->default(false)->after('company_id');
        });

        Schema::create('company_department', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->unique(['department_id', 'company_id']);
            $table->timestamps();
        });

        $existing = DB::table('departments')->whereNotNull('company_id')->get(['id', 'company_id']);
        foreach ($existing as $dept) {
            DB::table('company_department')->updateOrInsert(
                ['department_id' => $dept->id, 'company_id' => $dept->company_id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_department');

        $firstCompanyId = DB::table('companies')->value('id') ?? 1;
        DB::table('departments')->whereNull('company_id')->update(['company_id' => $firstCompanyId]);

        if (Schema::hasColumn('departments', 'is_global')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('is_global');
            });
        }

        try {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
            });
        } catch (\Throwable $e) {}

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        try {
            Schema::table('departments', function (Blueprint $table) {
                $table->unique(['company_id', 'department_code']);
            });
        } catch (\Throwable $e) {}
    }
};
