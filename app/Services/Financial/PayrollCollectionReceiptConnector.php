<?php

namespace App\Services\Financial;

use App\Enums\FinancialReceiptStatus;
use App\Enums\PaymentItemCategory;
use App\Models\Employee;
use App\Models\FinancialReceipt;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Services\Financial\Contracts\FinancialReceiptSourceConnector;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\OperationsAuditService;
use App\Services\PayrollRecordFinancialService;
use App\Services\ServiceFinancialWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollCollectionReceiptConnector implements FinancialReceiptSourceConnector
{
    public function sourceType(): string
    {
        return FinancialReceipt::SOURCE_PAYROLL_COLLECTION;
    }

    public function find(int $sourceId): Model
    {
        return PayrollRecord::findOrFail($sourceId);
    }

    public function findLocked(int $sourceId): Model
    {
        return PayrollRecord::whereKey($sourceId)->lockForUpdate()->firstOrFail();
    }

    public function assertManaged(Model $source): void
    {
        /** @var PayrollRecord $source */
        if (! $source->isCollection()) {
            throw ValidationException::withMessages([
                'operation_type' => __('financial.errors.payroll_not_receipt_source'),
            ]);
        }

        PayrollRecordFinancialService::ensureManaged($source);
    }

    public function authorize(Model $source, string $action, ?User $user = null): User
    {
        /** @var PayrollRecord $source */
        $user = $user ?? auth()->user();

        if (! $user) {
            throw new AuthorizationException(__('financial.errors.unauthenticated'));
        }

        $permission = match ($action) {
            'record' => 'record_service_receipt',
            'confirm' => 'confirm_service_receipt',
            'reverse' => 'reverse_financial_transaction',
            'request_handover' => 'request_cash_handover',
            default => $action,
        };

        ServiceFinancialWorkflowService::authorizeCompany($user, (int) $source->company_id, $permission);

        $branchId = $this->branchId($source);
        if ($branchId && $user->branch_id && ! $user->canAccessAllBranches()
            && (int) $user->branch_id !== (int) $branchId) {
            throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
        }

        return $user;
    }

    public function companyId(Model $source): int
    {
        /** @var PayrollRecord $source */
        return (int) $source->company_id;
    }

    public function branchId(Model $source): ?int
    {
        /** @var PayrollRecord $source */
        $branchId = $source->metadata['branch_id_snapshot'] ?? $source->employee?->branch_id;

        return $branchId ? (int) $branchId : null;
    }

    public function defaultCollectorEmployeeId(Model $source, User $user, array $data): ?int
    {
        $collectorId = $user->isSuperAdminOrOwner()
            ? ($data['collector_employee_id'] ?? $user->employee?->id)
            : $user->employee?->id;

        if (! $collectorId) {
            return null;
        }

        if ($user->isSuperAdminOrOwner() && ! empty($data['collector_employee_id'])) {
            $inScope = Employee::query()
                ->whereKey((int) $collectorId)
                ->where('company_id', $this->companyId($source))
                ->where('status', 'active')
                ->exists();

            if (! $inScope) {
                throw ValidationException::withMessages([
                    'collector_employee_id' => __('financial.errors.collector_required'),
                ]);
            }
        }

        return (int) $collectorId;
    }

    public function defaultPayerName(Model $source, array $data): ?string
    {
        /** @var PayrollRecord $source */
        return $data['payer_name']
            ?? $source->employee?->user?->name
            ?? ('#'.$source->employee_id);
    }

    public function handoverCategory(): PaymentItemCategory
    {
        return PaymentItemCategory::SALARY_COLLECTION;
    }

    public function validateRecordAmount(Model $source, float $amount): void
    {
        /** @var PayrollRecord $source */
        $summary = PayrollRecordFinancialService::collectionSummary($source);
        $available = (float) $summary['available_to_settle'];

        if ($amount > $available + 0.009) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.exceeds_available_collection'),
            ]);
        }
    }

    public function validateConfirmAmount(Model $source, FinancialReceipt $receipt): void
    {
        /** @var PayrollRecord $source */
        $summary = PayrollRecordFinancialService::collectionSummary($source);
        $otherPending = round(max(0.0, (float) $summary['pending_receipts'] - (float) $receipt->amount), 2);
        $available = round(max(0.0, (float) $summary['due'] - (float) $summary['total_settled'] - (float) $summary['deduction_reserved'] - $otherPending), 2);

        if ((float) $receipt->amount > $available + 0.009) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.exceeds_available_collection'),
            ]);
        }
    }

    public function onReceiptRecorded(Model $source, FinancialReceipt $receipt, User $user): void
    {
        /** @var PayrollRecord $source */
        OperationsAuditService::recordFor(
            model: $source,
            actionType: 'financial_receipt_recorded',
            oldStatus: $source->payment_status?->value,
            newStatus: $source->payment_status?->value,
            payload: [
                'record_number' => $source->record_number,
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'payment_method' => $receipt->payment_method?->value,
            ],
            userId: $user->id,
            companyId: (int) $source->company_id,
        );

        if (! $receipt->isCash() && $receipt->status === FinancialReceiptStatus::PENDING_CONFIRMATION) {
            DB::afterCommit(function () use ($source, $receipt, $user): void {
                NotificationDispatcher::translated(
                    record: $source,
                    eventKey: 'PayrollCollectionReceiptRecorded',
                    titleKey: 'financial.notifications.collection_receipt_recorded_title',
                    bodyKey: 'financial.notifications.collection_receipt_recorded_body',
                    parameters: [
                        'record' => (string) $source->record_number,
                        'receipt' => (string) $receipt->receipt_number,
                        'amount' => number_format((float) $receipt->amount, 2),
                    ],
                    icon: 'heroicon-o-receipt-percent',
                    color: 'info',
                    excludeUserIds: [$user->id],
                    context: [
                        'routing_mode' => 'operations_targeted',
                        'event_key' => 'PayrollCollectionReceiptRecorded',
                        'required_permission' => 'confirm_service_receipt',
                        'subject_type' => FinancialReceipt::class,
                        'subject_id' => $receipt->id,
                        'company_id' => (int) $source->company_id,
                        'branch_id' => $this->branchId($source),
                        'is_action_request' => true,
                    ],
                );
            });
        }
    }

    public function onReceiptConfirmed(Model $source, FinancialReceipt $receipt, User $user): void
    {
        /** @var PayrollRecord $source */
        $oldStatus = $source->payment_status?->value;
        $source = PayrollRecordFinancialService::syncCollection($source);

        OperationsAuditService::recordFor(
            model: $source,
            actionType: 'financial_receipt_confirmed',
            oldStatus: $oldStatus,
            newStatus: $source->payment_status?->value,
            payload: [
                'record_number' => $source->record_number,
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'payment_method' => $receipt->payment_method?->value,
                'cash' => $receipt->isCash(),
            ],
            userId: $user->id,
            companyId: (int) $source->company_id,
        );

        if ($source->created_by && (int) $source->created_by !== (int) $user->id) {
            DB::afterCommit(function () use ($source, $receipt, $user): void {
                NotificationDispatcher::translated(
                    record: $source,
                    eventKey: 'PayrollCollectionReceiptConfirmed',
                    titleKey: 'financial.notifications.collection_receipt_confirmed_title',
                    bodyKey: 'financial.notifications.collection_receipt_confirmed_body',
                    parameters: [
                        'record' => (string) $source->record_number,
                        'receipt' => (string) $receipt->receipt_number,
                        'amount' => number_format((float) $receipt->amount, 2),
                    ],
                    icon: 'heroicon-o-check-circle',
                    color: 'success',
                    excludeUserIds: [$user->id],
                    context: [
                        'routing_mode' => 'operations_targeted',
                        'target_user_ids' => [(int) $source->created_by],
                        'subject_type' => FinancialReceipt::class,
                        'subject_id' => $receipt->id,
                        'company_id' => (int) $source->company_id,
                        'branch_id' => $this->branchId($source),
                    ],
                );
            });
        }
    }

    public function onReceiptReversed(Model $source, FinancialReceipt $receipt, string $reason, ?User $user = null): void
    {
        /** @var PayrollRecord $source */
        $oldStatus = $source->payment_status?->value;
        $source = PayrollRecordFinancialService::syncCollection($source);
        $actorId = $user?->id ?? auth()->id();

        OperationsAuditService::recordFor(
            model: $source,
            actionType: 'financial_receipt_reversed',
            oldStatus: $oldStatus,
            newStatus: $source->payment_status?->value,
            payload: [
                'record_number' => $source->record_number,
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'reason' => $reason,
            ],
            userId: $actorId,
            companyId: (int) $source->company_id,
        );

        $targets = array_values(array_unique(array_filter([
            $receipt->collector?->user_id,
            $source->created_by,
        ])));

        if ($targets !== []) {
            DB::afterCommit(function () use ($source, $receipt, $reason, $actorId, $targets): void {
                NotificationDispatcher::translated(
                    record: $source,
                    eventKey: 'PayrollCollectionReceiptReversed',
                    titleKey: 'financial.notifications.collection_receipt_reversed_title',
                    bodyKey: 'financial.notifications.collection_receipt_reversed_body',
                    parameters: [
                        'record' => (string) $source->record_number,
                        'receipt' => (string) $receipt->receipt_number,
                        'amount' => number_format((float) $receipt->amount, 2),
                        'reason' => $reason,
                    ],
                    icon: 'heroicon-o-arrow-uturn-left',
                    color: 'danger',
                    excludeUserIds: array_filter([$actorId ? (int) $actorId : null]),
                    context: [
                        'routing_mode' => 'operations_targeted',
                        'target_user_ids' => $targets,
                        'subject_type' => FinancialReceipt::class,
                        'subject_id' => $receipt->id,
                        'company_id' => (int) $source->company_id,
                        'branch_id' => $this->branchId($source),
                    ],
                );
            });
        }
    }

    public function onHandoverChanged(Model $source): void
    {
        /** @var PayrollRecord $source */
        PayrollRecordFinancialService::syncCollection($source);
    }
}
