<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TreasuryTransactionType: string implements HasLabel, HasColor, HasIcon
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';

    public function getLabel(): string
    {
        return match ($this) {
            self::INCOMING => __('finance.treasury_transactions.types.incoming'),
            self::OUTGOING => __('finance.treasury_transactions.types.outgoing'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INCOMING => 'success',
            self::OUTGOING => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::INCOMING => 'heroicon-m-arrow-down-tray',
            self::OUTGOING => 'heroicon-m-arrow-up-tray',
        };
    }
}
