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
        Schema::table('pos_devices', function (Blueprint $table) {
            if (! Schema::hasColumn('pos_devices', 'bank_or_wallet_name')) {
                $table->string('bank_or_wallet_name')->nullable()->after('card_number');
            }
            if (! Schema::hasColumn('pos_devices', 'accountant_id')) {
                $table->foreignId('accountant_id')->nullable()->after('account_number')->constrained('employees')->nullOnDelete();
            }
            if (! Schema::hasColumn('pos_devices', 'accountant_email')) {
                $table->string('accountant_email')->nullable()->after('accountant_identity_number');
            }
            if (Schema::hasColumn('pos_devices', 'accountant_name')) {
                $table->string('accountant_name')->nullable()->change();
            }
            if (Schema::hasColumn('pos_devices', 'accountant_identity_number')) {
                $table->string('accountant_identity_number')->nullable()->change();
            }
            if (Schema::hasColumn('pos_devices', 'accountant_phone')) {
                $table->string('accountant_phone')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_devices', function (Blueprint $table) {
            if (Schema::hasColumn('pos_devices', 'accountant_id')) {
                $table->dropForeign(['accountant_id']);
                $table->dropColumn('accountant_id');
            }
            if (Schema::hasColumn('pos_devices', 'accountant_email')) {
                $table->dropColumn('accountant_email');
            }
            if (Schema::hasColumn('pos_devices', 'bank_or_wallet_name')) {
                $table->dropColumn('bank_or_wallet_name');
            }
        });
    }
};
