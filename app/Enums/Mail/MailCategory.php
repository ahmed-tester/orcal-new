<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailCategory: string implements HasColor, HasLabel
{
    case INQUIRY = 'inquiry';
    case REQUEST = 'request';
    case COMPLAINT = 'complaint';
    case OFFICIAL = 'official';
    case MARKETING = 'marketing';
    case SPAM = 'spam';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return __("mail_inbox.categories.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INQUIRY => 'info',
            self::REQUEST => 'primary',
            self::COMPLAINT => 'danger',
            self::OFFICIAL => 'success',
            self::MARKETING => 'warning',
            self::SPAM => 'gray',
            self::OTHER => 'gray',
        };
    }

    /**
     * Map a translated Arabic or English category label to its enum case.
     */
    public static function fromLabel(?string $label): self
    {
        $normalized = trim((string) $label);
        if ($normalized === '') {
            return self::OTHER;
        }

        if ($case = self::tryFrom($normalized)) {
            return $case;
        }

        $arMap = array_flip((array) __('mail_inbox.categories', [], 'ar'));
        if (isset($arMap[$normalized])) {
            return self::from($arMap[$normalized]);
        }

        return self::OTHER;
    }
}
