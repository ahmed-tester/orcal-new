<?php

namespace App\Services;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\PaymentStatus;
use App\Models\Debt;
use App\Models\FinancialDisbursement;
use App\Models\Invoice;
use App\Models\ServiceTransaction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Service-side connector of the unified financial workflow. It owns the
 * service financial snapshot, activation and compatibility fields only; the
 * money math lives in FinancialSummaryService.
 */
class ServiceFinancialWorkflowService
{
    public const WORKFLOW_VERSION = 1;

    public const FLOW_KEY = 'financial_workflow_version';

    public const FLOW_MANAGED = 'managed';

    /**
     * @return array<string, mixed>
     */
    public static function snapshot(ServiceTransaction $transaction): array
    {
        return (array) ($transaction->metadata ?? []);
    }

    public static function isManaged(ServiceTransaction $transaction): bool
    {
        return (int) ($transaction->metadata[self::FLOW_KEY] ?? 0) === self::WORKFLOW_VERSION;
    }

    public static function assertManaged(ServiceTransaction $transaction): void
    {
        if (! static::isManaged($transaction)) {
            throw ValidationException::withMessages([
                'financial' => __('financial.errors.workflow_not_active'),
            ]);
        }
    }

    /**
     * @return array{0: float, 1: float}
     */
    public static function resolveShares(array $meta, float $total): array
    {
        $total = round(max(0, $total), 2);
        $costBearer = $meta['cost_bearer'] ?? 'client_paid';

        [$client, $office] = match ($costBearer) {
            'office_liability' => [0.0, $total],
            'shared' => [
                min($total, max(0, round((float) ($meta['client_share'] ?? 0), 2))),
                null,
            ],
            default => [$total, 0.0],
        };

        if ($office === null) {
            $office = round(max(0, $total - $client), 2);
        }

        return [round($client, 2), round($office, 2)];
    }

    /**
     * Create the financial snapshot for a new transaction. It never trusts the
     * legacy paid_amount field and never moves money.
     */
    public static function activate(ServiceTransaction $transaction, ?User $actor = null): void
    {
        if (($transaction->metadata['financial_flow'] ?? null) === 'legacy') {
            return;
        }

        $meta = $transaction->metadata ?? [];
        $total = round((float) $transaction->total_amount, 2);
        [$clientShare, $officeShare] = static::resolveShares($meta, $total);

        $meta['client_share'] = $clientShare;
        $meta['office_share'] = $officeShare;

        $explicitFee = array_key_exists('gov_fee_expected', $meta);
        $feeValue = $explicitFee
            ? $meta['gov_fee_expected']
            : ($transaction->service?->default_gov_fee ?? null);

        $expectedFee = ($feeValue === null || $feeValue === '') ? null : round(max(0, (float) $feeValue), 2);

        $meta[self::FLOW_KEY] = self::WORKFLOW_VERSION;
        $meta['financial_flow'] = self::FLOW_MANAGED;
        $meta['service_total'] = $total;
        $meta['client_due_amount'] = $clientShare;
        $meta['expected_fee_amount'] = $expectedFee;
        $meta['expected_fee_source'] = $expectedFee === null
            ? 'undefined'
            : ($explicitFee ? 'explicit' : 'service_estimate');
        $meta['fee_beneficiary_reference'] = $meta['fee_beneficiary_reference'] ?? null;
        $meta['terms_approved_by'] = $meta['terms_approved_by'] ?? $actor?->id ?? auth()->id();
        $meta['terms_approved_at'] = $meta['terms_approved_at'] ?? now()->toDateTimeString();
        $meta['custody_status'] = 'not_applicable';

        $transaction->metadata = $meta;
        $transaction->paid_amount = 0;
        $transaction->remaining_amount = $clientShare;
        $transaction->payment_status = $clientShare > 0 ? 'unpaid' : 'paid';
        $transaction->saveQuietly();
    }

    /**
     * Recalculate the client-facing fields from actual confirmed receipts.
     */
    public static function sync(ServiceTransaction $transaction): ServiceTransaction
    {
        $summary = FinancialSummaryService::for($transaction);

        $meta = $transaction->metadata ?? [];

        if ((float) $summary['cash_received'] > 0) {
            $meta['custody_status'] = (float) $summary['custody'] > 0 ? 'on_employee' : 'in_treasury';
        } elseif ((float) $summary['received'] > 0) {
            $meta['custody_status'] = 'not_applicable';
        }

        $applied = (float) $summary['applied_to_client'];
        $remaining = (float) $summary['client_remaining'];

        $meta['financial_synced_at'] = now()->toDateTimeString();

        $transaction->metadata = $meta;
        $transaction->paid_amount = $applied;
        $transaction->remaining_amount = $remaining;
        $transaction->payment_status = match (true) {
            $remaining <= 0 && $applied >= (float) $summary['client_due'] => 'paid',
            $applied > 0 => 'partial',
            default => 'unpaid',
        };
        $transaction->workflow_status = $transaction->resolveWorkflowStatus();
        $transaction->saveQuietly();

        static::syncLinkedDocuments($transaction, $summary);

        return $transaction;
    }

