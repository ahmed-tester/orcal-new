<?php

namespace App\Models;

use App\Enums\OperationWorkflowStatus;
use App\Observers\ServiceTransactionObserver;
use App\Services\CodeGeneratorService;
use App\Services\FinancialSummaryService;
use App\Services\ServiceFinancialWorkflowService;
use App\Traits\HasApprovalStatus;
use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ServiceTransactionObserver::class])]
class ServiceTransaction extends Model
{
    use HasApprovalStatus, HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'transaction_date',
            'due_date',
            'completion_date',
        ];
    }

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'scope_type',
        'service_target',
        'company_id',
        'employee_id',
        'service_id',
        'service_name',
        'service_type',
        'first_party',
        'beneficiary_name',
        'beneficiary_phone',
        'beneficiary_national_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'service_status',
        'workflow_status',
        'executed_by',
        'due_date',
        'completion_date',
        'notes',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'due_date' => 'date',
            'completion_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * Resolve unified workflow status according to the operational lifecycle hierarchy.
     * Hierarchy: Escalated -> Suspense -> Mismatched -> Awaiting Approval -> Retroactive -> Completed -> Treasury -> Custody -> In Progress.
     */
    public function resolveWorkflowStatus(): string
    {
        $meta = $this->metadata ?? [];

        if (! empty($meta['escalated_at'])) {
            return OperationWorkflowStatus::ESCALATED->value;
        }

        if (! empty($meta['suspense_amount_id']) && ! ServiceFinancialWorkflowService::isManaged($this)) {
            return OperationWorkflowStatus::SUSPENSE_AMOUNT->value;
        }

        if (($meta['reconciliation_status'] ?? '') === 'matched') {
            return OperationWorkflowStatus::COMPLETED->value;
        }

        if (! empty($meta['mismatch_reason']) || in_array(($meta['reconciliation_status'] ?? ''), ['mismatched', 'amount_diff', 'missing_document', 'missing_custody', 'missing_counterparty'], true)) {
            return OperationWorkflowStatus::MISMATCHED->value;
        }

        if (! empty($meta['approval_requested_at'])) {
            return OperationWorkflowStatus::AWAITING_APPROVAL->value;
        }

        if (! empty($meta['retroactive']) || ($meta['settlement_mode'] ?? '') === 'retroactive') {
            return OperationWorkflowStatus::RETROACTIVE_SETTLEMENT->value;
        }

        if ($this->service_status === 'completed') {
            return OperationWorkflowStatus::COMPLETED->value;
        }

        $custodyStatus = $meta['custody_status'] ?? '';

        if ($custodyStatus === 'in_treasury') {
            return OperationWorkflowStatus::AT_TREASURY->value;
        }

        if ($custodyStatus === 'on_employee' && (float) $this->paid_amount > 0) {
            return OperationWorkflowStatus::EMPLOYEE_CUSTODY->value;
        }

        return OperationWorkflowStatus::IN_PROGRESS->value;
    }

    protected static function booted(): void
    {
        static::creating(function (ServiceTransaction $transaction) {
            if (empty($transaction->scope_type)) {
                $transaction->scope_type = ! empty($transaction->service_name) ? 'external' : 'internal';
            }

            if ($transaction->scope_type === 'internal' && empty($transaction->company_id)) {
                $transaction->company_id = $transaction->employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'transaction_number',
                    prefix: 'SRV',
                    companyId: $transaction->company_id,
                );
            }

            if (empty($transaction->transaction_date)) {
                $transaction->transaction_date = now()->toDateString();
            }

            if (empty($transaction->executed_by)) {
                $transaction->executed_by = auth()->user()?->employee?->id;
            }

            $meta = $transaction->metadata ?? [];
            if (empty($meta['employee_signature_code'])) {
                $meta['employee_signature_code'] = (string) rand(1000, 9999);
                $transaction->metadata = $meta;
            }
        });

        static::saving(function (ServiceTransaction $transaction) {
            $total = (float) ($transaction->total_amount ?? 0);
            $paid = (float) ($transaction->paid_amount ?? 0);

            if (ServiceFinancialWorkflowService::isManaged($transaction)) {
                $clientDue = (float) ($transaction->metadata['client_due_amount'] ?? $total);
                $remaining = max(0, round($clientDue - $paid, 2));

                $transaction->total_amount = $total;
                $transaction->paid_amount = $paid;
                $transaction->remaining_amount = $remaining;

                if ($clientDue > 0 && $remaining <= 0 && $paid >= $clientDue) {
                    $transaction->payment_status = 'paid';
                } elseif ($paid > 0) {
                    $transaction->payment_status = 'partial';
                } else {
                    $transaction->payment_status = 'unpaid';
                }

                if ($transaction->service_status === 'completed' && empty($transaction->completion_date)) {
                    $transaction->completion_date = now()->toDateString();
                }

                return;
            }

            $remaining = max(0, $total - $paid);

            $transaction->total_amount = $total;
            $transaction->paid_amount = $paid;
            $transaction->remaining_amount = $remaining;

            if ($total > 0 && $paid >= $total) {
                $transaction->payment_status = 'paid';
            } elseif ($paid > 0) {
                $transaction->payment_status = 'partial';
            } else {
                $transaction->payment_status = 'unpaid';
            }

            if ($transaction->service_status === 'completed' && empty($transaction->completion_date)) {
                $transaction->completion_date = now()->toDateString();
            }

            $meta = $transaction->metadata ?? [];
            $costBearer = $meta['cost_bearer'] ?? 'client_paid';

            if (($costBearer === 'client_paid' || $costBearer === 'shared') && $paid > 0) {
                if (empty($meta['custody_status'])) {
                    $meta['custody_status'] = 'on_employee';
                }
            } elseif ($costBearer === 'client_debt' || $costBearer === 'office_liability') {
                $meta['custody_status'] = 'not_applicable';
            }

            $hasProof = ! empty($meta['payment_proof']) || (! empty($meta['gov_payment_receipt']) && ! empty($meta['bank_deposit_receipt']));
            $custodyResolved = in_array($meta['custody_status'] ?? '', ['in_treasury', 'settled', 'not_applicable'], true);
            if ($hasProof && $custodyResolved) {
                $meta['reconciliation_status'] = 'matched';
            } else {
                $meta['reconciliation_status'] = 'pending_proofs';
            }

            $transaction->metadata = $meta;
        });
    }

    public function getCustodyStatusAttribute(): string
    {
        return $this->metadata['custody_status'] ?? 'not_applicable';
    }

    public function getCostBearerAttribute(): string
    {
        return $this->metadata['cost_bearer'] ?? 'client_paid';
    }

    public function getGovFeeAmountAttribute(): float
    {
        return (float) ($this->metadata['gov_fee_amount'] ?? 0);
    }

    public function getOfficeFeeAmountAttribute(): float
    {
        return (float) ($this->metadata['office_fee_amount'] ?? 0);
    }

    public function getReconciliationStatusAttribute(): string
    {
        return $this->metadata['reconciliation_status'] ?? 'pending_proofs';
    }

    public function isFinancialManaged(): bool
    {
        return ServiceFinancialWorkflowService::isManaged($this);
    }

    public function hasFinancialRecords(): bool
    {
        return $this->financialReceipts()->exists()
            || $this->financialDisbursements()->exists()
            || (float) ($this->paid_amount ?? 0) > 0;
    }

    /**
     * @return array<string, float|int|bool|null>
     */
    public function financialSummary(): array
    {
        return FinancialSummaryService::for($this);
    }

    public function financialReceipts(): HasMany
    {
        return $this->hasMany(FinancialReceipt::class, 'source_id')
            ->where('source_type', FinancialReceipt::SOURCE_SERVICE_TRANSACTION);
    }

    public function financialDisbursements(): HasMany
    {
        return $this->hasMany(FinancialDisbursement::class, 'source_id')
            ->where('source_type', FinancialDisbursement::SOURCE_SERVICE_TRANSACTION);
    }

    public function getDisplayServiceNameAttribute(): string
    {
        if ($this->scope_type === 'external' || ! empty($this->service_name)) {
            return $this->service_name ?? __('services_tracking.badges.external');
        }

        return $this->service?->name ?? '—';
    }

    public function getDisplayBeneficiaryAttribute(): string
    {
        if (filled($this->beneficiary_name)) {
            return $this->beneficiary_name;
        }

        if ($this->service_target === 'employee' && $this->employee) {
            return $this->employee->user?->name ?? (__('services_tracking.badges.employee')." #{$this->employee->id}");
        }

        return $this->company?->trade_name ?: ($this->company?->name ?? '—');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }

    public function deficiencyTasks(): HasMany
    {
        return $this->hasMany(UnifiedDeficiencyTask::class, 'service_transaction_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(UnifiedAuditLog::class, 'service_transaction_id')->orderBy('created_at', 'desc');
    }
}
