<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_fund_vouchers', function (Blueprint $table) {
            $table->string('disbursement_type', 30)->default('general')->after('type');
            $table->foreignId('linked_deposit_voucher_id')
                ->nullable()
                ->after('disbursement_type')
                ->constrained('asset_fund_vouchers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_fund_vouchers', function (Blueprint $table) {
            $table->dropForeign(['linked_deposit_voucher_id']);
            $table->dropColumn(['disbursement_type', 'linked_deposit_voucher_id']);
        });
    }
};

