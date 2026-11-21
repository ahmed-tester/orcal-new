<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\ServiceTransaction;
use App\Services\CompanyFormService;
use App\Services\OperationsAuditService;
use App\Services\OperationsReconciliationService;
use App\Services\ServiceFinancialWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceTransactionObserver
{
    /**
     * Automatically create corresponding financial records upon transaction creation:
     * 1. Automatic invoice reflecting total transaction amount.
     * 2. Automatic client debt for remaining owed balance.
     */
    public function created(ServiceTransaction $transaction): void
    {
        ServiceFinancialWorkflowService::activate($transaction);

        $meta = $transaction->metadata ?? [];

        DB::transaction(function () use (&$meta, $transaction) {
            $invoiceId = $this->createAutoInvoice($transaction);
            if ($invoiceId) {
                $meta['auto_invoice_id'] = $invoiceId;
            }

            $debtId = $this->createClientDebt($transaction);
            if ($debtId) {
                $meta['auto_debt_id'] = $debtId;
            }
        });

        if ($meta !== ($transaction->metadata ?? [])) {
            $transaction->metadata = $meta;
            $transaction->saveQuietly();
        }

        OperationsAuditService::record(
            companyId: $transaction->company_id ?? CompanyFormService::getDefaultCompanyId() ?? 1,
            transactionId: $transaction->id,
            actionType: 'created',
            oldStatus: null,
            newStatus: $transaction->workflow_status,
            payload: [
                'total_amount' => (float) $transaction->total_amount,
                'paid_amount' => (float) $transaction->paid_amount,
                'scope_type' => $transaction->scope_type,
            ],
        );
    }

    public function updating(ServiceTransaction $transaction): void
    {
        // Guard 1: Completion guard - block setting to completed if not matched
        if ($transaction->isDirty('service_status') && $transaction->service_status === 'completed') {
            $evaluation = OperationsReconciliationService::evaluate($transaction);
            if ($evaluation['status'] !== 'matched') {
                throw ValidationException::withMessages([
                    'service_status' => __('services_tracking.settlement.guard_cannot_complete'),
                ]);
            }
        }

        // Guard 2: Cancellation guard - block cancelling if cash custody is on employee
        if ($transaction->isDirty('status') && $transaction->status === 'cancelled') {
            $custodyStatus = $transaction->metadata['custody_status'] ?? 'not_applicable';
            if ($custodyStatus === 'on_employee' && (float) $transaction->paid_amount > 0) {
                throw ValidationException::withMessages([
                    'status' => __('services_tracking.settlement.guard_cannot_cancel_with_custody'),
                ]);
            }
        }
    }

    /**
     * Resolve workflow status on model saving if not manually overridden.
     */
    public function saving(ServiceTransaction $transaction): void
    {
        if (! $transaction->isDirty('workflow_status')) {
            $transaction->workflow_status = $transaction->resolveWorkflowStatus();
        }
    }

    /**
     * Prevent deleting transactions with financial receipts, disbursements or payments.
     */
    public function deleting(ServiceTransaction $transaction): void
    {
        if ($transaction->hasFinancialRecords()) {
            throw ValidationException::withMessages([
                'delete' => __('financial.errors.cannot_delete_with_financial_records'),
            ]);
        }
    }

    /**
     * The client share of the service, never the office share.
     */
    private function clientDueAmount(ServiceTransaction $transaction): float
    {
        if (ServiceFinancialWorkflowService::isManaged($transaction)) {
            return (float) ($transaction->metadata['client_due_amount'] ?? $transaction->total_amount);
        }

        return (float) $transaction->total_amount;
    }

    /**
     * Generate automatic invoice for the transaction.
     */
    private function createAutoInvoice(ServiceTransaction $transaction): ?int
    {
        if ((float) $transaction->total_amount <= 0) {
            return null;
        }

        $companyId = $transaction->company_id ?? CompanyFormService::getDefaultCompanyId();
        if (empty($companyId)) {
            logger()->warning('ServiceTransactionObserver: invoice skipped, no company.', [
                'transaction_number' => $transaction->transaction_number,
            ]);

            return null;
        }

        $invoicePaymentStatus = match ($transaction->payment_status) {
            'paid' => PaymentStatus::PAID,
            'partial', 'partially_paid' => PaymentStatus::PARTIALLY_PAID,
            default => PaymentStatus::UNPAID,
        };

        $invoice = Invoice::create([
            'company_id' => $companyId,
            'employee_id' => $transaction->employee_id,
            'issue_date' => $transaction->transaction_date ?? now()->toDateString(),
            'total_amount' => $this->clientDueAmount($transaction),
            'paid_amount' => (float) $transaction->paid_amount,
            'remaining_amount' => (float) $transaction->remaining_amount,
            'payment_status' => $invoicePaymentStatus,
            'is_paid_by_applicant' => true,
            'created_by' => auth()->id(),
            'notes' => __('services_tracking.observer.auto_invoice_notes', [
                'number' => $transaction->transaction_number,
            ]),
        ]);

        return $invoice->id;
    }

    /**
     * Generate automatic debt record for owed client balance.
     */
    private function createClientDebt(ServiceTransaction $transaction): ?int
    {
        $costBearer = $transaction->metadata['cost_bearer'] ?? 'client_paid';
        if (! in_array($costBearer, ['client_paid', 'client_debt'], true)) {
            return null;
        }

        $clientOwed = (float) $transaction->remaining_amount;
        if ($clientOwed <= 0) {
            return null;
        }

        $companyId = $transaction->company_id ?? CompanyFormService::getDefaultCompanyId();
        if (empty($companyId)) {
            return null;
        }

        $isExternal = $transaction->scope_type === 'external';
        $reason = __('services_tracking.observer.client_debt_reason', [
            'number' => $transaction->transaction_number,
        ]);

        $debt = Debt::create([
            'debtor_type' => $isExternal ? 'external' : 'internal',
            'company_id' => $companyId,
            'employee_id' => $isExternal ? null : $transaction->employee_id,
            'external_name' => $isExternal ? $transaction->beneficiary_name : null,
            'external_national_id' => $isExternal ? $transaction->beneficiary_national_id : null,
            'external_phone' => $isExternal ? $transaction->beneficiary_phone : null,
            'total_amount' => $clientOwed,
            'paid_amount' => 0,
            'due_date' => $transaction->due_date,
            'debt_reason' => $reason,
            'description' => $reason,
            'status' => 'pending',
            'metadata' => [
                'source' => 'service_transaction',
                'service_transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
            ],
        ]);

        return $debt->id;
    }
}
