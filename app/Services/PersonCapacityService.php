<?php

namespace App\Services;

use App\Models\Owner;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class PersonCapacityService
{
    
    public static function makeTableColumn(string $for = 'employee'): TextColumn
    {
        $isOwner = $for === 'owner';

        return TextColumn::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->state(function ($record) use ($isOwner) {
                if ($isOwner || $record instanceof Owner) {
                    return (bool) $record->user?->employee
                        ? __('owners.fields.capacity_employee_and_owner')
                        : __('owners.fields.capacity_owner');
                }

                return ($record->user?->owners?->isNotEmpty() ?? false)
                    ? __('employees.fields.capacity_employee_and_owner')
                    : __('employees.fields.capacity_employee');
            })
            ->badge()
            ->color(function ($record) use ($isOwner) {
                if ($isOwner || $record instanceof Owner) {
                    return (bool) $record->user?->employee ? 'warning' : 'success';
                }

                return ($record->user?->owners?->isNotEmpty() ?? false) ? 'warning' : 'info';
            })
            ->icon(function ($record) use ($isOwner) {
                if ($isOwner || $record instanceof Owner) {
                    return (bool) $record->user?->employee ? 'heroicon-m-briefcase' : 'heroicon-m-building-office-2';
                }

                return ($record->user?->owners?->isNotEmpty() ?? false) ? 'heroicon-m-briefcase' : 'heroicon-m-user';
            });
    }

    /**
     * Create the capacity table filter.
     */
    public static function makeTableFilter(string $for = 'employee'): SelectFilter
    {
        $isOwner = $for === 'owner';
        $relation = $isOwner ? 'user.employee' : 'user.owners';
        $onlyKey = $isOwner ? 'owner' : 'employee';

        return SelectFilter::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->options([
                $onlyKey => $isOwner ? __('owners.fields.capacity_owner') : __('employees.fields.capacity_employee'),
                'employee_and_owner' => $isOwner ? __('owners.fields.capacity_employee_and_owner') : __('employees.fields.capacity_employee_and_owner'),
            ])
            ->query(function ($query, array $data) use ($relation, $onlyKey) {
                if (($data['value'] ?? null) === 'employee_and_owner') {
                    $query->whereHas($relation);
                } elseif (($data['value'] ?? null) === $onlyKey) {
                    $query->whereDoesntHave($relation);
                }
            });
    }
}

