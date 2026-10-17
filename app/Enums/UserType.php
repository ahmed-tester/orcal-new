<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserType: string implements HasLabel
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case SAUDI_EMPLOYEE = 'saudi_employee';
    case FOREIGN_EMPLOYEE = 'foreign_employee';
    case OWNER = 'owner';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => __('user_types.super_admin'),
            self::ADMIN => __('user_types.admin'),
            self::SUPERVISOR => __('user_types.supervisor'),
            self::SAUDI_EMPLOYEE => __('user_types.saudi_employee'),
            self::FOREIGN_EMPLOYEE => __('user_types.foreign_employee'),
            self::OWNER => __('user_types.owner'),
        };
    }

    public function isSaudiEmployee(): bool
    {
        return $this === self::SAUDI_EMPLOYEE;
    }

    public function isForeignEmployee(): bool
    {
        return $this === self::FOREIGN_EMPLOYEE;
    }

    public function isOwner(): bool
    {
        return $this === self::OWNER;
    }
}
