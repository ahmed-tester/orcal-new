<?php

namespace App\Enums\Mail;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MailPriority: string implements HasColor, HasLabel
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function getLabel(): string
    {
        return __("mail_inbox.priorities.{$this->value}");
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::HIGH => 'danger',
            self::MEDIUM => 'warning',
            self::LOW => 'gray',
        };
    }

    /**
     * Map a translated Arabic or English priority label to its enum case.
     */
    public static function fromLabel(?string $label): self
    {
        $normalized = trim((string) $label);
        if ($normalized === '') {
            return self::MEDIUM;
        }

        if ($case = self::tryFrom($normalized)) {
            return $case;
        }

        $arMap = array_flip((array) __('mail_inbox.priorities', [], 'ar'));
        if (isset($arMap[$normalized])) {
            return self::from($arMap[$normalized]);
        }

        return self::MEDIUM;
    }
}
