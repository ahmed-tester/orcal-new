<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetFundVoucherItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_fund_voucher_id',
        'description',
        'percentage',
        'sub_total',
        'extra_1',
        'extra_2',
        'row_total',
        'sort_order',
    ];

    protected $attributes = [
        'sub_total'  => 0,
        'row_total'  => 0,
        'sort_order' => 0,
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'sub_total'  => 'decimal:2',
            'extra_1'    => 'decimal:2',
            'extra_2'    => 'decimal:2',
            'row_total'  => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    // ─── Relations ───────────────────────────────────────────────────────────

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(AssetFundVoucher::class, 'asset_fund_voucher_id');
    }
}

