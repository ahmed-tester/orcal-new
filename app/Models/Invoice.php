<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'issue_date',
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
}
