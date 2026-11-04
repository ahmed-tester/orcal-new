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
                $voucher->voucher_number = CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'voucher_number',
                    prefix: 'AFV',
                    companyId: null,
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
                if ($voucher->amount_without_tax !== null) {
                    $net = (float) $voucher->amount_without_tax;
                    $tax = (float) ($voucher->tax_amount ?? 0);
                    $total = round($net + $tax, 2);
                    $voucher->gross_amount = $total;
                    $voucher->total_amount = $total;
                }
            }
        });
    }

    // ─── Relations ───────────────────────────────────────────────────────────

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
