<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class FundFormService
{
    /**
     * Create an unconstrained, searchable Saudi & Admin Employee Select component for Funds.
     * Strictly includes Saudi employees and administrators across all companies, completely excluding foreign workers.
     */
    public static function makeSaudiEmployeeSelect(
        string $name = 'employee_id',
        ?string $label = null,
        bool $required = true,
        string $relationshipName = 'employee',
    ): Select {
        return Select::make($name)
            ->label($label ?? __('employees.single'))
            ->relationship(
                name: $relationshipName,
                titleAttribute: 'id',
                modifyQueryUsing: fn (Builder $query) => static::applySaudiEmployeeScope($query),
            )
            ->getSearchResultsUsing(fn (string $search) => static::employeeSearchResults($search))
            ->getOptionLabelFromRecordUsing(
                fn ($record) => ($record->user?->name ?? "موظف #{$record->id}").($record->employee_number ? " ({$record->employee_number})" : '')
            )
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * Scope query to active Saudi employees and administrators across all companies (free from company restrictions).
     */
    public static function applySaudiEmployeeScope(Builder $query): Builder
    {
        return $query->withoutGlobalScopes()
            ->with(['user', 'details'])
            ->where('status', 'active')
            ->where(function (Builder $q) {
                $q->saudi()
                    ->orWhereHas('user', fn ($sub) => $sub->whereIn('user_type', [
                        UserType::ADMIN,
                        UserType::SUPER_ADMIN,
                    
                    ]));
            });
    }

    /**
     * Search Saudi employees across all companies by name or employee number.
     */
    public static function employeeSearchResults(string $search): array
    {
        $query = Employee::query();
        static::applySaudiEmployeeScope($query);

        return $query
            ->where(function (Builder $q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn ($record) => [
                $record->id => ($record->user?->name ?? "موظف #{$record->id}").($record->employee_number ? " ({$record->employee_number})" : ''),
            ])
            ->toArray();
    }
}
