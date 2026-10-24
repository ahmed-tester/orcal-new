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

    /**
     * Smartly detect whether a user/employee is Saudi or Foreign based on
     * National ID, Iqama number, nationality, or name characteristics.
     */
    public static function detect(
        ?string $nationalId = null,
        ?string $iqamaNumber = null,
        ?string $nationality = null,
        ?string $name = null
    ): self {
        // 1. Check explicit nationality
        if (! empty($nationality)) {
            $nat = mb_strtolower(trim($nationality));
            if (str_contains($nat, 'سعود') || str_contains($nat, 'saudi') || $nat === 'ksa') {
                return self::SAUDI_EMPLOYEE;
            }

            return self::FOREIGN_EMPLOYEE;
        }

        // 2. Check National ID (In Saudi Arabia: Saudi National IDs are 10 digits starting with 1)
        $cleanId = preg_replace('/\D/', '', (string) $nationalId);
        if (strlen($cleanId) === 10) {
            if (str_starts_with($cleanId, '1')) {
                return self::SAUDI_EMPLOYEE;
            }
            if (str_starts_with($cleanId, '2')) {
                return self::FOREIGN_EMPLOYEE;
            }
        }

        // 3. Check Iqama number (10 digits starting with 2)
        $cleanIqama = preg_replace('/\D/', '', (string) $iqamaNumber);
        if (strlen($cleanIqama) === 10 && str_starts_with($cleanIqama, '2')) {
            return self::FOREIGN_EMPLOYEE;
        }

        // 4. Latin / English names indicate foreign worker (e.g. MAMUN KABIR, ANOWAR HOSSAIN)
        if (! empty($name) && preg_match('/[a-zA-Z]/', $name)) {
            return self::FOREIGN_EMPLOYEE;
        }

        // 5. Arabic names with Saudi patronymics (بن، بنت، آل)
        if (! empty($name) && preg_match('/\b(بن|بنت|آل)\b/u', $name)) {
            return self::SAUDI_EMPLOYEE;
        }

        // Default baseline for general employees
        return self::FOREIGN_EMPLOYEE;
    }
}
