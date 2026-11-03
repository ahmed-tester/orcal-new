<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_fund_vouchers', function (Blueprint $table) {
            // Deposit specific fields
            $table->string('contractor_name')->nullable()->after('company_id');
            $table->string('tax_invoice_number')->nullable()->after('contractor_name');
            $table->decimal('gross_amount', 15, 2)->nullable()->after('total_amount');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('gross_amount');
            $table->decimal('net_after_tax', 15, 2)->nullable()->after('tax_amount');
            $table->decimal('retention_percentage', 5, 2)->nullable()->after('net_after_tax');
            $table->decimal('retention_amount', 15, 2)->default(0)->after('retention_percentage');
            $table->decimal('fund_share_percentage', 5, 2)->nullable()->after('retention_amount');
            $table->decimal('fund_share_amount', 15, 2)->default(0)->after('fund_share_percentage');
            $table->decimal('net_after_deductions', 15, 2)->nullable()->after('fund_share_amount');

            // Withdrawal specific fields
            $table->string('expense_reason')->nullable()->after('party_name');
            $table->decimal('amount_without_tax', 15, 2)->nullable()->after('expense_reason');
        });
    }

    public function down(): void
    {
        Schema::table('asset_fund_vouchers', function (Blueprint $table) {
            $table->dropColumn([
                'contractor_name',
                'tax_invoice_number',
                'gross_amount',
                'tax_amount',
                'net_after_tax',
                'retention_percentage',
                'retention_amount',
                'fund_share_percentage',
                'fund_share_amount',
                'net_after_deductions',
                'expense_reason',
                'amount_without_tax',
            ]);
        });
    }
};

