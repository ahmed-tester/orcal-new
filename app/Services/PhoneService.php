<?php

namespace App\Services;

use Closure;
use Filament\Forms\Components\TextInput;

class PhoneService
{
    /**
     * Convert Eastern Arabic (٠-٩) and Persian (۰-۹) numerals to standard Western Arabic (0-9).
     */
    public static function convertArabicDigits(string $string): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $standard = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $string = str_replace($arabic, $standard, $string);
        return str_replace($persian, $standard, $string);
    }

    /**
     * Clean phone number from spaces, dashes, brackets, and extra characters.
     */
    public static function clean(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $phone = self::convertArabicDigits((string) $phone);

        // Keep digits and leading plus sign only
        $cleaned = preg_replace('/[^\d+]/', '', trim($phone));

        return $cleaned ?: null;
    }

    /**
     * Normalize Saudi and international phone numbers into standard local format (05XXXXXXXX) or standard international format.
     */
    public static function normalize(?string $phone): ?string
    {
        $cleaned = self::clean($phone);

        if (! $cleaned) {
            return null;
        }

        // Handle Saudi number variants
        // 1. +9665XXXXXXXX or 009665XXXXXXXX -> 05XXXXXXXX
        if (preg_match('/^(?:\+966|00966|966)(5\d{8})$/', $cleaned, $matches)) {
            return '0' . $matches[1];
        }

        // 2. 5XXXXXXXX (9 digits starting with 5) -> 05XXXXXXXX
        if (preg_match('/^5\d{8}$/', $cleaned)) {
            return '0' . $cleaned;
        }

        // 3. 05XXXXXXXX (10 digits starting with 05)
        if (preg_match('/^05\d{8}$/', $cleaned)) {
            return $cleaned;
        }

        return $cleaned;
    }

    /**
     * Check if the phone number is a valid Saudi mobile number (05XXXXXXXX).
     */
    public static function isValidSaudi(?string $phone): bool
    {
        $normalized = self::normalize($phone);

        if (! $normalized) {
            return false;
        }

        return (bool) preg_match('/^05\d{8}$/', $normalized);
    }

    /**
     * Validate general or Saudi phone number.
     */
    public static function isValid(?string $phone, bool $saudiOnly = false): bool
    {
        if (blank($phone)) {
            return false;
        }

        if ($saudiOnly) {
            return self::isValidSaudi($phone);
        }

        $normalized = self::normalize($phone);

        if (! $normalized) {
            return false;
        }

        // Valid if it is a valid Saudi mobile or a valid phone (7-15 digits, optional +)
        if (self::isValidSaudi($normalized)) {
            return true;
        }

        return (bool) preg_match('/^\+?[0-9]{7,15}$/', $normalized);
    }

    /**
     * Format phone number for pretty display (e.g., 05X XXX XXXX).
     */
    public static function format(?string $phone): ?string
    {
        $normalized = self::normalize($phone);

        if (! $normalized) {
            return null;
        }

        if (preg_match('/^(05\d)(\d{3})(\d{4})$/', $normalized, $matches)) {
            return "{$matches[1]} {$matches[2]} {$matches[3]}";
        }

        return $normalized;
    }

    /**
     * Resolve user-friendly translated attribute name from dot-notated Livewire attributes (e.g., data.phone).
     */
    public static function resolveAttributeLabel(string $attribute, ?string $customLabel = null): string
    {
        if ($customLabel) {
            return $customLabel;
        }

        $segments = explode('.', $attribute);
        $cleanAttribute = end($segments);

        if (is_numeric($cleanAttribute) && count($segments) > 1) {
            $cleanAttribute = $segments[count($segments) - 2];
        }

        $key = 'validation.attributes.' . $cleanAttribute;
        if (__($key) !== $key) {
            return __($key);
        }

        $fullKey = 'validation.attributes.' . $attribute;
        if (__($fullKey) !== $fullKey) {
            return __($fullKey);
        }

        $employeeKey = 'employees.fields.' . $cleanAttribute;
        if (__($employeeKey) !== $employeeKey) {
            return __($employeeKey);
        }

        $orgKey = 'organization.companies.fields.' . $cleanAttribute;
        if (__($orgKey) !== $orgKey) {
            return __($orgKey);
        }

        $ownerKey = 'owners.fields.' . $cleanAttribute;
        if (__($ownerKey) !== $ownerKey) {
            return __($ownerKey);
        }

        return $cleanAttribute;
    }

    /**
     * Filament Form Field Builder for Phone Numbers.
     */
    public static function makeFormField(
        string $name = 'phone',
        ?string $label = null,
        bool $required = false,
        bool $saudiOnly = false
    ): TextInput {
        $resolvedLabel = $label ?? (__('validation.attributes.' . $name) !== 'validation.attributes.' . $name ? __('validation.attributes.' . $name) : null);

        return TextInput::make($name)
            ->label($resolvedLabel ?? $name)
            ->tel()
            ->prefixIcon('heroicon-o-phone')
            ->placeholder('05XXXXXXXX')
            ->maxLength(20)
            ->when($required, fn (TextInput $field) => $field->required())
            ->rules([
                fn () => function (string $attribute, $value, Closure $fail) use ($saudiOnly, $resolvedLabel) {
                    if (blank($value)) {
                        return;
                    }

                    if (! PhoneService::isValid($value, $saudiOnly)) {
                        $attributeLabel = $resolvedLabel ?? PhoneService::resolveAttributeLabel($attribute);

                        $fail(__('validation.phone', [
                            'attribute' => $attributeLabel,
                        ]));
                    }
                }
            ])
            ->dehydrateStateUsing(fn ($state) => self::normalize($state));
    }
}
