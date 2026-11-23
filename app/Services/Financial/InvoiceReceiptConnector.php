<?php

namespace App\Services\Financial;

use App\Enums\FinancialReceiptStatus;
use App\Enums\PaymentItemCategory;
use App\Models\Employee;
use App\Models\FinancialReceipt;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Financial\Contracts\FinancialReceiptSourceConnector;
use App\Services\InvoiceFinancialService;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\OperationsAuditService;
use App\Services\ServiceFinancialWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceReceiptConnector implements FinancialReceiptSourceConnector
{
    public function sourceType(): string
    {
        return FinancialReceipt::SOURCE_INVOICE;
    }

    public function find(int $sourceId): Model
    {
        return Invoice::findOrFail($sourceId);
    }

    public function findLocked(int $sourceId): Model
    {
        return Invoice::whereKey($sourceId)->lockForUpdate()->firstOrFail();
    }

    public function assertManaged(Model $source): void
    {
        /** @var Invoice $source */
        InvoiceFinancialService::ensureManagedForReceipt($source);
    }

    public function authorize(Model $source, string $action, ?User $user = null): User
    {
        /** @var Invoice $source */
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

        if ($source->branch_id && $user->branch_id && ! $user->canAccessAllBranches()
            && (int) $user->branch_id !== (int) $source->branch_id) {
            throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
        }

        return $user;
    }

    public function companyId(Model $source): int
    {
        /** @var Invoice $source */
        return (int) $source->company_id;
    }

    public function branchId(Model $source): ?int
    {
        /** @var Invoice $source */
        return $source->branch_id ? (int) $source->branch_id : null;
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
            /** @var Invoice $source */
            $inScope = Employee::query()
                ->whereKey((int) $collectorId)
                ->where('company_id', $source->company_id)
                ->when($source->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
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
        /** @var Invoice $source */
        return $data['payer_name']
            ?? $source->customer?->name
            ?? $source->employee?->user?->name
            ?? $source->invoice_number;
    }

    public function handoverCategory(): PaymentItemCategory
    {
        return PaymentItemCategory::INVOICES;
    }

    public function validateRecordAmount(Model $source, float $amount): void
    {
        /** @var Invoice $source */
        $summary = InvoiceFinancialService::summary($source);
        $available = round(max(0.0, (float) $summary['remaining'] - (float) $summary['pending_receipts']), 2);

        if ($amount > $available + 0.009) {
            throw ValidationException::withMessages([
                'amount' => __('invoices.errors.exceeds_remaining', [
                    'amount' => number_format($available, 2),
                ]),
            ]);
        }
    }

    public function validateConfirmAmount(Model $source, FinancialReceipt $receipt): void
    {
        /** @var Invoice $source */
        $summary = InvoiceFinancialService::summary($source);
        $otherPending = round(max(0.0, (float) $summary['pending_receipts'] - (float) $receipt->amount), 2);
        $available = round(max(0.0, (float) $summary['remaining'] - $otherPending), 2);

        if ((float) $receipt->amount > $available + 0.009) {
            throw ValidationException::withMessages([
                'amount' => __('invoices.errors.exceeds_remaining', [
                    'amount' => number_format($available, 2),
                ]),
            ]);
        }
    }

    public function onReceiptRecorded(Model $source, FinancialReceipt $receipt, User $user): void
    {
        /** @var Invoice $source */
        OperationsAuditService::recordFor(
            model: $source,
            actionType: 'financial_receipt_recorded',
            oldStatus: $source->payment_status?->value,
            newStatus: $source->payment_status?->value,
            payload: [
                'invoice_number' => $source->invoice_number,
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
                    eventKey: 'InvoiceFinancialReceiptRecorded',
                    titleKey: 'invoices.notifications.receipt_recorded_title',
                    bodyKey: 'invoices.notifications.receipt_recorded_body',
                    parameters: [
                        'invoice' => (string) $source->invoice_number,
                        'receipt' => (string) $receipt->receipt_number,
                        'amount' => number_format((float) $receipt->amount, 2),
                    ],
                    icon: 'heroicon-o-receipt-percent',
                    color: 'info',
                    excludeUserIds: [$user->id],
                    context: [
                        'routing_mode' => 'operations_targeted',
                        'event_key' => 'InvoiceFinancialReceiptRecorded',
                        'required_permission' => 'confirm_service_receipt',
                        'subject_type' => FinancialReceipt::class,
                        'subject_id' => $receipt->id,
                        'company_id' => (int) ($receipt->company_id ?? $source->company_id),
                        'branch_id' => $receipt->branch_id ?? $source->branch_id,
                        'is_action_request' => true,
                    ],
                );
            });
        }
    }

    public function onReceiptConfirmed(Model $source, FinancialReceipt $receipt, User $user): void
    {
        /** @var Invoice $source */
        $oldStatus = $source->payment_status?->value;
        $source = InvoiceFinancialService::sync($source);

        OperationsAuditService::recordFor(
            model: $source,
            actionType: 'financial_receipt_confirmed',
            oldStatus: $oldStatus,
            newStatus: $source->payment_status?->value,
            payload: [
                'invoice_number' => $source->invoice_number,
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
                    eventKey: 'InvoiceFinancialReceiptConfirmed',
                    titleKey: 'invoices.notifications.receipt_confirmed_title',
                    bodyKey: 'invoices.notifications.receipt_confirmed_body',
                    parameters: [
                        'invoice' => (string) $source->invoice_number,
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
                        'branch_id' => $source->branch_id,
                    ],
                );
            });
        }
    }

    public function onReceiptReversed(Model $source, FinancialReceipt $receipt, string $reason, ?User $user = null): void
    {
        /** @var Invoice $source */
        $oldStatus = $source->payment_status?->value;
        $source = InvoiceFinancialService::sync($source);
        $actorId = $user?->id ?? auth()->id();

        OperationsAuditService::recordFor(
            model: $source,
            actionType: 'financial_receipt_reversed',
            oldStatus: $oldStatus,
            newStatus: $source->payment_status?->value,
            payload: [
                'invoice_number' => $source->invoice_number,
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
                    eventKey: 'InvoiceFinancialReceiptReversed',
                    titleKey: 'invoices.notifications.receipt_reversed_title',
                    bodyKey: 'invoices.notifications.receipt_reversed_body',
                    parameters: [
                        'invoice' => (string) $source->invoice_number,
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
                        'branch_id' => $source->branch_id,
                    ],
                );
            });
        }
    }

    public function onHandoverChanged(Model $source): void
    {
        /** @var Invoice $source */
        InvoiceFinancialService::sync($source);
    }
}
