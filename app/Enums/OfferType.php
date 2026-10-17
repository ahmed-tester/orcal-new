<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OfferType: string implements HasLabel, HasColor, HasIcon
{
    case QUOTATION          = 'quotation';
    case SUPPLY_OFFER       = 'supply_offer';
    case UNIFIED_CONTRACT   = 'unified_contract';
    case AGREEMENT_CONTRACT = 'agreement_contract';
    case SALE_AGREEMENT     = 'sale_agreement';

    public function getLabel(): string
    {
        return match ($this) {
            self::QUOTATION          => __('offers.types.quotation'),
            self::SUPPLY_OFFER       => __('offers.types.supply_offer'),
            self::UNIFIED_CONTRACT   => __('offers.types.unified_contract'),
            self::AGREEMENT_CONTRACT => __('offers.types.agreement_contract'),
            self::SALE_AGREEMENT     => __('offers.types.sale_agreement'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::QUOTATION          => 'info',
            self::SUPPLY_OFFER       => 'success',
            self::UNIFIED_CONTRACT   => 'warning',
            self::AGREEMENT_CONTRACT => 'primary',
            self::SALE_AGREEMENT     => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::QUOTATION          => 'heroicon-m-document-currency-dollar',
            self::SUPPLY_OFFER       => 'heroicon-m-truck',
            self::UNIFIED_CONTRACT   => 'heroicon-m-briefcase',
            self::AGREEMENT_CONTRACT => 'heroicon-m-shield-check',
            self::SALE_AGREEMENT     => 'heroicon-m-shopping-cart',
        };
    }
}
