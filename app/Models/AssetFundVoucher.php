<?php

namespace App\Models;

use App\Enums\AssetFundSourceType;
use App\Enums\AssetFundVoucherType;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetFundVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_fund_id',
        'voucher_number',
        'type',
        'disbursement_type',
        'linked_deposit_voucher_id',
        'voucher_date',
        'total_amount',
        'source_type',
        'company_id',
        'party_name',
        'contractor_name',
        'tax_invoice_number',
        'gross_amount',
        'tax_amount',
        'net_after_tax',
        'retention_percentage',
        'retention_amount',
        'fund_share_percentage',
        'fund_share_amount',
        'net_after_deductions',
        'expense_reason',
        'amount_without_tax',
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'total_amount' => 0,
        'tax_amount' => 0,
        'retention_amount' => 0,
        'fund_share_amount' => 0,
        'source_type' => 'free',
        'disbursement_type' => 'general',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssetFundVoucherType::class,
            'source_type' => AssetFundSourceType::class,
            'voucher_date' => 'date',
            'total_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'net_after_tax' => 'decimal:2',
            'retention_percentage' => 'decimal:2',
            'retention_amount' => 'decimal:2',
            'fund_share_percentage' => 'decimal:2',
            'fund_share_amount' => 'decimal:2',
            'net_after_deductions' => 'decimal:2',
            'amount_without_tax' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AssetFundVoucher $voucher) {
            if (empty($voucher->voucher_number)) {
                $type = $voucher->type instanceof AssetFundVoucherType
                    ? $voucher->type->value
                    : (string) $voucher->type;

                $prefix = $type === AssetFundVoucherType::WITHDRAWAL->value ? 'WDR' : 'DEP';

                $voucher->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: $prefix,
                    companyId: null,
                    scopeQuery: fn ($query) => $query->where('voucher_number', 'like', $prefix . '-%'),
                );
            }

            if (empty($voucher->created_by)) {
                $voucher->created_by = auth()->id();
            }
        });

        static::saving(function (AssetFundVoucher $voucher) {
            $source = $voucher->source_type instanceof AssetFundSourceType
                ? $voucher->source_type->value
                : (string) ($voucher->source_type ?? 'free');

            if ($source === 'free') {
                $voucher->company_id = null;
            } else {
                $voucher->party_name = null;
            }

            $type = $voucher->type instanceof AssetFundVoucherType
                ? $voucher->type->value
                : (string) ($voucher->type ?? 'deposit');

            if ($type === 'deposit') {
                $voucher->disbursement_type = 'general';
                $voucher->linked_deposit_voucher_id = null;

                if ($voucher->gross_amount !== null) {
                    $gross = (float) $voucher->gross_amount;
                    $tax = (float) ($voucher->tax_amount ?? 0);
                    $net = round($gross - $tax, 2);
                    $voucher->net_after_tax = $net;

                    $retPct = (float) ($voucher->retention_percentage ?? 0);
                    $retAmt = round($net * ($retPct / 100), 2);
                    $voucher->retention_amount = $retAmt;

                    $fndPct = (float) ($voucher->fund_share_percentage ?? 0);
                    $fndAmt = round($net * ($fndPct / 100), 2);
                    $voucher->fund_share_amount = $fndAmt;

                    $afterDeductions = round($net - $retAmt - $fndAmt, 2);
                    $voucher->net_after_deductions = $afterDeductions;
                    $voucher->total_amount = $afterDeductions;
                }
            } elseif ($type === 'withdrawal') {
                if ($voucher->disbursement_type !== 'linked_deposit') {
                    $voucher->linked_deposit_voucher_id = null;
                }

                if ($voucher->amount_without_tax !== null) {
                    $net = (float) $voucher->amount_without_tax;
                    $tax = (float) ($voucher->tax_amount ?? 0);
                    $total = round($net + $tax, 2);
                    $voucher->gross_amount = $total;
                    $voucher->total_amount = $total;

                    // التحقق الصارم من عدم تجاوز الرصيد المتاح لمنع السوالب نهائياً
                    if ($voucher->asset_fund_id && $total > 0) {
                        $fundAvailable = static::getFundGrossAvailableBalance((int) $voucher->asset_fund_id, $voucher->id);
                        if ($total > $fundAvailable) {
                            throw new \Exception(__('asset_funds.asset_fund_vouchers.errors.amount_exceeds_fund_gross_balance', [
                                'amount'    => number_format($total, 2),
                                'available' => number_format($fundAvailable, 2),
                            ]));
                        }

                        if ($voucher->disbursement_type === 'linked_deposit' && $voucher->linked_deposit_voucher_id) {
                            $depositRemaining = static::getRemainingBalanceForDeposit((int) $voucher->linked_deposit_voucher_id, $voucher->id);
                            if ($total > $depositRemaining) {
                                throw new \Exception(__('asset_funds.asset_fund_vouchers.errors.amount_exceeds_deposit_balance', [
                                    'remaining' => number_format($depositRemaining, 2),
                                    'amount'    => number_format($total, 2),
                                ]));
                            }
                        }
                    }
                }
            }
        });

        static::deleting(function (AssetFundVoucher $voucher) {
            if ($voucher->linkedWithdrawals()->exists()) {
                throw new \Exception(__('asset_funds.asset_fund_vouchers.errors.cannot_delete_deposit_with_withdrawals'));
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function linkedDepositVoucher(): BelongsTo
    {
        return $this->belongsTo(self::class, 'linked_deposit_voucher_id');
    }

    public function linkedWithdrawals(): HasMany
    {
        return $this->hasMany(self::class, 'linked_deposit_voucher_id');
    }

    public function getRemainingSpendableAmountAttribute(): float
    {
        $gross = (float) ($this->gross_amount ?? 0);
        if ($gross <= 0) {
            $gross = (float) $this->total_amount + (float) $this->tax_amount + (float) $this->retention_amount + (float) $this->fund_share_amount;
        }

        try {
            $used = (float) $this->linkedWithdrawals()->sum('total_amount');
        } catch (\Throwable $e) {
            $used = 0;
        }

        return max(0, round($gross - $used, 2));
    }

    public static function getRemainingBalanceForDeposit(int $depositId, ?int $ignoreWithdrawalId = null): float
    {
        $deposit = static::find($depositId);
        if (! $deposit) {
            return 0;
        }

        $gross = (float) ($deposit->gross_amount ?? 0);
        if ($gross <= 0) {
            $gross = (float) $deposit->total_amount + (float) $deposit->tax_amount + (float) $deposit->retention_amount + (float) $deposit->fund_share_amount;
        }

        try {
            $usedQuery = $deposit->linkedWithdrawals();
            if ($ignoreWithdrawalId) {
                $usedQuery->where('id', '!=', $ignoreWithdrawalId);
            }
            $used = (float) $usedQuery->sum('total_amount');
        } catch (\Throwable $e) {
            $used = 0;
        }

        return max(0, round($gross - $used, 2));
    }

    public static function getFundGrossAvailableBalance(int $fundId, ?int $ignoreWithdrawalId = null): float
    {
        $deposits = static::where('asset_fund_id', $fundId)
            ->where(function ($q) {
                $q->where('type', AssetFundVoucherType::DEPOSIT->value)
                  ->orWhere('type', 'deposit');
            })
            ->get();

        $withdrawalsQuery = static::where('asset_fund_id', $fundId)
            ->where(function ($q) {
                $q->where('type', AssetFundVoucherType::WITHDRAWAL->value)
                  ->orWhere('type', 'withdrawal');
            });

        if ($ignoreWithdrawalId) {
            $withdrawalsQuery->where('id', '!=', $ignoreWithdrawalId);
        }

        $totalWithdrawals = (float) $withdrawalsQuery->sum('total_amount');

        $grossDeposits = 0;
        foreach ($deposits as $dep) {
            $gross = (float) ($dep->gross_amount ?? 0);
            if ($gross <= 0) {
                $gross = (float) $dep->total_amount + (float) $dep->tax_amount + (float) $dep->retention_amount + (float) $dep->fund_share_amount;
            }
            $grossDeposits += $gross;
        }

        return round($grossDeposits - $totalWithdrawals, 2);
    }

    public function fund(): BelongsTo
    {
        return $this->belongsTo(AssetFund::class, 'asset_fund_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetFundVoucherItem::class)->orderBy('sort_order');
    }
}
