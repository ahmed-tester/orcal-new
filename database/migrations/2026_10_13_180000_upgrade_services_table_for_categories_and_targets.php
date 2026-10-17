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
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
            $table->string('target_type', 30)->default('general')->after('name');
            $table->string('category', 50)->default('other')->after('target_type');
            $table->decimal('default_gov_fee', 12, 2)->nullable()->default(0)->after('price');
            $table->boolean('is_system')->default(false)->after('status');

            $table->index('target_type');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['target_type']);
            $table->dropIndex(['category']);
            $table->dropColumn(['target_type', 'category', 'default_gov_fee', 'is_system']);
        });
    }
};
