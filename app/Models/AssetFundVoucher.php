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
        'notes',
        'created_by',
    ];

    protected $attributes = [
        'total_amount' => 0,
        'source_type'  => 'free',
    ];

    protected function casts(): array
    {
        return [
            'type'         => AssetFundVoucherType::class,
            'source_type'  => AssetFundSourceType::class,
            'voucher_date' => 'date',
            'total_amount' => 'decimal:2',
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

