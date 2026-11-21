<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PerformanceEscalationStatus: string implements HasColor, HasLabel
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => __('performance_escalations.statuses.open'),
            self::CLOSED => __('performance_escalations.statuses.closed'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::CLOSED => 'gray',
        };
    }
}
