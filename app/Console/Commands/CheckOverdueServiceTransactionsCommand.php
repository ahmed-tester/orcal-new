<?php

namespace App\Console\Commands;

use App\Enums\OperationWorkflowStatus;
use App\Models\ServiceTransaction;
use App\Services\Notifications\OperationsNotificationDiagnostics;
use App\Services\OperationsNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckOverdueServiceTransactionsCommand extends Command
{
    protected $signature = 'service-transactions:check-overdue
                            {--since= : Cutoff date (Y-m-d) to avoid alerting historical overdue transactions before rollout}';

    protected $description = 'Check active service transactions past their due date and notify their assigned executor once';

    public function handle(): int
    {
        $today = Carbon::today();
        $sinceOption = $this->option('since') ?: config('notifications.service_overdue_since', '2026-11-23');
        $sinceDate = Carbon::parse($sinceOption)->startOfDay();

        $sentCount = 0;
        $skippedCount = 0;

        ServiceTransaction::query()
            ->where('service_status', 'active')
            ->where('workflow_status', '!=', OperationWorkflowStatus::COMPLETED->value)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today->toDateString())
            ->where(function ($query) use ($sinceDate): void {
                $query->whereDate('due_date', '>=', $sinceDate->toDateString())
                    ->orWhere('created_at', '>=', $sinceDate);
            })
            ->orderBy('id')
            ->chunkById(100, function ($transactions) use ($today, &$sentCount, &$skippedCount): void {
                foreach ($transactions as $transaction) {
                    $result = $this->processTransaction((int) $transaction->id, $today);
                    if ($result === true) {
                        $sentCount++;
                    } elseif ($result === false) {
                        $skippedCount++;
                    }
                }
            });

        $this->info("Overdue service transactions check completed. Sent: {$sentCount}, Skipped: {$skippedCount}.");

        return self::SUCCESS;
    }

    private function processTransaction(int $transactionId, Carbon $today): ?bool
    {
        return DB::transaction(function () use ($transactionId, $today): ?bool {
            $tx = ServiceTransaction::query()
                ->with(['executor.user', 'service'])
                ->whereKey($transactionId)
                ->lockForUpdate()
                ->first();

            if (! $tx) {
                return null;
            }

            if ($tx->service_status !== 'active'
                || $tx->workflow_status === OperationWorkflowStatus::COMPLETED->value
                || ($tx->metadata['status'] ?? null) === 'cancelled'
                || empty($tx->due_date)) {
                return null;
            }

            $dueDate = Carbon::parse($tx->due_date)->startOfDay();
            if (! $dueDate->lt($today)) {
                return null;
            }

            $dueDateString = $dueDate->toDateString();
            $meta = $tx->metadata ?? [];

            if (! empty($meta['overdue_notified_at'])
                && ($meta['overdue_notified_for_due_date'] ?? null) === $dueDateString) {
                return null;
            }

            $daysOverdue = (int) $dueDate->diffInDays($today);
            $dispatched = OperationsNotificationService::notifyOverdue($tx, $daysOverdue, synchronous: true);

            if ($dispatched <= 0) {
                $skipReason = empty($tx->executed_by) ? 'missing_executor' : 'no_eligible_recipient';
                $meta['overdue_last_skipped_at'] = now()->toDateTimeString();
                $meta['overdue_last_skip_reason'] = $skipReason;
                $tx->metadata = $meta;
                $tx->saveQuietly();

                OperationsNotificationDiagnostics::write('overdue_skipped_no_recipient', ServiceTransaction::class, $tx->id, [
                    'reason' => $skipReason,
                ]);

                return false;
            }

            $meta['overdue_notified_at'] = now()->toDateTimeString();
            $meta['overdue_notified_for_due_date'] = $dueDateString;
            unset($meta['overdue_last_skip_reason']);

            $tx->metadata = $meta;
            $tx->saveQuietly();

            return true;
        });
    }
}
