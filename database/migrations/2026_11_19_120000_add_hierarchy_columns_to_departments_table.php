<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->unsignedInteger('sort_order')->default(0)->after('status');
            $table->index(['parent_id', 'sort_order'], 'departments_parent_sort_index');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('departments_parent_sort_index');
            $table->dropColumn(['description', 'sort_order']);
        });
    }
};