    /**
     * Switch the service to the managed financial path, reusing the snapshot
     * already created at activation.
     */
    public static function activateExisting(ServiceTransaction $transaction, ?User $actor = null): ServiceTransaction
    {
        if (! static::isManaged($transaction)) {
            static::activate($transaction, $actor);
        }

        $transaction->refresh();

        return static::sync($transaction);
    }

    public static function authorize(ServiceTransaction $transaction, string $permission, ?User $user = null): User
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            throw new AuthorizationException(__('financial.errors.unauthenticated'));
        }

        static::authorizeCompany($user, (int) $transaction->company_id, $permission);

        $branchId = $transaction->employee?->branch_id ?? $transaction->executor?->branch_id;

        if ($branchId && $user->branch_id && ! $user->canAccessAllBranches()
            && (int) $user->branch_id !== (int) $branchId) {
            throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
        }

        return $user;
    }

    public static function authorizeCompany(User $user, int $companyId, string $permission): void
    {
        if (! $user->isSuperAdmin()) {
            $authorized = $user->getAccessibleCompanyIds();

            if ($authorized === [] || ! in_array($companyId, array_map('intval', $authorized), true)) {
                throw new AuthorizationException(__('financial.errors.out_of_company_scope'));
            }
        }

        if (! $user->isSuperAdmin() && ! $user->can($permission)) {
            throw new AuthorizationException(__('financial.errors.missing_permission'));
        }
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private static function syncLinkedDocuments(ServiceTransaction $transaction, array $summary): void
    {
        $applied = (float) $summary['applied_to_client'];
        $remaining = (float) $summary['client_remaining'];

        if (! empty($transaction->metadata['auto_invoice_id'])) {
            $invoice = Invoice::find($transaction->metadata['auto_invoice_id']);

            if ($invoice) {
                $invoice->total_amount = (float) $summary['client_due'];
                $invoice->paid_amount = $applied;
                $invoice->remaining_amount = $remaining;
                $invoice->payment_status = match (true) {
                    $remaining <= 0 && $applied > 0 => PaymentStatus::PAID,
                    $applied > 0 => PaymentStatus::PARTIALLY_PAID,
                    default => PaymentStatus::UNPAID,
                };
                $invoice->saveQuietly();
            }
        }

        if (! empty($transaction->metadata['auto_debt_id'])) {
            $debt = Debt::find($transaction->metadata['auto_debt_id']);

            if ($debt) {
                $debt->paid_amount = $applied;
                $debt->remaining_amount = $remaining;
                $debt->status = $remaining <= 0 ? 'paid' : 'pending';
                $debt->saveQuietly();
            }
        }
    }

    /**
     * Snapshot numbers exposed to the summary widget and reconciliation.
     *
     * @return array<string, mixed>
     */
    public static function displaySnapshot(ServiceTransaction $transaction): array
    {
        $meta = $transaction->metadata ?? [];

        return [
            'client_due_amount' => (float) ($meta['client_due_amount'] ?? $transaction->total_amount),
            'expected_fee_amount' => array_key_exists('expected_fee_amount', $meta) && $meta['expected_fee_amount'] !== null
                ? (float) $meta['expected_fee_amount']
                : null,
            'expected_fee_source' => $meta['expected_fee_source'] ?? 'undefined',
            'fee_beneficiary_reference' => $meta['fee_beneficiary_reference'] ?? null,
            'terms_approved_at' => $meta['terms_approved_at'] ?? null,
            'version' => (int) ($meta[self::FLOW_KEY] ?? 0),
        ];
    }

    /**
     * Whether the employee still owes cash custody on this transaction.
     */
    public static function hasOpenCustody(ServiceTransaction $transaction): bool
    {
        return (float) FinancialSummaryService::for($transaction)['custody'] > 0;
    }

    /**
     * Whether the transaction has an approved personal claim still unpaid.
     */
    public static function hasEmployeeDue(ServiceTransaction $transaction): bool
    {
        return (float) FinancialSummaryService::for($transaction)['employee_due'] > 0;
    }

    /**
     * Update the workflow status only when there is an actual change.
     */
    public static function touchWorkflow(ServiceTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $locked = ServiceTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            static::sync($locked);
            $transaction->setRawAttributes($locked->getAttributes(), true);
        });
    }

    public static function pendingClaimsCount(ServiceTransaction $transaction): int
    {
        return FinancialDisbursement::forSource(FinancialSummaryService::SOURCE_TYPE, (int) $transaction->id)
            ->where('status', FinancialDisbursementStatus::PENDING_APPROVAL->value)
            ->count();
    }
}
