<?php

namespace App\Filament\Resources\RoscaFunds\Schemas;

use App\Enums\RoscaAllocationMethod;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoscaFundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.rosca_funds.sections.basic_info'))
                    ->icon('heroicon-o-users')
                    ->columns(3)
                    ->schema([
                        TextInput::make('fund_number')
                            ->label(__('funds.rosca_funds.fields.fund_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        TextInput::make('fund_name')
                            ->label(__('funds.rosca_funds.fields.fund_name'))
                            ->required(),

                        DatePicker::make('start_date')
                            ->label(__('funds.rosca_funds.fields.start_date'))
                            ->default(now())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculatePool($set, $get)),

                        TextInput::make('members_count')
                            ->label(__('funds.rosca_funds.fields.members_count'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 1)))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculatePool($set, $get)),

                        TextInput::make('monthly_installment')
                            ->label(__('funds.rosca_funds.fields.monthly_installment'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculatePool($set, $get)),

                        TextInput::make('total_pool_amount')
                            ->label(__('funds.rosca_funds.fields.total_pool_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('funds.rosca_funds.helpers.total_pool_amount')),

                        DatePicker::make('end_date')
                            ->label(__('funds.rosca_funds.fields.end_date')),

                        Select::make('allocation_method')
                            ->label(__('funds.rosca_funds.fields.allocation_method'))
                            ->options(RoscaAllocationMethod::class)
                            ->default(RoscaAllocationMethod::CONSENSUS->value)
                            ->required(),

                        Select::make('status')
                            ->label(__('funds.rosca_funds.fields.status'))
                            ->options([
                                'draft' => __('funds.enums.general_status.draft'),
                                'active' => __('funds.enums.general_status.active'),
                                'completed' => __('funds.enums.general_status.completed'),
                                'closed' => __('funds.enums.general_status.closed'),
                            ])
                            ->default('active')
                            ->required(),
                    ]),

                Section::make(__('funds.rosca_funds.sections.management'))
                    ->icon('heroicon-o-shield-check')
                    ->columns(1)
                    ->schema([
                        Select::make('assigned_manager_id')
                            ->label(__('funds.rosca_funds.fields.assigned_manager'))
                            ->relationship(
                                name: 'assignedManager',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query
                                    ->with('employee')
                                    ->whereHas('employee', fn ($q) => $q->withoutGlobalScopes()->where('status', 'active')->saudi())
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => $record->name.($record->employee?->employee_number ? " ({$record->employee->employee_number})" : '')
                            )
                            ->searchable()
                            ->preload(),

                        Textarea::make('rules_and_conditions')
                            ->label(__('funds.rosca_funds.fields.rules_and_conditions'))
                            ->rows(4),

                        Textarea::make('notes')
                            ->label(__('funds.rosca_funds.fields.notes'))
                            ->rows(2),
                    ]),
            ]);
    }

    public static function recalculatePool(callable $set, callable $get): void
    {
        $count = max(1, (int) ($get('members_count') ?? 0));
        $monthly = (float) ($get('monthly_installment') ?? 0);
        $total = round($count * $monthly, 2);

        $set('total_pool_amount', $total);

        $startDate = $get('start_date');
        if ($startDate && $count > 0) {
            $set('end_date', Carbon::parse($startDate)->addMonths($count)->toDateString());
        }
    }
}
