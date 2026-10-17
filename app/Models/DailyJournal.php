<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyJournal extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'entry_date',
        ];
    }


    protected $fillable = [
        'entry_number',
        'entry_date',
        'company_id',
        'branch_id',
        'purchases',
        'sales',
        'net_amount',
        'recorded_by',
        'notes',
        'is_detailed',
        'sales_cash',
        'sales_network',
        'sales_deferred',
        'sales_other',
        'purchases_cash',
        'purchases_network',
        'purchases_credit',
        'purchases_debit',
        'expenses_other',
        'metadata',
    ];

    protected $attributes = [
        'purchases' => 0,
        'sales' => 0,
        'net_amount' => 0,
        'is_detailed' => false,
    ];

    protected function casts(): array
    {
        return [
            'entry_date'  => 'date',
            'is_detailed' => 'boolean',
            // الإجماليات
            'purchases'   => 'decimal:2',
            'sales'       => 'decimal:2',
            'net_amount'  => 'decimal:2',
            // تفاصيل المبيعات
            'sales_cash'      => 'decimal:2',
            'sales_network'   => 'decimal:2',
            'sales_deferred'  => 'decimal:2',
            'sales_other'     => 'decimal:2',
            // تفاصيل المشتريات
            'purchases_cash'    => 'decimal:2',
            'purchases_network' => 'decimal:2',
            'purchases_credit'  => 'decimal:2',
            'purchases_debit'   => 'decimal:2',
            'expenses_other'    => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * احسب إجمالي المبيعات من التفاصيل.
     */
    public static function calcSalesFromDetails(array|self $data): float
    {
        $d = is_array($data) ? $data : $data->toArray();
        return array_sum(array_map(
            fn ($k) => (float) ($d[$k] ?? 0),
            ['sales_cash', 'sales_network', 'sales_deferred', 'sales_other']
        ));
    }

    /**
     * احسب إجمالي المشتريات من التفاصيل.
     */
    public static function calcPurchasesFromDetails(array|self $data): float
    {
        $d = is_array($data) ? $data : $data->toArray();
        return array_sum(array_map(
            fn ($k) => (float) ($d[$k] ?? 0),
            ['purchases_cash', 'purchases_network', 'purchases_credit', 'purchases_debit', 'expenses_other']
        ));
    }

    protected static function booted(): void
    {
        static::creating(function (DailyJournal $journal) {
            if (empty($journal->entry_number)) {
                $journal->entry_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'entry_number',
                    prefix: 'D',
                    companyId: $journal->company_id,
                    padLength: 4,
                );
            }

            if (empty($journal->entry_date)) {
                $journal->entry_date = now()->toDateString();
            }

            if (empty($journal->company_id)) {
                $user = auth()->user();
                $journal->company_id = $user?->company_id ?? $user?->employee?->company_id;
            }

            if (empty($journal->recorded_by)) {
                $journal->recorded_by = auth()->user()?->employee?->id;
            }

            // لو يومية مفصلة: احسب الإجماليات من التفاصيل تلقائياً
            if ($journal->is_detailed) {
                $journal->sales     = static::calcSalesFromDetails($journal);
                $journal->purchases = static::calcPurchasesFromDetails($journal);
            }

            $journal->net_amount = (float)($journal->sales ?? 0) - (float)($journal->purchases ?? 0);
        });

        static::updating(function (DailyJournal $journal) {
            // لو يومية مفصلة: احسب الإجماليات من التفاصيل تلقائياً
            if ($journal->is_detailed) {
                $journal->sales     = static::calcSalesFromDetails($journal);
                $journal->purchases = static::calcPurchasesFromDetails($journal);
            }

            $journal->net_amount = (float)($journal->sales ?? 0) - (float)($journal->purchases ?? 0);
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

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by');
    }
}
