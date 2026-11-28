<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasHijriDates;

    public const FLOW_KEY = 'financial_workflow_version';

    public const WORKFLOW_VERSION = 1;

    public const FLOW_MANAGED = 'managed';

    public const PURPOSE_RENEWAL_EXPENSE = 'renewal_expense';

    public const RENEWAL_FEE_WITH_FEE = 'with_fee';

    public const RENEWAL_FEE_NO_FEE = 'no_fee';

    public function getHijriDateFieldNames(): array
    {
        return [
            'issue_date',
            'due_date',
        ];
    }

    protected $fillable = [
        'invoice_number',
        'company_id',
        'branch_id',
        'invoice_type_id',
        'customer_id',
        'employee_id',
        'issue_date',
        'due_date',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'payment_status',
        'is_paid_by_applicant',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'is_paid_by_applicant' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function getDueDateAttribute(): ?Carbon
    {
        $value = $this->metadata['due_date'] ?? null;

        return filled($value) ? Carbon::parse($value) : null;
    }

    public function setDueDateAttribute(mixed $value): void
    {
        $meta = $this->metadata ?? [];
        if (! is_array($meta)) {
            $meta = [];
        }

        if (filled($value)) {
            $meta['due_date'] = Carbon::parse($value)->toDateString();
        } else {
            unset($meta['due_date']);
        }

        $this->metadata = $meta;
    }

    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();
        $dueDate = $this->metadata['due_date'] ?? null;

        if (filled($dueDate)) {
            $attributes['due_date'] = $dueDate;
        }

        return $attributes;
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (blank($invoice->invoice_number)) {
                $invoice->invoice_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'invoice_number',
                    prefix: 'INV',
                    companyId: $invoice->company_id,
                    padLength: 4,
                    datePart: 'Ymd',
                );
            }
        });

        static::saving(function (Invoice $invoice) {
            $invoice->total_amount = (float)($invoice->total_amount ?? 0);
            $invoice->paid_amount = (float)($invoice->paid_amount ?? 0);
            $invoice->remaining_amount = max(0, $invoice->total_amount - $invoice->paid_amount);
            if ($invoice->paid_amount >= $invoice->total_amount && $invoice->total_amount > 0) {
                $invoice->payment_status = PaymentStatus::PAID;
            } elseif ($invoice->paid_amount > 0) {
                $invoice->payment_status = PaymentStatus::PARTIALLY_PAID;
            } else {
                $invoice->payment_status = PaymentStatus::UNPAID;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function invoiceType(): BelongsTo
    {
        return $this->belongsTo(InvoiceType::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function financialReceipts(): HasMany
    {
        return $this->hasMany(FinancialReceipt::class, 'source_id')
            ->where('source_type', FinancialReceipt::SOURCE_INVOICE);
    }

    public function financialSourceType(): string
    {
        return FinancialReceipt::SOURCE_INVOICE;
    }

    public function isManaged(): bool
    {
        return (int) ($this->metadata[self::FLOW_KEY] ?? 0) === self::WORKFLOW_VERSION;
    }

    public function isServiceMirror(): bool
    {
        if (! empty($this->metadata['service_transaction_id'])
            || ($this->metadata['financial_flow'] ?? null) === 'service_mirror'
            || ($this->metadata['source'] ?? null) === FinancialReceipt::SOURCE_SERVICE_TRANSACTION) {
            return true;
        }

        if (! $this->exists) {
            return false;
        }

        return ServiceTransaction::where('metadata->auto_invoice_id', $this->id)->exists();
    }

    public function linkedServiceTransaction(): ?ServiceTransaction
    {
        $id = $this->metadata['service_transaction_id'] ?? null;

        if ($id) {
            return ServiceTransaction::find($id);
        }

        if (! $this->exists) {
            return null;
        }

        return ServiceTransaction::where('metadata->auto_invoice_id', $this->id)->first();
    }

    public function isRenewalExpense(): bool
    {
        return ($this->metadata['document_purpose'] ?? null) === self::PURPOSE_RENEWAL_EXPENSE;
    }

    public function scopeRenewalExpenses(Builder $query): Builder
    {
        return $query->where('metadata->document_purpose', self::PURPOSE_RENEWAL_EXPENSE);
    }

    public function scopeExcludingRenewalExpenses(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereNull('metadata->document_purpose')
                ->orWhere('metadata->document_purpose', '!=', self::PURPOSE_RENEWAL_EXPENSE);
        });
    }

    public function hasFinancialImpact(): bool
    {
        if ((float) $this->paid_amount > 0) {
            return true;
        }

        if ($this->exists && FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $this->id)->exists()) {
            return true;
        }

        $meta = $this->metadata ?? [];

        return ! empty($meta['reversed_at']) || ! empty($meta['handover_id']) || ! empty($meta['treasury_transaction_id']);
    }
}
