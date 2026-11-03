<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum AssetFundVoucherType: string implements HasLabel, HasColor, HasIcon
{
    case DEPOSIT    = 'deposit';
    case WITHDRAWAL = 'withdrawal';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEPOSIT    => __('asset_funds.enums.voucher_type.deposit'),
            self::WITHDRAWAL => __('asset_funds.enums.voucher_type.withdrawal'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DEPOSIT    => 'success',
            self::WITHDRAWAL => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DEPOSIT    => 'heroicon-m-arrow-down-circle',
            self::WITHDRAWAL => 'heroicon-m-arrow-up-circle',
        };
    }
}

