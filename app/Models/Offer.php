<?php

namespace App\Models;


use App\Traits\HasHijriDates;
use App\Enums\OfferStatus;
use App\Enums\OfferType;
use App\Enums\PricingType;
use App\Enums\WorkScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory, HasHijriDates;

    public function getHijriDateFieldNames(): array
    {
        return [
            'offer_date',
            'valid_until',
        ];
    }


    protected $fillable = [
        'offer_type',
        'contract_category_id',
        'offer_number',
        'company_id',
        'customer_id',
        'subject',
        'description',
        'work_scope',
        'supplied_materials',
        'scope_description',
        'pricing_type',
        'is_tax_inclusive',
        'tax_rate',
        'execution_duration',
        'terms_and_conditions',
        'offer_date',
        'valid_until',
        'status',
        'manager_id',
        'supervisor_id',
        'show_stamp',
        'created_by',
        'notes',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'offer_type'       => OfferType::class,
            'work_scope'       => WorkScope::class,
            'pricing_type'     => PricingType::class,
            'is_tax_inclusive' => 'boolean',
            'tax_rate'         => 'decimal:2',
            'show_stamp'       => 'boolean',
            'offer_date'       => 'date',
            'valid_until'      => 'date',
            'status'           => OfferStatus::class,
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Offer $offer) {
            if (blank($offer->created_by) && auth()->check()) {
                $offer->created_by = auth()->id();
            }

            if (blank($offer->offer_number)) {
                $prefix = match ($offer->offer_type) {
                    OfferType::SUPPLY_OFFER       => 'SUP',
                    OfferType::UNIFIED_CONTRACT   => 'CNT',
                    OfferType::AGREEMENT_CONTRACT => 'AGR',
                    OfferType::SALE_AGREEMENT     => 'SAL',
                    default                       => 'OFF',
                };
                $offer->offer_number = \App\Services\CodeGeneratorService::generate(
                    modelClass: static::class,
                    column: 'offer_number',
                    prefix: $prefix,
                    companyId: $offer->company_id,
                    padLength: 4,
                    datePart: 'Ymd',
                );
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfferItem::class)->orderBy('sort_order');
    }

    public function contractCategory(): BelongsTo
    {
        return $this->belongsTo(ContractCategory::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(ContractVoucher::class);
    }
}
