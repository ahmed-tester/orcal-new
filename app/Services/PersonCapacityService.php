<?php

namespace App\Services;

use App\Models\Owner;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class PersonCapacityService
{
    /**
     * Get the capacity options for either an employee or an owner.
     *
     * @return array<string, string>
     */
    public static function getOptions(string $for = 'employee'): array
    {
        if ($for === 'owner') {
            return [
                'owner' => __('owners.fields.capacity_owner'),
                'employee_and_owner' => __('owners.fields.capacity_employee_and_owner'),
            ];
        }

        return [
            'employee' => __('employees.fields.capacity_employee'),
            'employee_and_owner' => __('employees.fields.capacity_employee_and_owner'),
        ];
    }

    /**
     * Resolve the capacity key from stored metadata, falling back to relations.
     */
    public static function resolveKey(mixed $record, string $for = 'employee'): string
    {
        $isOwner = $for === 'owner' || $record instanceof Owner;
        $stored = is_array($record?->metadata) ? ($record->metadata['person_capacity'] ?? null) : null;

        if (is_string($stored) && array_key_exists($stored, static::getOptions($isOwner ? 'owner' : 'employee'))) {
            return $stored;
        }

        if ($isOwner) {
            return (bool) $record?->user?->employee ? 'employee_and_owner' : 'owner';
        }

        return ($record?->user?->owners?->isNotEmpty() ?? false) ? 'employee_and_owner' : 'employee';
    }

    /**
     * Merge the selected capacity into a record's metadata array.
     *
     * @param  array<string, mixed>|string|null  $metadata
     * @return array<string, mixed>
     */
    public static function mergeIntoMetadata(array|string|null $metadata, ?string $capacity, string $for = 'employee'): array
    {
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        $metadata = is_array($metadata) ? $metadata : [];
        $default = $for === 'owner' ? 'owner' : 'employee';
        $options = static::getOptions($for);

        $metadata['person_capacity'] = ($capacity && array_key_exists($capacity, $options)) ? $capacity : $default;

        return $metadata;
    }

    /**
     * Create the capacity Select form field for Create/Edit pages.
     */
    public static function makeFormField(string $for = 'employee'): Select
    {
        $isOwner = $for === 'owner';

        return Select::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->options(static::getOptions($for))
            ->default($isOwner ? 'owner' : 'employee')
            ->selectablePlaceholder(false)
            ->native(false);
    }

    public static function makeTableColumn(string $for = 'employee'): TextColumn
    {
        $isOwner = $for === 'owner';

        return TextColumn::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->state(function ($record) use ($for, $isOwner) {
                $key = static::resolveKey($record, $for);
                $options = static::getOptions($isOwner || $record instanceof Owner ? 'owner' : 'employee');

                return $options[$key] ?? ($isOwner ? __('owners.fields.capacity_owner') : __('employees.fields.capacity_employee'));
            })
            ->badge()
            ->color(function ($record) use ($for, $isOwner) {
                $key = static::resolveKey($record, $for);

                if ($key === 'employee_and_owner') {
                    return 'warning';
                }

                return ($isOwner || $record instanceof Owner) ? 'success' : 'info';
            })
            ->icon(function ($record) use ($for, $isOwner) {
                $key = static::resolveKey($record, $for);

                if ($key === 'employee_and_owner') {
                    return 'heroicon-m-briefcase';
                }

                return ($isOwner || $record instanceof Owner) ? 'heroicon-m-building-office-2' : 'heroicon-m-user';
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
            ->options(static::getOptions($for))
            ->query(function ($query, array $data) use ($relation, $onlyKey) {
                $value = $data['value'] ?? null;
                if (! $value) {
                    return;
                }

                $query->where(function ($q) use ($value, $relation, $onlyKey) {
                    $q->where('metadata->person_capacity', $value)
                        ->orWhere(function ($fallback) use ($value, $relation, $onlyKey) {
                            $fallback->whereNull('metadata->person_capacity');
                            if ($value === 'employee_and_owner') {
                                $fallback->whereHas($relation);
                            } elseif ($value === $onlyKey) {
                                $fallback->whereDoesntHave($relation);
                            }
                        });
                });
            });
    }
}
