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
                            ->label('نوع العملية')
                            ->options([
                                'payroll'    => 'رواتب 💵',
                                'collection' => 'تحصيل 📥',
                            ])
                            ->default('payroll')
                            ->required(),

                        Select::make('employee_id')
                            ->label(__('finance.payroll_records.fields.employee'))
                            ->relationship('employee', 'id', function ($query, callable $get) {
                                $companyId = $get('company_id') ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
                                if ($companyId) $query->where('company_id', $companyId);
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::syncFinancials($set, $get, (int)$state, $get('payroll_month'))),

                        DatePicker::make('payroll_month')
                            ->label(__('finance.payroll_records.fields.payroll_month'))
                            ->default(now()->startOfMonth())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::syncFinancials($set, $get, (int)$get('employee_id'), $state))
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
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculate($set, $get, (float)$state)),
                    ]),

                Section::make(__('finance.payroll_records.sections.additions'))
                     ->description(__('finance.payroll_records.sections.additions_desc'))
                     ->icon('heroicon-o-plus-circle')
                     ->columns(3)
                     ->schema([
                         TextInput::make('overtime_amount')
                             ->label(__('finance.payroll_records.fields.overtime_amount'))
                             ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->helperText('محسوبة آلياً من ساعات العمل الإضافي'),

                         TextInput::make('rewards_amount')
                             ->label(__('finance.payroll_records.fields.rewards_amount'))
                             ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->helperText('المكافآت المعتمدة للشهر فقط')
                             ->extraAttributes(['class' => 'font-semibold text-emerald-600']),

                         TextInput::make('additional_amount')
                             ->label(__('finance.payroll_records.fields.additional_amount'))
                             ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->extraAttributes(['class' => 'font-bold text-emerald-600']),
                     ]),

                Section::make(__('finance.payroll_records.sections.deductions'))
                     ->description(__('finance.payroll_records.sections.deductions_desc'))
                     ->icon('heroicon-o-minus-circle')
                     ->columns(3)
                     ->schema([
                         TextInput::make('advance_deduction')
                             ->label(__('finance.payroll_records.fields.advance_deduction'))
                             ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->helperText('أقساط السلف والديون المستحقة')
                             ->extraAttributes(['class' => 'font-semibold text-amber-600']),

                         TextInput::make('absence_deduction')
                             ->label(__('finance.payroll_records.fields.absence_deduction'))
                             ->numeric()->prefix('SAR')->default(0)->disabled()->dehydrated()
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->helperText('خصم الغياب والتأخير من سجل البصمة'),

                         TextInput::make('deduction_amount')
                             ->label(__('finance.payroll_records.fields.deduction_amount'))
                             ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->extraAttributes(['class' => 'font-bold text-rose-600']),
                     ]),

                Section::make(__('finance.payroll_records.sections.summary'))
                     ->description(__('finance.payroll_records.sections.summary_desc'))
                     ->icon('heroicon-o-banknotes')
                     ->columns(3)
                     ->schema([
                         TextInput::make('net_amount')
                             ->label(__('finance.payroll_records.fields.net_amount'))
                             ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->extraAttributes(['class' => 'font-extrabold text-xl text-primary-600']),

                         TextInput::make('paid_amount')
                             ->label(__('finance.payroll_records.fields.paid_amount'))
                             ->numeric()->prefix('SAR')->default(0)
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->reactive()
                             ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculate($set, $get)),

                         TextInput::make('remaining_amount')
                             ->label(__('finance.payroll_records.fields.remaining_amount'))
                             ->numeric()->prefix('SAR')->disabled()->dehydrated()->default(0)
                             ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                             ->extraAttributes(['class' => 'font-bold text-amber-600']),

                        Select::make('payment_status')
                            ->label(__('finance.payroll_records.fields.payment_status'))
                            ->options(PaymentStatus::class)->default(PaymentStatus::UNPAID->value)->disabled()->dehydrated(),

                        Textarea::make('notes')
                            ->label(__('finance.payroll_records.fields.notes'))->rows(2)->columnSpan(2),
                    ]),
            ]);
    }

    protected static function syncFinancials(callable $set, callable $get, ?int $empId, ?string $month): void
    {
        $data = PayrollCalculationService::calculateEmployeeFinancials($empId, $month);
        foreach ($data as $key => $val) {
            if ($val !== null) $set($key, $val);
        }
    }

    protected static function recalculate(callable $set, callable $get, ?float $basicSalary = null): void
    {
        $totals = PayrollCalculationService::computeTotals(
            $basicSalary ?? (float)($get('basic_salary') ?? 0),
            (float)($get('additional_amount') ?? 0),
            (float)($get('deduction_amount') ?? 0),
            (float)($get('paid_amount') ?? 0)
        );
        foreach ($totals as $key => $val) {
            $set($key, $val);
        }
    }
}
