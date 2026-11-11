<?php

use App\Enums\OperationWorkflowStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_transactions', function (Blueprint $table) {
            $table->string('workflow_status', 30)
                ->default(OperationWorkflowStatus::IN_PROGRESS->value)
                ->after('service_status');

            $table->index('workflow_status');
        });

        // Migrate existing records based on the workflow resolution rule
        \App\Models\ServiceTransaction::query()->chunkById(200, function ($transactions) {
            foreach ($transactions as $transaction) {
                $transaction->workflow_status = $transaction->resolveWorkflowStatus();
                $transaction->save();
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_transactions', function (Blueprint $table) {
            $table->dropIndex(['workflow_status']);
            $table->dropColumn('workflow_status');
        });
    }
};
