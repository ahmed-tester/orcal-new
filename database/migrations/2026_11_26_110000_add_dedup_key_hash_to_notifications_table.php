<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->string('dedup_key_hash', 64)->nullable()->after('data');
            $table->unique('dedup_key_hash', 'notifications_dedup_key_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropUnique('notifications_dedup_key_hash_unique');
            $table->dropColumn('dedup_key_hash');
        });
    }
};
