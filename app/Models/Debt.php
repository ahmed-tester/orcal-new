<?php

namespace App\Models;

use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'debt_date',
            'due_date',
        ];
    }

    protected $fillable = [
        'debt_number',
        'debt_date',
        'due_date',
        'debtor_type',
        'company_id',
        'employee_id',
        'external_name',
        'external_national_id',
        'external_phone',
        'external_email',
        'external_address',
        'guarantor_id',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'debt_reason',
        'description',
        'status',
        'notify_employee_id',
        'notification_notes',
        'executed_by',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'debt_date'        => 'date',
            'due_date'         => 'date',
            'total_amount'     => 'decimal:2',
            'paid_amount'      => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'metadata'         => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Debt $debt) {
            if (empty($debt->debtor_type)) {
                $debt->debtor_type = !empty($debt->external_name) ? 'external' : 'internal';
            }

            if ($debt->debtor_type !== 'external' && empty($debt->company_id)) {
                $debt->company_id = $debt->employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
            }

            if (empty($debt->debt_number)) {
                $debt->debt_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'debt_number',
                    prefix: 'DBT',
                    companyId: $debt->company_id,
                );
            }

            if (empty($debt->debt_date)) {
                $debt->debt_date = now()->toDateString();
            }

            if (empty($debt->executed_by)) {
                $debt->executed_by = auth()->user()?->employee?->id;
            }
        });

        static::saving(function (Debt $debt) {
            static::calculateTotals($debt);
        });
    }

    public function getBeneficiaryNameAttribute(): string
    {
        if ($this->debtor_type === 'external' || !empty($this->external_name)) {
            return $this->external_name ?? __('debts.badges.external');
        }

        if ($this->employee?->user?->name) {
            return $this->employee->user->name;
        }

        return $this->company?->name ?? '—';
    }

    public function getBeneficiaryPhoneAttribute(): ?string
    {
        if ($this->debtor_type === 'external' || !empty($this->external_phone)) {
            return $this->external_phone;
        }

        return $this->employee?->user?->phone ?? $this->employee?->phone ?? null;
    }

    public function isExternal(): bool
    {
        return $this->debtor_type === 'external';
    }

    public static function calculateTotals(Debt $debt): void
    {
        $total = (float)($debt->total_amount ?? 0);
        $paid = (float)($debt->paid_amount ?? 0);
        $remaining = max(0, $total - $paid);

        $debt->total_amount = $total;
        $debt->paid_amount = $paid;
        $debt->remaining_amount = $remaining;

        if ($total > 0 && $paid >= $total) {
            $debt->status = 'paid';
        } elseif ($paid > 0) {
            $debt->status = 'partial';
        } elseif (!empty($debt->due_date) && now()->startOfDay()->gt(\Carbon\Carbon::parse($debt->due_date)->startOfDay())) {
            $debt->status = 'overdue';
        } else {
            $debt->status = 'pending';
        }
    }

    public function recalculateAndSave(): void
    {
        $this->paid_amount = (float)$this->collections()->sum('amount');
        static::calculateTotals($this);
        $this->saveQuietly();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(Guarantor::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(DebtCollection::class);
    }

    public function notifyEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'notify_employee_id');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'executed_by');
    }
}
