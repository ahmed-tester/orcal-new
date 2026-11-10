<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_funds', function (Blueprint $table) {
            $table->decimal('default_tax_percentage', 5, 2)
                ->default(15.00)
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('asset_funds', function (Blueprint $table) {
            $table->dropColumn('default_tax_percentage');
        });
    }
};

