<?php

namespace App\Services;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

class EmployeeAgeService
{
    /**
     * Calculate age from a birth date string or Carbon instance.
     */
    public static function calculateAge(mixed $birthDate): ?int
    {
        if (empty($birthDate)) {
            return null;
        }

        try {
            return Carbon::parse($birthDate)->age;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Create the reactive birth date form field.
     */
    public static function makeBirthDateField(string $name = 'birth_date'): DatePicker
    {
        return DatePicker::make($name)
            ->label(__('employees.fields.birth_date'))
            ->native(false)
            ->live()
            ->afterStateUpdated(function ($state, callable $set) {
                $set('age', static::calculateAge($state));
            });
    }

    /**
     * Create the auto-calculated read-only age form field.
     */
    public static function makeAgeField(string $name = 'age', string $birthDateField = 'birth_date'): TextInput
    {
        return TextInput::make($name)
            ->label(__('employees.fields.age'))
            ->suffix(__('employees.fields.age_unit'))
            ->readOnly()
            ->dehydrated(false)
            ->placeholder(__('employees.fields.age_auto_calculated'))
            ->formatStateUsing(function ($state, callable $get) use ($birthDateField) {
                return static::calculateAge($get($birthDateField)) ?? $state;
            });
    }

    /**
     * Create both birth_date and age form fields as an array.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function makeFormFields(): array
    {
        return [
            static::makeBirthDateField(),
            static::makeAgeField(),
        ];
    }

    /**
     * Create the table column for age with birth date formatted in description.
     */
    public static function makeTableColumn(string $statePath = 'details.birth_date'): TextColumn
    {
        return TextColumn::make($statePath)
            ->label(__('employees.fields.age'))
            ->state(function ($record) {
                $birthDate = $record->details?->birth_date;
                if (!$birthDate) {
                    return '—';
                }
                $age = static::calculateAge($birthDate);
                return $age !== null ? ($age . ' ' . __('employees.fields.age_unit')) : '—';
            })
            ->description(fn ($record) => $record->details?->birth_date?->format('Y-m-d'))
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
