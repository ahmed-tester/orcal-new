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
        Schema::table('daily_journals', function (Blueprint $table) {
            $table->boolean('is_detailed')->default(false)->after('notes');

            $table->decimal('sales_cash',     14, 2)->nullable()->default(null)->after('is_detailed');
            $table->decimal('sales_network',  14, 2)->nullable()->default(null)->after('sales_cash');
            $table->decimal('sales_deferred', 14, 2)->nullable()->default(null)->after('sales_network');
            $table->decimal('sales_other',    14, 2)->nullable()->default(null)->after('sales_deferred');

            $table->decimal('purchases_cash',    14, 2)->nullable()->default(null)->after('sales_other');
            $table->decimal('purchases_network', 14, 2)->nullable()->default(null)->after('purchases_cash');
            $table->decimal('purchases_credit',  14, 2)->nullable()->default(null)->after('purchases_network');
            $table->decimal('purchases_debit',   14, 2)->nullable()->default(null)->after('purchases_credit');
            $table->decimal('expenses_other',    14, 2)->nullable()->default(null)->after('purchases_debit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_journals', function (Blueprint $table) {
            $table->dropColumn([
                'is_detailed',
                'sales_cash', 'sales_network', 'sales_deferred', 'sales_other',
                'purchases_cash', 'purchases_network', 'purchases_credit', 'purchases_debit', 'expenses_other',
            ]);
        });
    }
};
