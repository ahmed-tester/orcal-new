<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentItemCategory: string implements HasLabel, HasColor, HasIcon
{
    case SALARY_COLLECTION = 'salary_collection';
    case SALARY_PAYMENT    = 'salary_payment';
    case GENERAL_EXPENSES  = 'general_expenses';
    case VIOLATIONS        = 'violations';
    case SERVICES          = 'services';
    case INVOICES          = 'invoices';
    case RENT              = 'rent';
    case MAINTENANCE       = 'maintenance';
    case OTHER             = 'other';

    public function getLabel(): string
    {
        return __('finance.payment_collection_items.categories.' . $this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SALARY_COLLECTION => 'success',
            self::SALARY_PAYMENT    => 'warning',
            self::GENERAL_EXPENSES  => 'danger',
            self::VIOLATIONS        => 'danger',
            self::SERVICES          => 'info',
            self::INVOICES          => 'gray',
            self::RENT              => 'primary',
            self::MAINTENANCE       => 'warning',
            self::OTHER             => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SALARY_COLLECTION => 'heroicon-m-arrow-down-tray',
            self::SALARY_PAYMENT    => 'heroicon-m-arrow-up-tray',
            self::GENERAL_EXPENSES  => 'heroicon-m-receipt-percent',
            self::VIOLATIONS        => 'heroicon-m-exclamation-triangle',
            self::SERVICES          => 'heroicon-m-briefcase',
            self::INVOICES          => 'heroicon-m-document-text',
            self::RENT              => 'heroicon-m-building-office',
            self::MAINTENANCE       => 'heroicon-m-wrench-screwdriver',
            self::OTHER             => 'heroicon-m-ellipsis-horizontal',
        };
    }
}
