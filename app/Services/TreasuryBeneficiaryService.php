<?php

namespace App\Services;

use App\Models\ForeignEmployee;
use App\Models\SaudiEmployee;
use Filament\Forms\Components\Select;

class TreasuryBeneficiaryService
{
    /**
     * Get employees list options formatted as [id => "Name (Code)"]
     */
    public static function getEmployeeOptions(string $beneficiaryType, ?int $companyId = null): array
    {
        $modelClass = match ($beneficiaryType) {
            'saudi_employee' => SaudiEmployee::class,
            'foreign_employee' => ForeignEmployee::class,
            default => null,
        };

        if (!$modelClass) {
            return [];
        }

        $query = $modelClass::with('user');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->mapWithKeys(function ($emp) {
            $name = $emp->user?->name ?? "موظف #{$emp->id}";
            $code = $emp->employee_number ? " ({$emp->employee_number})" : '';
            return [$emp->id => "{$name}{$code}"];
        })->toArray();
    }

    /**
     * Resolve the employee's user name by beneficiary type and employee ID.
     */
    public static function resolveEmployeeName(?string $beneficiaryType, mixed $employeeId): ?string
    {
        if (empty($employeeId) || empty($beneficiaryType)) {
            return null;
        }

        $modelClass = match ($beneficiaryType) {
            'saudi_employee' => SaudiEmployee::class,
            'foreign_employee' => ForeignEmployee::class,
            default => null,
        };

        if (!$modelClass) {
            return null;
        }

        return $modelClass::with('user')->find($employeeId)?->user?->name;
    }

    /**
     * Detect beneficiary type and employee ID from an existing recipient_or_payer name.
     */
    public static function detectBeneficiary(?string $name): array
    {
        if (!empty($name)) {
            if ($saudiEmp = SaudiEmployee::whereHas('user', fn ($q) => $q->where('name', $name))->first()) {
                return [
                    'beneficiary_type' => 'saudi_employee',
                    'employee_id' => $saudiEmp->id,
                ];
            }

            if ($foreignEmp = ForeignEmployee::whereHas('user', fn ($q) => $q->where('name', $name))->first()) {
                return [
                    'beneficiary_type' => 'foreign_employee',
                    'employee_id' => $foreignEmp->id,
                ];
            }
        }

        return [
            'beneficiary_type' => 'other',
            'employee_id' => null,
        ];
    }

    /**
     * Build the reusable dynamic employee Select component.
     */
    public static function makeEmployeeSelect(string $name = 'employee_id'): Select
    {
        return Select::make($name)
            ->label(fn (callable $get) => match ($get('beneficiary_type')) {
                'saudi_employee' => __('finance.treasury_transactions.fields.saudi_employee'),
                'foreign_employee' => __('finance.treasury_transactions.fields.foreign_employee'),
                default => __('employees.saudi_single'),
            })
            ->options(fn (callable $get) => static::getEmployeeOptions((string) $get('beneficiary_type'), $get('company_id')))
            ->searchable()
            ->preload()
            ->live()
            ->visible(fn (callable $get) => in_array($get('beneficiary_type'), ['saudi_employee', 'foreign_employee']))
            ->required(fn (callable $get) => in_array($get('beneficiary_type'), ['saudi_employee', 'foreign_employee']))
            ->dehydrated(false)
            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                $name = static::resolveEmployeeName((string) $get('beneficiary_type'), $state);
                $set('recipient_or_payer', $name);
            });
    }
}
