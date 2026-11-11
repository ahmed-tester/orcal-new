<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            if (! Schema::hasColumn('job_titles', 'is_global')) {
                $table->boolean('is_global')->default(false)->after('company_id');
            }
            $table->foreignId('company_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            if (Schema::hasColumn('job_titles', 'is_global')) {
                $table->dropColumn('is_global');
            }
        });
    }
};
