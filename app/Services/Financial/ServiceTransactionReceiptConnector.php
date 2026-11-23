<?php

namespace App\Services\Financial;

use App\Enums\PaymentItemCategory;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Services\Financial\Contracts\FinancialReceiptSourceConnector;
use App\Services\Notifications\OperationsNotificationDiagnostics;
use App\Services\OperationsAuditService;
use App\Services\OperationsNotificationService;
use App\Services\OperationsReconciliationService;
use App\Services\ServiceFinancialWorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ServiceTransactionReceiptConnector implements FinancialReceiptSourceConnector
{
    public function sourceType(): string
    {
        return FinancialReceipt::SOURCE_SERVICE_TRANSACTION;
    }

    public function find(int $sourceId): Model
    {
        return ServiceTransaction::findOrFail($sourceId);
    }

    public function findLocked(int $sourceId): Model
    {
        return ServiceTransaction::whereKey($sourceId)->lockForUpdate()->firstOrFail();
    }

    public function assertManaged(Model $source): void
    {
        /** @var ServiceTransaction $source */
        ServiceFinancialWorkflowService::assertManaged($source);
    }

    public function authorize(Model $source, string $action, ?User $user = null): User
    {
        /** @var ServiceTransaction $source */
        $permission = match ($action) {
            'record' => 'record_service_receipt',
            'confirm', 'reverse' => 'confirm_service_receipt',
            'request_handover' => 'request_cash_handover',
            default => $action,
        };

        return ServiceFinancialWorkflowService::authorize($source, $permission, $user);
    }

    public function companyId(Model $source): int
    {
        /** @var ServiceTransaction $source */
        return (int) $source->company_id;
    }

    public function branchId(Model $source): ?int
    {
        /** @var ServiceTransaction $source */
        return $source->employee?->branch_id ?? $source->executor?->branch_id;
    }

    public function defaultCollectorEmployeeId(Model $source, User $user, array $data): ?int
    {
        /** @var ServiceTransaction $source */
        $collectorId = $user->isSuperAdminOrOwner()
            ? ($data['collector_employee_id'] ?? $source->executed_by ?? $user->employee?->id)
            : ($user->employee?->id ?? $source->executed_by);

        return $collectorId ? (int) $collectorId : null;
    }

    public function defaultPayerName(Model $source, array $data): ?string
    {
        /** @var ServiceTransaction $source */
        return $data['payer_name'] ?? $source->display_beneficiary;
    }

    public function handoverCategory(): PaymentItemCategory
    {
        return PaymentItemCategory::SERVICES;
    }

    public function validateRecordAmount(Model $source, float $amount): void
    {
        // Service transactions allow client surplus receipts.
    }

    public function validateConfirmAmount(Model $source, FinancialReceipt $receipt): void
    {
        // Service transactions allow client surplus receipts.
    }

    public function onReceiptRecorded(Model $source, FinancialReceipt $receipt, User $user): void
    {
        /** @var ServiceTransaction $source */
        OperationsAuditService::record(
            companyId: (int) $source->company_id,
            transactionId: $source->id,
            actionType: 'financial_receipt_recorded',
            oldStatus: $source->workflow_status,
            newStatus: $source->workflow_status,
            payload: [
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'payment_method' => $receipt->payment_method?->value,
            ],
            userId: $user->id,
        );

        if (! $receipt->isCash()) {
            DB::afterCommit(function () use ($source, $receipt, $user) {
                OperationsNotificationService::notifyFinancialReceiptRecorded($source, $receipt, $user);
            });
        }
    }

    public function onReceiptConfirmed(Model $source, FinancialReceipt $receipt, User $user): void
    {
        /** @var ServiceTransaction $source */
        ServiceFinancialWorkflowService::sync($source);
        OperationsReconciliationService::reconcile($source);

        OperationsAuditService::record(
            companyId: (int) $source->company_id,
            transactionId: $source->id,
            actionType: 'financial_receipt_confirmed',
            oldStatus: $source->workflow_status,
            newStatus: $source->workflow_status,
            payload: [
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'payment_method' => $receipt->payment_method?->value,
                'cash' => $receipt->isCash(),
            ],
            userId: $user->id,
        );

        OperationsNotificationDiagnostics::write('receipt_confirmation_notification_scheduled', ServiceTransaction::class, $source->id, [
            'receipt_id' => $receipt->id,
        ]);

        DB::afterCommit(function () use ($source, $receipt) {
            OperationsNotificationService::notifyFinancialReceiptConfirmed($source, $receipt);
        });
    }

    public function onReceiptReversed(Model $source, FinancialReceipt $receipt, string $reason, ?User $user = null): void
    {
        /** @var ServiceTransaction $source */
        ServiceFinancialWorkflowService::sync($source);
        OperationsReconciliationService::reconcile($source);

        OperationsAuditService::record(
            companyId: (int) $source->company_id,
            transactionId: $source->id,
            actionType: 'financial_receipt_reversed',
            oldStatus: $source->workflow_status,
            newStatus: $source->workflow_status,
            payload: [
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'reason' => $reason,
            ],
            userId: $user?->id ?? auth()->id(),
        );

        $actorId = $user?->id ?? auth()->id();
        DB::afterCommit(function () use ($source, $receipt, $reason, $actorId): void {
            OperationsNotificationService::notifyFinancialReceiptReversed($source, $receipt, $reason, $actorId);
        });
    }

    public function onHandoverChanged(Model $source): void
    {
        /** @var ServiceTransaction $source */
        ServiceFinancialWorkflowService::sync($source);
        OperationsReconciliationService::reconcile($source);
    }
}
