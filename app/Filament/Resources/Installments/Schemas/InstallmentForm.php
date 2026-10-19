<?php

namespace App\Filament\Resources\Installments\Schemas;

use App\Enums\InstallmentStatus;
use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use App\Models\Guarantor;
use App\Models\InstallmentType;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstallmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.installments.sections.type_and_source'))
                    ->description(__('finance.installments.sections.type_and_source_desc'))
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->schema([
                        ToggleButtons::make('transaction_type')
                            ->label(__('finance.installments.fields.transaction_type'))
                            ->options([
                                'installment' => __('finance.installments.toggle_options.installment'),
                                'advance'     => __('finance.installments.toggle_options.advance'),
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
                            ]),

                        ToggleButtons::make('beneficiary_type')
                            ->label(__('finance.installments.fields.beneficiary_type'))
                            ->options([
                                'internal' => __('finance.installments.toggle_options.internal'),
                                'external' => __('finance.installments.toggle_options.external'),
                            ])
                            ->icons([
                                'internal' => 'heroicon-m-building-office-2',
                                'external' => 'heroicon-m-user-plus',
                            ])
                            ->colors([
                                'internal' => 'primary',
                                'external' => 'warning',
                            ])
                            ->default('internal')
                            ->inline()
                            ->grouped()
                            ->required()
                            ->reactive()
                            ->extraAttributes([
                                'class' => '!w-full !max-w-full',
                            ]),

                        DatePicker::make('installment_date')
                            ->label(__('finance.installments.fields.installment_date'))
                            ->default(now())
                            ->required(),

                        Select::make('status')
                            ->label(__('finance.installments.fields.status'))
                            ->options(InstallmentStatus::class)
                            ->default(InstallmentStatus::OPEN->value)
                            ->required(),

                        TextInput::make('installment_number')
                            ->label(__('finance.installments.fields.installment_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('finance.installments.sections.loan_info'))
                    ->description(__('finance.installments.sections.loan_info_desc'))
                    ->icon('heroicon-o-building-office-2')
                    ->columns(3)
                    ->visible(fn (callable $get) => ($get('beneficiary_type') ?? 'internal') === 'internal')
                    ->schema([
                        OrgSelect::company('company_id', __('finance.installments.fields.company'), 'employee_id'),

                        Select::make('employee_id')
                            ->label(__('finance.installments.fields.employee'))
                            ->relationship('employee', 'id', function ($query, callable $get) {
                                $companyId = $get('company_id') ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
                                if ($companyId) {
                                    $query->where('company_id', $companyId);
                                }
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? (__('employees.single') . " #{$record->id}"))
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get) => ($get('beneficiary_type') ?? 'internal') === 'internal')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee && $employee->company_id) {
                                        $set('company_id', $employee->company_id);
                                    }
                                }
                            }),

                        static::getInstallmentTypeSelect(isRequired: true),
                    ]),

                Section::make(__('finance.installments.sections.external_legal_info'))
                    ->description(__('finance.installments.sections.external_legal_info_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->visible(fn (callable $get) => $get('beneficiary_type') === 'external')
                    ->schema([
                        TextInput::make('external_name')
                            ->label(__('finance.installments.fields.external_name'))
                            ->placeholder(__('finance.installments.placeholders.external_name'))
                            ->required(fn (callable $get) => $get('beneficiary_type') === 'external')
                            ->maxLength(255),

                        TextInput::make('external_national_id')
                            ->label(__('finance.installments.fields.external_national_id'))
                            ->placeholder(__('finance.installments.placeholders.external_national_id'))
                            ->required(fn (callable $get) => $get('beneficiary_type') === 'external')
                            ->maxLength(50),

                        PhoneService::makeFormField('external_phone', __('finance.installments.fields.external_phone'))
                            ->required(fn (callable $get) => $get('beneficiary_type') === 'external'),

                        TextInput::make('external_email')
                            ->label(__('finance.installments.fields.external_email'))
                            ->email()
                            ->maxLength(100),

                        TextInput::make('external_address')
                            ->label(__('finance.installments.fields.external_address'))
                            ->placeholder(__('finance.installments.placeholders.external_address')),

                        static::getInstallmentTypeSelect(isRequired: false),
                    ]),

                Section::make(__('guarantors.section_title'))
                    ->description(__('guarantors.section_desc'))
                    ->icon('heroicon-o-user-group')
                    ->columns(1)
                    ->collapsed(true)
                    ->collapsible()
                    ->schema([
                        Select::make('guarantor_id')
                            ->label(__('finance.installments.fields.guarantor'))
                            ->relationship('guarantor', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} " . ($record->national_id ? "({$record->national_id})" : '') . ($record->phone ? " - {$record->phone}" : ''))
                            ->searchable(['name', 'national_id', 'phone'])
                            ->preload()
                            ->createOptionModalHeading(__('guarantors.actions.create'))
                            ->createOptionAction(fn ($action) => $action->modalWidth('3xl'))
                            ->createOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('type')
                                            ->label(__('guarantors.fields.type'))
                                            ->options([
                                                'internal' => __('guarantors.types.internal'),
                                                'external' => __('guarantors.types.external'),
                                            ])
                                            ->default('external')
                                            ->required(),

                                        TextInput::make('name')
                                            ->label(__('guarantors.fields.name'))
                                            ->placeholder(__('guarantors.placeholders.name'))
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('national_id')
                                            ->label(__('guarantors.fields.national_id'))
                                            ->placeholder(__('guarantors.placeholders.national_id'))
                                            ->maxLength(50),

                                        PhoneService::makeFormField('phone', __('guarantors.fields.phone')),

                                        TextInput::make('job_title')
                                            ->label(__('guarantors.fields.job_title'))
                                            ->placeholder(__('guarantors.placeholders.job_title'))
                                            ->maxLength(100),

                                        TextInput::make('address')
                                            ->label(__('guarantors.fields.address'))
                                            ->placeholder(__('guarantors.placeholders.address')),

                                        Textarea::make('notes')
                                            ->label(__('guarantors.fields.notes'))
                                            ->placeholder(__('guarantors.placeholders.notes'))
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->createOptionUsing(function (array $data, callable $get) {
                                $companyId = $get('company_id') ?? auth()->user()?->company_id;
                                return Guarantor::create([
                                    'type'        => $data['type'] ?? 'external',
                                    'company_id'  => $companyId,
                                    'name'        => $data['name'],
                                    'national_id' => $data['national_id'] ?? null,
                                    'phone'       => $data['phone'] ?? null,
                                    'job_title'   => $data['job_title'] ?? null,
                                    'address'     => $data['address'] ?? null,
                                    'notes'       => $data['notes'] ?? null,
                                    'status'      => 'active',
                                ])->id;
                            }),
                    ]),

                Section::make(__('finance.installments.sections.financial'))
                    ->description(__('finance.installments.sections.financial_desc'))
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_amount')
                            ->label(__('finance.installments.fields.total_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateLoan($set, $get, (float)$state)),

                        TextInput::make('down_payment')
                            ->label(__('finance.installments.fields.down_payment'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->placeholder('0.00')
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateLoan($set, $get)),

                        TextInput::make('months_count')
                            ->label(__('finance.installments.fields.months_count'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->dehydrateStateUsing(fn ($state) => max(1, (int)($state ?: 1)))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateLoan($set, $get)),

                        TextInput::make('monthly_amount')
                            ->label(__('finance.installments.fields.monthly_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->helperText(__('finance.installments.placeholders.monthly_amount_helper')),

                        TextInput::make('paid_amount')
                            ->label(__('finance.installments.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalculateLoan($set, $get)),

                        TextInput::make('remaining_amount')
                            ->label(__('finance.installments.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->extraAttributes(['class' => 'font-bold text-lg text-amber-600']),

                        DatePicker::make('start_due_date')
                            ->label(__('finance.installments.fields.start_due_date'))
                            ->default(now()),

                        DatePicker::make('final_due_date')
                            ->label(__('finance.installments.fields.final_due_date')),

                        Select::make('executed_by')
                            ->label(__('finance.installments.fields.executed_by'))
                            ->relationship('executor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? (__('employees.single') . " #{$record->id}"))
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->employee?->id)
                            ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                            ->dehydrated(),

                        Textarea::make('description')
                            ->label(__('finance.installments.fields.description'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('finance.installments.sections.notification'))
                    ->description(__('finance.installments.sections.notification_desc'))
                    ->icon('heroicon-o-bell-alert')
                    ->columns(2)
                    ->schema([
                        Select::make('notify_employee_id')
                            ->label(__('finance.installments.fields.notify_employee'))
                            ->relationship('notifyEmployee', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? (__('employees.single') . " #{$record->id}"))
                            ->searchable()
                            ->preload(),

                        Textarea::make('notification_notes')
                            ->label(__('finance.installments.fields.notification_notes'))
                            ->placeholder(__('finance.installments.placeholders.notification_notes'))
                            ->rows(2),
                    ]),
            ]);
    }

    public static function recalculateLoan(callable $set, callable $get, ?float $totalAmount = null): void
    {
        $total = $totalAmount !== null ? $totalAmount : (float)($get('total_amount') ?? 0);
        $down = (float)($get('down_payment') ?? 0);
        $months = max(1, (int)($get('months_count') ?? 1));
        $paid = (float)($get('paid_amount') ?? 0);

        $netToPay = max(0, $total - $down);
        $monthly = round($netToPay / $months, 2);
        $remaining = round(max(0, $total - $down - $paid), 2);

        $set('monthly_amount', $monthly);
        $set('remaining_amount', $remaining);
    }

    public static function getInstallmentTypeSelect(bool $isRequired = true): Select
    {
        return Select::make('installment_type_id')
            ->label(fn (callable $get) => ($get('transaction_type') ?? 'installment') === 'advance' 
                ? __('finance.installments.fields.advance_type') 
                : __('finance.installments.fields.installment_type_only'))
            ->options(function (callable $get) {
                $transactionType = $get('transaction_type') ?? 'installment';
                $companyId = $get('company_id') ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;

                $query = InstallmentType::where('status', 'active')
                    ->where(function ($q) use ($companyId) {
                        $q->whereNull('company_id');
                        if ($companyId) {
                            $q->orWhere('company_id', $companyId);
                        }
                    });

                if ($transactionType === 'advance') {
                    $query->where('category', 'advances');
                } else {
                    $query->whereIn('category', ['debts', 'others']);
                }

                $items = $query->get();

                if ($items->isEmpty()) {
                    $items = InstallmentType::where('status', 'active')->get();
                }

                $grouped = [];
                foreach ($items as $item) {
                    $categoryName = $item->category instanceof \App\Enums\InstallmentCategory
                        ? $item->category->getLabel()
                        : __('finance.payment_collection_items.categories.other');
                    $grouped[$categoryName][$item->id] = $item->name;
                }

                return $grouped;
            })
            ->searchable()
            ->preload()
            ->reactive()
            ->required($isRequired)
            ->createOptionAction(fn ($action) => $action->modalWidth('xl'))
            ->createOptionForm([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('finance.installment_types.fields.name'))
                            ->placeholder(__('finance.installments.placeholders.installment_type_name'))
                            ->required(),
                        Select::make('category')
                            ->label(__('finance.installment_types.fields.category'))
                            ->options(\App\Enums\InstallmentCategory::class)
                            ->default(fn (callable $get) => ($get('transaction_type') ?? 'installment') === 'advance' ? 'advances' : 'others')
                            ->required(),
                    ]),
            ])
            ->createOptionUsing(function (array $data, callable $get) {
                $companyId = $get('company_id') ?? auth()->user()?->company_id;
                return InstallmentType::create([
                    'name'       => $data['name'],
                    'category'   => $data['category'],
                    'company_id' => $companyId,
                    'status'     => 'active',
                ])->id;
            });
    }
}
