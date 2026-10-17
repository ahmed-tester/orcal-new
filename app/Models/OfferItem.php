<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'sort_order',
        'item_name',
        'specifications',
        'quantity',
        'unit',
        'unit_price',
        'price',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'quantity' => 1,
        'unit_price' => 0,
        'price' => 0,
    ];

    protected static function booted(): void
    {
        static::saving(function (OfferItem $item) {
            $item->sort_order = (int)($item->sort_order ?? 0);
            $item->quantity = (float)($item->quantity ?: 1);
            $item->unit_price = (float)($item->unit_price ?? 0);
            $calculated = (float)($item->quantity * $item->unit_price);
            $item->price = (!empty($item->price) && (float)$item->price > 0) ? (float)$item->price : $calculated;
        });
    }

    protected function casts(): array
    {
        return [
            'quantity'   => 'decimal:2',
            'unit_price' => 'decimal:2',
            'price'      => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
