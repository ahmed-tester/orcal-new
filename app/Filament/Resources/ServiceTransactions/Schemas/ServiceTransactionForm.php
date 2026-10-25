<?php

namespace App\Filament\Resources\ServiceTransactions\Schemas;

use App\Enums\ApprovalStatus;
use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use App\Models\Service;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('services_tracking.sections.scope_and_date'))
                    ->description(__('services_tracking.sections.scope_and_date_desc'))
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('scope_type')
                            ->label(__('services_tracking.fields.scope_type'))
                            ->options([
                                'internal' => __('services_tracking.toggle_options.internal'),
                                'external' => __('services_tracking.toggle_options.external'),
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
                            ->columnSpanFull(),

                        TextInput::make('transaction_number')
                            ->label(__('services_tracking.fields.transaction_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('transaction_date')
                            ->label(__('services_tracking.fields.transaction_date'))
                            ->default(now())
                            ->required(),

                        DatePicker::make('due_date')
                            ->label(__('services_tracking.fields.due_date')),

                        ToggleButtons::make('status')
                            ->label(__('services_tracking.fields.status'))
                            ->options(ApprovalStatus::class)
                            ->default(ApprovalStatus::UNAPPROVED->value)
                            ->inline()
                            ->grouped()
                            ->disabled(fn () => ! (auth()->user()?->can('approve_service_transaction') ?? false))
                            ->dehydrated(),
                    ]),

                Section::make(__('services_tracking.sections.internal_service'))
                    ->description(__('services_tracking.sections.internal_service_desc'))
                    ->icon('heroicon-o-building-office-2')
                    ->columns(2)
                    ->visible(fn (callable $get) => ($get('scope_type') ?? 'internal') === 'internal')
                    ->schema([
                        ToggleButtons::make('service_target')
                            ->label(__('services_tracking.fields.service_target'))
                            ->options([
                                'company' => __('services_tracking.toggle_options.target_company'),
                                'employee' => __('services_tracking.toggle_options.target_employee'),
                            ])
                            ->icons([
                                'company' => 'heroicon-m-building-office',
                                'employee' => 'heroicon-m-user-group',
                            ])
                            ->colors([
                                'company' => 'info',
                                'employee' => 'success',
                            ])
                            ->default('company')
                            ->inline()
                            ->grouped()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('service_id', null);
                                if ($state === 'company') {
                                    $set('employee_id', null);
                                }
                            })
                            ->columnSpanFull(),

                        OrgSelect::company('company_id', __('services_tracking.fields.company'), ['employee_id']),

                        Select::make('employee_id')
                            ->label(__('services_tracking.fields.employee'))
                            ->relationship('employee', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? (__('employees.single')." #{$record->id}"))
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => $get('service_target') === 'employee')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $emp = Employee::find($state);
                                    if ($emp && $emp->company_id) {
                                        $set('company_id', $emp->company_id);
                                    }
                                }
                            }),

                        Select::make('service_id')
                            ->label(__('services_tracking.fields.service'))
                            ->relationship('service', 'name', function ($query, callable $get) {
                                $target = $get('service_target') ?? 'company';

                                return $query->whereIn('target_type', [$target, 'general'])->where('status', 'active');
                            })
                            ->getOptionLabelFromRecordUsing(fn (Service $record) => "{$record->name} • ".number_format($record->price, 2).' SAR')
                            ->searchable(['name'])
                            ->preload()
                            ->required(fn (callable $get) => ($get('scope_type') ?? 'internal') === 'internal')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    $svc = Service::find($state);
                                    if ($svc) {
                                        $price = (float) $svc->price;
                                        $set('total_amount', $price);
                                        $paid = (float) ($get('paid_amount') ?? 0);
                                        $set('remaining_amount', max(0, $price - $paid));
                                    }
                                }
                            })
                            ->columnSpanFull()
                            ->createOptionModalHeading(__('services_tracking.quick_add.modal_title'))
                            ->createOptionAction(fn ($action) => $action->modalWidth('2xl'))
                            ->createOptionForm([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('services_tracking.quick_add.name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Select::make('target_type')
                                            ->label(__('services_tracking.quick_add.target_type'))
                                            ->options([
                                                'company' => __('services_tracking.toggle_options.target_company'),
                                                'employee' => __('services_tracking.toggle_options.target_employee'),
                                                'general' => __('services.general'),
                                            ])
                                            ->default(fn (callable $get) => $get('../../service_target') ?? 'company')
                                            ->required(),

                                        Select::make('category')
                                            ->label(__('services_tracking.quick_add.category'))
                                            ->options([
                                                'cr_licenses' => __('services_tracking.categories.cr_licenses'),
                                                'municipal' => __('services_tracking.categories.municipal'),
                                                'work_permits' => __('services_tracking.categories.work_permits'),
                                                'passports' => __('services_tracking.categories.passports'),
                                                'visas' => __('services_tracking.categories.visas'),
                                                'insurance' => __('services_tracking.categories.insurance'),
                                                'other' => __('services_tracking.categories.other'),
                                            ])
                                            ->default('other')
                                            ->required(),

                                        TextInput::make('price')
                                            ->label(__('services_tracking.quick_add.price'))
                                            ->numeric()
                                            ->prefix('SAR')
                                            ->default(0)
                                            ->required(),

                                        TextInput::make('default_gov_fee')
                                            ->label(__('services.estimated_gov_fee'))
                                            ->numeric()
                                            ->prefix('SAR')
                                            ->default(0),

                                        Textarea::make('notes')
                                            ->label(__('services_tracking.quick_add.notes'))
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),

                Section::make(__('services_tracking.sections.external_service'))
                    ->description(__('services_tracking.sections.external_service_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->visible(fn (callable $get) => $get('scope_type') === 'external')
                    ->schema([
                        TextInput::make('service_name')
                            ->label(__('services_tracking.fields.service_name'))
                            ->placeholder(__('services_tracking.placeholders.service_name'))
                            ->required(fn (callable $get) => $get('scope_type') === 'external')
                            ->maxLength(255),

                        TextInput::make('service_type')
                            ->label(__('services_tracking.fields.service_type'))
                            ->placeholder(__('services_tracking.placeholders.service_type'))
                            ->maxLength(100),

                        TextInput::make('first_party')
                            ->label(__('services_tracking.fields.first_party'))
                            ->placeholder(__('services_tracking.placeholders.first_party'))
                            ->maxLength(255),

                        TextInput::make('beneficiary_name')
                            ->label(__('services_tracking.fields.beneficiary_name'))
                            ->placeholder(__('services_tracking.placeholders.beneficiary_name'))
                            ->required(fn (callable $get) => $get('scope_type') === 'external')
                            ->maxLength(255),

                        PhoneService::makeFormField('beneficiary_phone', __('services_tracking.fields.beneficiary_phone')),

                        TextInput::make('beneficiary_national_id')
                            ->label(__('services_tracking.fields.beneficiary_national_id'))
                            ->placeholder(__('services_tracking.placeholders.beneficiary_national_id'))
                            ->maxLength(50),
                    ]),

                Section::make(__('services_tracking.sections.financial'))
                    ->description(__('services_tracking.sections.financial_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_amount')
                            ->label(__('services_tracking.fields.total_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $total = (float) ($state ?? 0);
                                $paid = (float) ($get('paid_amount') ?? 0);
                                $set('remaining_amount', max(0, $total - $paid));
                            }),

                        TextInput::make('paid_amount')
                            ->label(__('services_tracking.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $paid = (float) ($state ?? 0);
                                $total = (float) ($get('total_amount') ?? 0);
                                $set('remaining_amount', max(0, $total - $paid));
                            }),

                        TextInput::make('remaining_amount')
                            ->label(__('services_tracking.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make(__('services_tracking.sections.execution'))
                    ->description(__('services_tracking.sections.execution_desc'))
                    ->icon('heroicon-o-check-circle')
                    ->columns(2)
                    ->schema([
                        Select::make('service_status')
                            ->label(__('services_tracking.fields.service_status'))
                            ->options([
                                'active' => __('services_tracking.service_statuses.active'),
                                'completed' => __('services_tracking.service_statuses.completed'),
                                'inactive' => __('services_tracking.service_statuses.inactive'),
                            ])
                            ->default('active')
                            ->required()
                            ->reactive(),

                        Select::make('executed_by')
                            ->label(__('services_tracking.fields.executed_by'))
                            ->relationship('executor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? (__('employees.single')." #{$record->id}"))
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->employee?->id)
                            ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                            ->dehydrated(),

                        DatePicker::make('completion_date')
                            ->label(__('services_tracking.fields.completion_date'))
                            ->visible(fn (callable $get) => $get('service_status') === 'completed'),

                        Textarea::make('notes')
                            ->label(__('services_tracking.fields.notes'))
                            ->placeholder(__('services_tracking.placeholders.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
