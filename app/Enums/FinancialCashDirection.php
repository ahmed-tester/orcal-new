<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialCashDirection: string implements HasColor, HasLabel
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';

    public function getLabel(): string
    {
        return match ($this) {
            self::INCOMING => __('financial.cash_directions.incoming'),
            self::OUTGOING => __('financial.cash_directions.outgoing'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INCOMING => 'success',
            self::OUTGOING => 'danger',
        };
    }
}
