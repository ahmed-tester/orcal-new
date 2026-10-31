<?php

namespace App\Filament\Resources\PayrollRecords\Schemas;

use App\Enums\PaymentStatus;
use App\Filament\Components\OrgSelect;
use App\Services\PayrollCalculationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayrollRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.payroll_records.sections.payroll_info'))
                    ->description(__('finance.payroll_records.sections.payroll_info_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->schema([
                        TextInput::make('record_number')
                            ->label(__('finance.payroll_records.fields.record_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        OrgSelect::company('company_id', __('finance.payroll_records.fields.company'), 'employee_id'),

                        Select::make('operation_type')
                            ->label(__('finance.payroll_records.fields.operation_type'))
                            ->options([
                                'payroll' => __('finance.payroll_records.fields.operation_types.payroll'),
                                'collection' => __('finance.payroll_records.fields.operation_types.collection'),
                            ])
                            ->default('payroll')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state === 'collection') {
                                    $set('basic_salary', 0);
                                    $set('additional_amount', 0);
                                    $set('deduction_amount', 0);
                                    $set('overtime_amount', 0);
                                    $set('rewards_amount', 0);
                                    $set('advance_deduction', 0);
                                    $set('absence_deduction', 0);
                                    static::recalculateCollection($set, $get);
                                } else {
                                    static::syncFinancials($set, $get, (int) $get('employee_id'), $get('payroll_month'));
                                }
                            }),

                        Select::make('employee_id')
                            ->label(__('finance.payroll_records.fields.employee'))
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
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::syncFinancials($set, $get, (int) $state, $get('payroll_month'))),

                        DatePicker::make('payroll_month')
                            ->label(__('finance.payroll_records.fields.payroll_month'))
                            ->default(now()->startOfMonth())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::syncFinancials($set, $get, (int) $get('employee_id'), $state))
                            ->rules([fn (callable $get, $record) => PayrollCalculationService::uniquePayrollRule($get, $record)]),

                        DatePicker::make('record_date')
                            ->label(__('finance.payroll_records.fields.record_date'))
                            ->default(now())
                            ->required(),

                        TextInput::make('basic_salary')
                            ->label(__('finance.payroll_records.fields.basic_salary'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->hidden(fn (callable $get) => $get('operation_type') === 'collection')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculate($set, $get, (float) $state)),
                    ]),

                Section::make(__('finance.payroll_records.sections.additions'))
                    ->description(__('finance.payroll_records.sections.additions_desc'))
                    ->icon('heroicon-o-plus-circle')
                    ->hidden(fn (callable $get) => $get('operation_type') === 'collection')
                    ->columns(3)
                    ->schema([
                        TextInput::make('overtime_amount')
                            ->label(__('finance.payroll_records.fields.overtime_amount'))
                            ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('finance.payroll_records.helpers.overtime_amount')),

                        TextInput::make('rewards_amount')
                            ->label(__('finance.payroll_records.fields.rewards_amount'))
                            ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('finance.payroll_records.helpers.rewards_amount'))
                            ->extraAttributes(['class' => 'font-semibold text-emerald-600']),

                        TextInput::make('additional_amount')
                            ->label(__('finance.payroll_records.fields.additional_amount'))
                            ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->extraAttributes(['class' => 'font-bold text-emerald-600']),
                    ]),

                Section::make(__('finance.payroll_records.sections.deductions'))
                    ->description(__('finance.payroll_records.sections.deductions_desc'))
                    ->icon('heroicon-o-minus-circle')
                    ->hidden(fn (callable $get) => $get('operation_type') === 'collection')
                    ->columns(3)
                    ->schema([
                        TextInput::make('absence_deduction')
                            ->label(__('finance.payroll_records.fields.absence_deduction'))
                            ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('finance.payroll_records.helpers.absence_deduction')),

                        TextInput::make('metadata.collected_deduction')
                            ->label(__('finance.payroll_records.fields.collected_deduction'))
                            ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->extraAttributes(['class' => 'font-bold text-purple-600'])
                            ->visible(fn (callable $get) => (float) ($get('metadata.collected_deduction') ?? 0) > 0),

                        TextInput::make('deduction_amount')
                            ->label(__('finance.payroll_records.fields.deduction_amount'))
                            ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->extraAttributes(['class' => 'font-bold text-rose-600']),

                        TextInput::make('metadata.collection_notes')
                            ->label(__('finance.payroll_records.fields.collection_notes_desc'))
                            ->disabled()->dehydrated()
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'text-purple-700 font-medium'])
                            ->visible(fn (callable $get) => (float) ($get('metadata.collected_deduction') ?? 0) > 0),
                    ]),

                Section::make(__('finance.payroll_records.sections.summary'))
                    ->description(__('finance.payroll_records.sections.summary_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('net_amount')
                            ->label(__('finance.payroll_records.fields.net_amount'))
                            ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->extraAttributes(['class' => 'font-extrabold text-xl text-primary-600'])
                            ->hidden(fn (callable $get) => $get('operation_type') === 'collection'),

                        TextInput::make('net_amount')
                            ->label(__('finance.payroll_records.fields.collection_amount'))
                            ->numeric()->prefix('SAR')->default(0)->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->extraAttributes(['class' => 'font-extrabold text-xl text-purple-600'])
                            ->visible(fn (callable $get) => $get('operation_type') === 'collection')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateCollection($set, $get, (float) $state)),

                        TextInput::make('paid_amount')
                            ->label(fn (callable $get) => $get('operation_type') === 'collection'
                                ? __('finance.payroll_records.fields.collected_amount')
                                : __('finance.payroll_records.fields.paid_amount'))
                            ->numeric()->prefix('SAR')->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($get('operation_type') === 'collection') {
                                    static::recalculateCollection($set, $get);
                                } else {
                                    static::recalculate($set, $get);
                                }
                            }),

                        TextInput::make('remaining_amount')
                            ->label(fn (callable $get) => $get('operation_type') === 'collection'
                                ? __('finance.payroll_records.fields.remaining_to_collect')
                                : __('finance.payroll_records.fields.remaining_amount'))
                            ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->extraAttributes(['class' => 'font-bold text-amber-600']),

                        Select::make('payment_status')
                            ->label(__('finance.payroll_records.fields.payment_status'))
                            ->options(PaymentStatus::class)->default(PaymentStatus::UNPAID->value)->disabled()->dehydrated(),

                        Textarea::make('notes')
                            ->label(__('finance.payroll_records.fields.notes'))
                            ->rows(2)
                            ->columnSpan(2)
                            ->hidden(fn (callable $get) => $get('operation_type') === 'collection'),

                        Textarea::make('notes')
                            ->label(__('finance.payroll_records.fields.collection_description'))
                            ->placeholder(__('finance.payroll_records.fields.collection_description_placeholder'))
                            ->rows(2)
                            ->columnSpan(2)
                            ->visible(fn (callable $get) => $get('operation_type') === 'collection'),
                    ]),
            ]);
    }

    protected static function syncFinancials(callable $set, callable $get, ?int $empId, ?string $month): void
    {
        if ($get('operation_type') === 'collection') {
            return;
        }

        $data = PayrollCalculationService::calculateEmployeeFinancials($empId, $month);
        foreach ($data as $key => $val) {
            if ($val !== null) {
                $set($key, $val);
            }
        }

        if (isset($data['collected_deduction'])) {
            $set('metadata.collected_deduction', $data['collected_deduction']);
            $set('metadata.collection_notes', $data['collection_notes'] ?? '');
        }
    }

    protected static function recalculate(callable $set, callable $get, ?float $basicSalary = null): void
    {
        $totals = PayrollCalculationService::computeTotals(
            $basicSalary ?? (float) ($get('basic_salary') ?? 0),
            (float) ($get('additional_amount') ?? 0),
            (float) ($get('deduction_amount') ?? 0),
            (float) ($get('paid_amount') ?? 0)
        );
        foreach ($totals as $key => $val) {
            $set($key, $val);
        }
    }

    protected static function recalculateCollection(callable $set, callable $get, ?float $netAmount = null): void
    {
        $net = $netAmount ?? (float) ($get('net_amount') ?? 0);
        $paid = (float) ($get('paid_amount') ?? 0);
        $remaining = round(max(0, $net - $paid), 2);
        $set('remaining_amount', $remaining);

        $status = ($paid >= $net && $net > 0)
            ? PaymentStatus::PAID->value
            : ($paid > 0 ? PaymentStatus::PARTIALLY_PAID->value : PaymentStatus::UNPAID->value);
        $set('payment_status', $status);
    }
}
