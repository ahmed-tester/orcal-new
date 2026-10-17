<?php

namespace App\Filament\Resources\EmployeeRewards\Schemas;

use App\Enums\RewardStatus;
use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeRewardForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.employee_rewards.sections.reward_info'))
                    ->description(__('finance.employee_rewards.sections.reward_info_desc'))
                    ->icon('heroicon-o-gift')
                    ->columns(3)
                    ->schema([
                        TextInput::make('reward_number')
                            ->label(__('finance.employee_rewards.fields.reward_number'))
                            ->placeholder(__('finance.employee_rewards.placeholders.auto_reward_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('reward_date')
                            ->label(__('finance.employee_rewards.fields.reward_date'))
                            ->default(now())
                            ->required(),

                        OrgSelect::company('company_id', __('finance.employee_rewards.fields.company'), 'employee_id'),

                        Select::make('employee_id')
                            ->label(__('finance.employee_rewards.fields.employee'))
                            ->relationship('employee', 'id', function ($query, callable $get) {
                                $companyId = $get('company_id') ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
                                if ($companyId) {
                                    $query->where('company_id', $companyId);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee) {
                                        if ($employee->company_id) {
                                            $set('company_id', $employee->company_id);
                                        }
                                        if ((float)$employee->basic_salary > 0) {
                                            // Standard 30 days * 8 hours = 240 work hours per month
                                            $calcHourly = round((float)$employee->basic_salary / 240, 2);
                                            $set('hourly_rate', $calcHourly);
                                            static::recalculateBonus($set, $get, $calcHourly);
                                        }
                                    }
                                }
                            }),

                        TextInput::make('reason')
                            ->label(__('finance.employee_rewards.fields.reason'))
                            ->placeholder(__('finance.employee_rewards.placeholders.reason'))
                            ->required(),

                        TextInput::make('location')
                            ->label(__('finance.employee_rewards.fields.location'))
                            ->placeholder(__('finance.employee_rewards.placeholders.location')),

                        Select::make('status')
                            ->label(__('finance.employee_rewards.fields.status'))
                            ->options(RewardStatus::class)
                            ->default(RewardStatus::PENDING->value)
                            ->required()
                            ->reactive(),

                        DatePicker::make('paid_date')
                            ->label(__('finance.employee_rewards.fields.paid_date'))
                            ->visible(fn (callable $get) => $get('status') === RewardStatus::PAID->value),

                        Select::make('treasury_transaction_id')
                            ->label(__('finance.employee_rewards.fields.treasury_transaction'))
                            ->relationship('treasuryTransaction', 'voucher_number', fn ($query) => $query->where('transaction_type', 'outgoing'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('status') === RewardStatus::PAID->value),
                    ]),

                Section::make(__('finance.employee_rewards.sections.hours_minutes'))
                    ->description(__('finance.employee_rewards.sections.hours_minutes_desc'))
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->schema([
                        TextInput::make('hourly_rate')
                            ->label(__('finance.employee_rewards.fields.hourly_rate'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateBonus($set, $get, (float)$state)),

                        TextInput::make('hours_count')
                            ->label(__('finance.employee_rewards.fields.hours_count'))
                            ->numeric()
                            ->step(0.25)
                            ->default(0)
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateBonus($set, $get)),

                        TextInput::make('hours_amount')
                            ->label(__('finance.employee_rewards.fields.hours_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->default(0),

                        TextInput::make('minute_rate')
                            ->label(__('finance.employee_rewards.fields.minute_rate'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->default(0),

                        TextInput::make('minutes_count')
                            ->label(__('finance.employee_rewards.fields.minutes_count'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(59)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateBonus($set, $get)),

                        TextInput::make('minutes_amount')
                            ->label(__('finance.employee_rewards.fields.minutes_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->default(0),

                        TextInput::make('net_amount')
                            ->label(__('finance.employee_rewards.fields.net_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->default(0)
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'font-bold text-lg text-emerald-600']),

                        Textarea::make('notes')
                            ->label(__('finance.employee_rewards.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function recalculateBonus(callable $set, callable $get, ?float $hourlyRate = null): void
    {
        $hourly = $hourlyRate !== null ? $hourlyRate : (float)($get('hourly_rate') ?? 0);
        $hours = (float)($get('hours_count') ?? 0);
        $minutes = (int)($get('minutes_count') ?? 0);

        $hoursAmount = round($hourly * $hours, 2);
        $minuteRate = round($hourly / 60, 4);
        $minutesAmount = round($minuteRate * $minutes, 2);
        $net = round($hoursAmount + $minutesAmount, 2);

        $set('hours_amount', $hoursAmount);
        $set('minute_rate', $minuteRate);
        $set('minutes_amount', $minutesAmount);
        $set('net_amount', $net);
    }
}
