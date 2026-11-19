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
        Schema::dropIfExists('unified_audit_logs');

        Schema::create('unified_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->nullableMorphs('auditable', 'audit_logs_auditable_idx');
            $table->unsignedBigInteger('service_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action_type', 50)->index();
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50)->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->foreign('service_transaction_id', 'audit_logs_tx_fk')
                ->references('id')
                ->on('service_transactions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_audit_logs');
    }
};
