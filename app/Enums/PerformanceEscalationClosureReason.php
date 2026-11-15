<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PerformanceEscalationClosureReason: string implements HasColor, HasLabel
{
    case RECOVERED = 'recovered';
    case MANUAL = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECOVERED => __('performance_escalations.closure_reasons.recovered'),
            self::MANUAL => __('performance_escalations.closure_reasons.manual'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RECOVERED => 'success',
            self::MANUAL => 'warning',
        };
    }
}
