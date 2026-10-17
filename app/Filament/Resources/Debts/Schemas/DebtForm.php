<?php

namespace App\Filament\Resources\Debts\Schemas;

use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DebtForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. مصدر وتاريخ الدين
                Section::make(__('debts.sections.debtor_source'))
                    ->description(__('debts.sections.debtor_source_desc'))
                    ->icon('heroicon-o-user-circle')
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('debtor_type')
                            ->label(__('debts.fields.debtor_type'))
                            ->options([
                                'internal' => __('debts.toggle_options.internal'),
                                'external' => __('debts.toggle_options.external'),
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
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'external') {
                                    $set('company_id', null);
                                    $set('employee_id', null);
                                } else {
                                    $set('external_name', null);
                                    $set('external_national_id', null);
                                    $set('external_phone', null);
                                    $set('external_email', null);
                                    $set('external_address', null);
                                }
                            }),

                        TextInput::make('debt_number')
                            ->label(__('debts.fields.debt_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('debt_date')
                            ->label(__('debts.fields.debt_date'))
                            ->default(now())
                            ->required(),

                        DatePicker::make('due_date')
                            ->label(__('debts.fields.due_date')),

                        Select::make('status')
                            ->label(__('debts.fields.status'))
                            ->options([
                                'pending' => __('debts.statuses.pending'),
                                'partial' => __('debts.statuses.partial'),
                                'paid'    => __('debts.statuses.paid'),
                                'overdue' => __('debts.statuses.overdue'),
                            ])
                            ->default('pending')
                            ->disabled(fn ($record) => $record === null)
                            ->dehydrated(),
                    ]),

                // 2. بيانات المنشأة والموظف المدين (في حال كان داخلياً)
                Section::make(__('debts.sections.internal_debtor'))
                    ->description(__('debts.sections.internal_debtor_desc'))
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->visible(fn (callable $get) => ($get('debtor_type') ?? 'internal') === 'internal')
                    ->schema([
                        OrgSelect::company('company_id', __('debts.fields.company'), ['employee_id']),

                        Select::make('employee_id')
                            ->label(__('debts.fields.employee'))
                            ->relationship('employee', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee && $employee->company_id) {
                                        $set('company_id', $employee->company_id);
                                    }
                                }
                            }),
                    ]),

                // 3. السجل القانوني الكامل (في حال كان خارج المنشآت)
                Section::make(__('debts.sections.external_legal_info'))
                    ->description(__('debts.sections.external_legal_info_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('debtor_type') === 'external')
                    ->schema([
                        TextInput::make('external_name')
                            ->label(__('debts.fields.external_name'))
                            ->placeholder(__('debts.placeholders.external_name'))
                            ->required(fn (callable $get) => $get('debtor_type') === 'external')
                            ->maxLength(255),

                        TextInput::make('external_national_id')
                            ->label(__('debts.fields.external_national_id'))
                            ->placeholder(__('debts.placeholders.external_national_id'))
                            ->required(fn (callable $get) => $get('debtor_type') === 'external')
                            ->maxLength(50),

                        TextInput::make('external_phone')
                            ->label(__('debts.fields.external_phone'))
                            ->placeholder(__('debts.placeholders.external_phone'))
                            ->tel()
                            ->required(fn (callable $get) => $get('debtor_type') === 'external')
                            ->maxLength(50),

                        TextInput::make('external_email')
                            ->label(__('debts.fields.external_email'))
                            ->email()
                            ->maxLength(100),

                        TextInput::make('external_address')
                            ->label(__('debts.fields.external_address'))
                            ->placeholder(__('debts.placeholders.external_address'))
                            ->columnSpanFull(),
                    ]),

                // 4. بيانات الضامن أو الكفيل (اختياري)
                Section::make(__('debts.sections.guarantor_info'))
                    ->description(__('debts.sections.guarantor_info_desc'))
                    ->icon('heroicon-o-user-group')
                    ->columns(1)
                    ->collapsed(true)
                    ->collapsible()
                    ->schema([
                        Select::make('guarantor_id')
                            ->label(__('debts.fields.guarantor'))
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

                                        TextInput::make('phone')
                                            ->label(__('guarantors.fields.phone'))
                                            ->placeholder(__('debts.placeholders.external_phone'))
                                            ->tel()
                                            ->maxLength(50),

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
                            ]),
                    ]),

                // 5. البيانات المالية وأصل الدين
                Section::make(__('debts.sections.financial'))
                    ->description(__('debts.sections.financial_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(2)
                    ->schema([
                        TextInput::make('total_amount')
                            ->label(__('debts.fields.total_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $total = (float)($state ?? 0);
                                $paid = (float)($get('paid_amount') ?? 0);
                                $set('remaining_amount', max(0, $total - $paid));
                            }),

                        TextInput::make('paid_amount')
                            ->label(__('debts.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('remaining_amount')
                            ->label(__('debts.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('debt_reason')
                            ->label(__('debts.fields.debt_reason'))
                            ->placeholder(__('debts.placeholders.debt_reason'))
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('debts.fields.description'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // 6. المتابعة والتنبيهات
                Section::make(__('debts.sections.notification'))
                    ->description(__('debts.sections.notification_desc'))
                    ->icon('heroicon-o-bell')
                    ->columns(2)
                    ->collapsed(true)
                    ->collapsible()
                    ->schema([
                        Select::make('notify_employee_id')
                            ->label(__('debts.fields.notify_employee'))
                            ->relationship('notifyEmployee', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload(),

                        Select::make('executed_by')
                            ->label(__('debts.fields.executed_by'))
                            ->relationship('executor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->employee?->id)
                            ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                            ->dehydrated(),

                        Textarea::make('notification_notes')
                            ->label(__('debts.fields.notification_notes'))
                            ->placeholder(__('debts.placeholders.notification_notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
