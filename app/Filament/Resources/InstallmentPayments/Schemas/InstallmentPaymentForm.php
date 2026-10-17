<?php

namespace App\Filament\Resources\InstallmentPayments\Schemas;

use App\Enums\InstallmentStatus;
use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use App\Models\Installment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstallmentPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.installment_payments.sections.payment_info'))
                    ->description(__('finance.installment_payments.sections.payment_info_desc'))
                    ->icon('heroicon-o-receipt-percent')
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        ToggleButtons::make('payment_type')
                            ->label(__('finance.installment_payments.fields.payment_type'))
                            ->options([
                                'installment' => __('finance.installment_payments.types.installment'),
                                'advance'     => __('finance.installment_payments.types.advance'),
                            ])
                            ->icons([
                                'installment' => 'heroicon-m-calendar-days',
                                'advance'     => 'heroicon-m-banknotes',
                            ])
                            ->colors([
                                'installment' => 'info',
                                'advance'     => 'success',
                            ])
                            ->default('installment')
                            ->inline()
                            ->grouped()
                            ->required()
                            ->reactive()
                            ->extraAttributes([
                                'class' => '!w-full !max-w-full',
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('installment_id', null);
                                $set('amount', 0);
                            }),

                        ToggleButtons::make('beneficiary_type')
                            ->label(__('finance.installments.fields.beneficiary_type'))
                            ->options([
                                'internal' => __('finance.installments.toggle_options.internal'),
                                'external' => __('finance.installments.toggle_options.external'),
                            ])
                            ->icons([
                                'internal' => 'heroicon-m-building-office-2',
                                'external' => 'heroicon-m-user',
                            ])
                            ->colors([
                                'internal' => 'primary',
                                'external' => 'warning',
                            ])
                            ->default('internal')
                            ->inline()
                            ->grouped()
                            ->reactive()
                            ->extraAttributes([
                                'class' => '!w-full !max-w-full',
                            ])
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('installment_id', null);
                                $set('employee_id', null);
                                $set('amount', 0);
                            }),

                        TextInput::make('voucher_number')
                            ->label(__('finance.installment_payments.fields.voucher_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('payment_date')
                            ->label(__('finance.installment_payments.fields.payment_date'))
                            ->default(now())
                            ->required(),

                        OrgSelect::company('company_id', __('finance.installment_payments.fields.company'), ['employee_id', 'installment_id'])
                            ->visible(fn (callable $get) => ($get('beneficiary_type') ?? 'internal') === 'internal'),

                        static::getEmployeeSelect(),

                        static::getInstallmentSelect(),

                        TextInput::make('amount')
                            ->label(__('finance.installment_payments.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        Select::make('payment_source')
                            ->label(__('finance.installment_payments.fields.payment_source'))
                            ->options(\App\Enums\InstallmentPaymentSource::class)
                            ->default(\App\Enums\InstallmentPaymentSource::CASH->value)
                            ->required()
                            ->reactive(),

                        Select::make('treasury_transaction_id')
                            ->label(__('finance.installment_payments.fields.treasury_transaction'))
                            ->relationship('treasuryTransaction', 'voucher_number', fn ($query) => $query->where('transaction_type', 'incoming'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => ($get('payment_source') instanceof \App\Enums\InstallmentPaymentSource ? $get('payment_source')->value : $get('payment_source')) === 'cash'),

                        Select::make('payroll_record_id')
                            ->label(__('finance.installment_payments.fields.payroll_record'))
                            ->relationship('payrollRecord', 'record_number')
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => ($get('payment_source') instanceof \App\Enums\InstallmentPaymentSource ? $get('payment_source')->value : $get('payment_source')) === 'payroll_deduction'),

                        Select::make('executed_by')
                            ->label(__('finance.installment_payments.fields.executed_by'))
                            ->relationship('executor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->employee?->id)
                            ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                            ->dehydrated(),

                        Textarea::make('description')
                            ->label(__('finance.installment_payments.fields.description'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Reusable Employee Select component with filtering for active installments.
     */
    public static function getEmployeeSelect(): Select
    {
        return Select::make('employee_id')
            ->label(__('finance.installment_payments.fields.employee'))
            ->relationship('employee', 'id', fn ($query, callable $get) => static::filterEmployeesWithInstallments($query, $get))
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
            ->searchable()
            ->preload()
            ->visible(fn (callable $get) => ($get('beneficiary_type') ?? 'internal') === 'internal')
            ->reactive()
            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::handleEmployeeSelected($state, $set, $get));
    }

    /**
     * Reusable Installment Select component filtered by type and beneficiary.
     */
    public static function getInstallmentSelect(): Select
    {
        return Select::make('installment_id')
            ->label(__('finance.installment_payments.fields.installment'))
            ->relationship('installment', 'installment_number', fn ($query, callable $get) => static::filterInstallments($query, $get))
            ->getOptionLabelFromRecordUsing(function ($record) {
                $beneficiary = $record->beneficiary_name;
                $rem = number_format((float)$record->remaining_amount, 2);
                $code = $record->installment_number;
                $remainingLabel = __('finance.installments.fields.remaining_amount');
                return "{$beneficiary}  —  ({$code})  —  {$remainingLabel}: {$rem}";
            })
            ->searchable(['installment_number', 'external_name', 'external_national_id', 'external_phone'])
            ->preload()
            ->required()
            ->reactive()
            ->columnSpanFull()
            ->afterStateUpdated(fn ($state, callable $set) => static::handleInstallmentSelected($state, $set));
    }

    /**
     * Query scope: Only employees having active installments/advances with remaining amount.
     */
    protected static function filterEmployeesWithInstallments($query, callable $get): void
    {
        $companyId = $get('company_id') ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $paymentType = $get('payment_type') ?? 'installment';

        $query->whereHas('installments', function ($q) use ($paymentType) {
            $q->where('remaining_amount', '>', 0)
              ->where('transaction_type', $paymentType)
              ->whereNotIn('status', [InstallmentStatus::COMPLETED, InstallmentStatus::CANCELLED]);
        });
    }

    /**
     * Query scope: Active installments for selected employee / company or external.
     */
    protected static function filterInstallments($query, callable $get): void
    {
        $query->where('remaining_amount', '>', 0)
              ->whereNotIn('status', [InstallmentStatus::COMPLETED, InstallmentStatus::CANCELLED]);

        $paymentType = $get('payment_type');
        if ($paymentType) {
            $query->where('transaction_type', $paymentType);
        }

        $beneficiaryType = $get('beneficiary_type') ?? 'internal';
        if ($beneficiaryType === 'external') {
            $query->where('beneficiary_type', 'external');
        } else {
            $query->where(function ($q) {
                $q->where('beneficiary_type', 'internal')
                  ->orWhereNull('beneficiary_type');
            });

            $employeeId = $get('employee_id');
            if ($employeeId) {
                $query->where('employee_id', $employeeId);
            } else {
                $companyId = $get('company_id') ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
            }
        }
    }

    /**
     * Handle employee selection: auto-fill installment if only 1 exists, sync company.
     */
    protected static function handleEmployeeSelected($state, callable $set, callable $get): void
    {
        if (! $state) {
            $set('installment_id', null);
            $set('amount', 0);
            return;
        }

        $employee = Employee::find($state);
        if ($employee && $employee->company_id) {
            $set('company_id', $employee->company_id);
        }

        $paymentType = $get('payment_type') ?? 'installment';

        $activeInstallments = Installment::where('employee_id', $state)
            ->where('remaining_amount', '>', 0)
            ->where('transaction_type', $paymentType)
            ->whereNotIn('status', [InstallmentStatus::COMPLETED, InstallmentStatus::CANCELLED])
            ->get();

        if ($activeInstallments->count() === 1) {
            $single = $activeInstallments->first();
            $set('installment_id', $single->id);
            $set('amount', (float)$single->monthly_amount > 0 ? (float)$single->monthly_amount : (float)$single->remaining_amount);
        } else {
            $set('installment_id', null);
            $set('amount', 0);
        }
    }

    /**
     * Handle installment selection: auto-populate company, employee, and monthly amount.
     */
    protected static function handleInstallmentSelected($state, callable $set): void
    {
        if (! $state) {
            return;
        }

        $installment = Installment::find($state);
        if ($installment) {
            if ($installment->company_id) {
                $set('company_id', $installment->company_id);
            }
            if ($installment->employee_id) {
                $set('employee_id', $installment->employee_id);
            }
            if ($installment->transaction_type) {
                $set('payment_type', $installment->transaction_type);
            }
            if ($installment->beneficiary_type) {
                $set('beneficiary_type', $installment->beneficiary_type);
            }

            $set('amount', (float)$installment->monthly_amount > 0 ? (float)$installment->monthly_amount : (float)$installment->remaining_amount);
        }
    }
}
