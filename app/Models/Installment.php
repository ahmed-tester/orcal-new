<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\InstallmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installment extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'installment_date',
            'start_due_date',
            'final_due_date',
        ];
    }


    protected $fillable = [
        'installment_number',
        'transaction_type',
        'beneficiary_type',
        'installment_date',
        'company_id',
        'employee_id',
        'external_name',
        'external_national_id',
        'external_phone',
        'external_email',
        'external_address',
        'guarantor_id',
        'installment_type_id',
        'total_amount',
        'down_payment',
        'monthly_amount',
        'paid_amount',
        'remaining_amount',
        'months_count',
        'start_due_date',
        'final_due_date',
        'notify_employee_id',
        'notification_notes',
        'description',
        'executed_by',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'installment_date' => 'date',
            'start_due_date'   => 'date',
            'final_due_date'   => 'date',
            'total_amount'     => 'decimal:2',
            'down_payment'     => 'decimal:2',
            'monthly_amount'   => 'decimal:2',
            'paid_amount'      => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'status'           => InstallmentStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Installment $installment) {
            if (empty($installment->transaction_type)) {
                $installment->transaction_type = 'installment';
            }

            if (empty($installment->beneficiary_type)) {
                $installment->beneficiary_type = !empty($installment->external_name) ? 'external' : 'internal';
            }

            if ($installment->beneficiary_type !== 'external' && empty($installment->company_id)) {
                $installment->company_id = $installment->employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($installment->installment_number)) {
                $prefix = ($installment->transaction_type === 'advance') ? 'ADV' : 'INS';
                $installment->installment_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'installment_number',
                    prefix: $prefix,
                    companyId: $installment->company_id,
                );
            }

            if (empty($installment->installment_date)) {
                $installment->installment_date = now()->toDateString();
            }

            if (empty($installment->executed_by)) {
                $installment->executed_by = auth()->user()?->employee?->id;
            }
        });

        static::saving(function (Installment $installment) {
            static::calculateTotals($installment);
        });
    }

    public function getBeneficiaryNameAttribute(): string
    {
        if ($this->beneficiary_type === 'external' || !empty($this->external_name)) {
            return $this->external_name ?? 'طرف خارجي';
        }

        return $this->employee?->user?->name ?? 'غير محدد';
    }

    public function getBeneficiaryPhoneAttribute(): ?string
    {
        if ($this->beneficiary_type === 'external' || !empty($this->external_phone)) {
            return $this->external_phone;
        }

        return $this->employee?->user?->phone ?? $this->employee?->phone ?? null;
    }

    public function isAdvance(): bool
    {
        return $this->transaction_type === 'advance';
    }

    public function isExternal(): bool
    {
        return $this->beneficiary_type === 'external';
    }

    public static function calculateTotals(Installment $installment): void
    {
        $installment->total_amount = (float)($installment->total_amount ?? 0);
        $installment->down_payment = (float)($installment->down_payment ?? 0);
        $installment->paid_amount = (float)($installment->paid_amount ?? 0);
        $installment->months_count = max(1, (int)($installment->months_count ?: 1));

        $total = $installment->total_amount;
        $down = $installment->down_payment;
        $paid = $installment->paid_amount;
        $months = $installment->months_count;

        if (empty($installment->monthly_amount) || (float)$installment->monthly_amount == 0) {
            $netToDivide = max(0, $total - $down);
            $installment->monthly_amount = round($netToDivide / $months, 2);
        } else {
            $installment->monthly_amount = (float)$installment->monthly_amount;
        }

        $remaining = max(0, $total - $down - $paid);
        $installment->remaining_amount = round($remaining, 2);

        if ($remaining <= 0 && $total > 0) {
            $installment->status = InstallmentStatus::COMPLETED;
        } elseif ($installment->status === InstallmentStatus::COMPLETED && $remaining > 0) {
            $installment->status = InstallmentStatus::OPEN;
        }
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function installmentType(): BelongsTo
    {
        return $this->belongsTo(InstallmentType::class);
    }

    public function notifyEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'notify_employee_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(InstallmentSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InstallmentPayment::class);
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }
}
