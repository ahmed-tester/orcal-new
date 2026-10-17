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
        Schema::table('installments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
            $table->foreignId('employee_id')->nullable()->change();
            $table->foreignId('installment_type_id')->nullable()->change();

            $table->string('transaction_type')->default('installment')->after('installment_number');
            $table->string('beneficiary_type')->default('internal')->after('transaction_type');

            $table->string('external_name')->nullable()->after('employee_id');
            $table->string('external_national_id')->nullable()->after('external_name');
            $table->string('external_phone')->nullable()->after('external_national_id');
            $table->string('external_email')->nullable()->after('external_phone');
            $table->text('external_address')->nullable()->after('external_email');

            $table->foreignId('guarantor_id')->nullable()->after('external_address')->constrained('guarantors')->nullOnDelete();

            $table->index(['transaction_type', 'beneficiary_type']);
            $table->index('external_national_id');
            $table->index('external_phone');
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->change();
            $table->foreignId('employee_id')->nullable()->change();
            $table->string('payment_type')->nullable()->after('installment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installment_payments', function (Blueprint $table) {
            $table->dropColumn('payment_type');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropForeign(['guarantor_id']);
            $table->dropIndex(['transaction_type', 'beneficiary_type']);
            $table->dropIndex(['external_national_id']);
            $table->dropIndex(['external_phone']);

            $table->dropColumn([
                'transaction_type',
                'beneficiary_type',
                'external_name',
                'external_national_id',
                'external_phone',
                'external_email',
                'external_address',
                'guarantor_id',
            ]);
        });
    }
};
