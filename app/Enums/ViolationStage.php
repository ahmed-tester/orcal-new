<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ViolationStage: string implements HasLabel, HasColor, HasIcon
{
    case CORRECTION       = 'correction';
    case OBJECTION        = 'objection';
    case GRACE            = 'grace';
    case LEGAL            = 'legal';
    case ADMIN_EXECUTION  = 'admin_execution';
    case SUSPENDED        = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::CORRECTION      => __('violations.stages.correction'),
            self::OBJECTION       => __('violations.stages.objection'),
            self::GRACE           => __('violations.stages.grace'),
            self::LEGAL           => __('violations.stages.legal'),
            self::ADMIN_EXECUTION => __('violations.stages.admin_execution'),
            self::SUSPENDED       => __('violations.stages.suspended'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CORRECTION      => 'info',
            self::OBJECTION       => 'warning',
            self::GRACE           => 'primary',
            self::LEGAL           => 'danger',
            self::ADMIN_EXECUTION => 'danger',
            self::SUSPENDED       => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::CORRECTION      => 'heroicon-o-wrench-screwdriver',
            self::OBJECTION       => 'heroicon-o-chat-bubble-left-right',
            self::GRACE           => 'heroicon-o-clock',
            self::LEGAL           => 'heroicon-o-scale',
            self::ADMIN_EXECUTION => 'heroicon-o-shield-exclamation',
            self::SUSPENDED       => 'heroicon-o-no-symbol',
        };
    }

    public function getDays(): ?int
    {
        return match ($this) {
            self::CORRECTION => 14,
            self::OBJECTION  => 60,
            self::GRACE      => 90,
            default          => null,
        };
    }
}
