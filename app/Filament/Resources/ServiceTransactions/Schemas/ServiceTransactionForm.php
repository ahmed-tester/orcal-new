<?php

namespace App\Filament\Resources\ServiceTransactions\Schemas;

use App\Enums\ApprovalStatus;
use App\Enums\FinancialPaymentMethod;
use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use App\Models\Service;
use App\Models\ServiceTransaction;
use App\Services\CashHandoverService;
use App\Services\CompanyFormService;
use App\Services\FinancialReceiptService;
use App\Services\PhoneService;
use App\Services\ServiceFinancialWorkflowService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ServiceTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $wizard = static::getWizard();
        $record = $schema->getRecord();
        $isEdit = $record instanceof ServiceTransaction && $record->exists;

        if (! $isEdit) {
            return $schema
                ->columns(1)
                ->components([
                    $wizard,
                ]);
        }

        return $schema
            ->columns(1)
            ->components([
                Tabs::make('transaction_management_tabs')
                    ->tabs([
                        Tab::make('operational_dashboard')
                            ->label(__('services_tracking.tabs.operational_dashboard'))
                            ->icon('heroicon-o-presentation-chart-line')
                            ->schema([
                                View::make('filament.resources.service-transactions.operational-dashboard'),
                            ]),

                        Tab::make('edit_fields')
                            ->label(__('services_tracking.tabs.edit_data'))
                            ->visible(fn () => $schema->getOperation() !== 'view')
                            ->icon('heroicon-o-pencil-square')
                            ->schema([
                                $wizard,
                            ]),
                    ]),
            ]);
    }

    public static function getWizard(): Wizard
    {
        $isCashReceipt = function (callable $get): bool {
            $method = $get('receipt_payment_method');
            if ($method instanceof FinancialPaymentMethod) {
                return $method->isCash();
            }

            return $method === FinancialPaymentMethod::CASH->value || $method === 'cash';
        };

        $clientDue = fn (callable $get): float => ServiceFinancialWorkflowService::resolveShares(
            $get('metadata') ?? [],
            (float) $get('total_amount'),
        )[0];

        return Wizard::make([
            Step::make('service_and_party_step')
                ->label(__('services_tracking.wizard.step_1_title'))
                ->description(__('services_tracking.wizard.step_1_desc'))
                ->icon('heroicon-o-identification')
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

                            CompanyFormService::makeEmployeeSelect(
                                label: __('services_tracking.fields.employee'),
                                required: false,
                                companyFieldName: 'company_id',
                            )
                                ->visible(fn (callable $get) => $get('service_target') === 'employee')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $emp = Employee::find($state);
                                        if ($emp) {
                                            if ($emp->company_id) {
                                                $set('company_id', $emp->company_id);
                                            }
                                            $set('beneficiary_name', $emp->user?->name ?? $emp->name);
                                        }
                                    }
                                }),

                            TextInput::make('beneficiary_name')
                                ->label(__('services_tracking.fields.beneficiary_name'))
                                ->placeholder(__('services_tracking.placeholders.beneficiary_name'))
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('services_tracking.sections.external_service'))
                        ->description(__('services_tracking.sections.external_service_desc'))
                        ->icon('heroicon-o-identification')
                        ->columns(2)
                        ->visible(fn (callable $get) => $get('scope_type') === 'external')
                        ->schema([
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

                    Section::make(__('services_tracking.sections.service'))
                        ->description(__('services_tracking.sections.service_desc'))
                        ->icon('heroicon-o-squares-2x2')
                        ->columns(2)
                        ->schema([
                            Select::make('service_id')
                                ->label(__('services_tracking.fields.service'))
                                ->relationship('service', 'name', function ($query, callable $get, ?Model $record) {
                                    $target = $get('service_target') ?? 'company';

                                    return $query->where(function ($q) use ($target, $record) {
                                        $q->whereIn('target_type', [$target, 'general'])->where('status', 'active');
                                        if ($record && ! empty($record->service_id)) {
                                            $q->orWhere('id', $record->service_id);
                                        }
                                    });
                                })
                                ->getOptionLabelFromRecordUsing(fn (Service $record) => "{$record->name} • ".number_format($record->price, 2).' '.__('services_tracking.currency_sar'))
                                ->getOptionLabelUsing(function ($value): ?string {
                                    $svc = Service::find($value);

                                    return $svc ? "{$svc->name} • ".number_format($svc->price, 2).' '.__('services_tracking.currency_sar') : null;
                                })
                                ->searchable(['name'])
                                ->preload()
                                ->visible(fn (callable $get) => ($get('scope_type') ?? 'internal') === 'internal')
                                ->required(fn (callable $get) => ($get('scope_type') ?? 'internal') === 'internal')
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get, ?Model $record) {
                                    if ($state) {
                                        $svc = Service::find($state);
                                        if ($svc) {
                                            $price = (float) ($record?->exists ? $record->total_amount : $svc->price);

                                            $set('total_amount', $price);
                                            $costBearer = $get('metadata.cost_bearer') ?? 'client_paid';
                                            $clientShare = match ($costBearer) {
                                                'office_liability' => 0,
                                                'shared' => min($price, max(0, (float) ($get('metadata.client_share') ?? round($price / 2, 2)))),
                                                default => $price,
                                            };
                                            $set('metadata.client_share', $clientShare);
                                            $set('metadata.office_share', max(0, $price - $clientShare));
                                            $set('metadata.gov_fee_expected', (float) ($svc->default_gov_fee ?? 0));
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
                                                ->prefix(__('services_tracking.currency_sar'))
                                                ->default(0)
                                                ->required(),

                                            TextInput::make('default_gov_fee')
                                                ->label(__('services.estimated_gov_fee'))
                                                ->numeric()
                                                ->prefix(__('services_tracking.currency_sar'))
                                                ->default(0),

                                            Textarea::make('notes')
                                                ->label(__('services_tracking.quick_add.notes'))
                                                ->rows(2)
                                                ->columnSpanFull(),
                                        ]),
                                ]),

                            TextInput::make('service_name')
                                ->label(__('services_tracking.fields.service_name'))
                                ->placeholder(__('services_tracking.placeholders.service_name'))
                                ->visible(fn (callable $get) => $get('scope_type') === 'external')
                                ->required(fn (callable $get) => $get('scope_type') === 'external')
                                ->maxLength(255),

                            TextInput::make('service_type')
                                ->label(__('services_tracking.fields.service_type'))
                                ->placeholder(__('services_tracking.placeholders.service_type'))
                                ->visible(fn (callable $get) => $get('scope_type') === 'external')
                                ->maxLength(100),

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
                                ->dehydrated()
                                ->columnSpanFull(),
                        ]),
                ]),

            Step::make('financial_and_proofs_step')
                ->label(__('services_tracking.wizard.step_2_title'))
                ->description(__('services_tracking.wizard.step_2_desc'))
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Hidden::make('submission_key')
                        ->default(fn () => (string) Str::uuid())
                        ->visible(fn ($record) => $record === null),

                    Section::make(__('services_tracking.sections.financial'))
                        ->description(__('services_tracking.sections.financial_desc'))
                        ->columns(3)
                        ->schema([
                            ToggleButtons::make('metadata.cost_bearer')
                                ->label(__('services_tracking.cost_bearers.label'))
                                ->options([
                                    'client_paid' => __('services_tracking.cost_bearers.client_paid'),
                                    'client_debt' => __('services_tracking.cost_bearers.client_debt'),
                                    'office_liability' => __('services_tracking.cost_bearers.office_liability'),
                                    'shared' => __('services_tracking.cost_bearers.shared'),
                                ])
                                ->icons([
                                    'client_paid' => 'heroicon-m-banknotes',
                                    'client_debt' => 'heroicon-m-clock',
                                    'office_liability' => 'heroicon-m-building-office',
                                    'shared' => 'heroicon-m-user-group',
                                ])
                                ->colors([
                                    'client_paid' => 'success',
                                    'client_debt' => 'warning',
                                    'office_liability' => 'danger',
                                    'shared' => 'info',
                                ])
                                ->default('client_paid')
                                ->inline()
                                ->grouped()
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $total = (float) ($get('total_amount') ?? 0);
                                    if ($state === 'office_liability') {
                                        $set('metadata.client_share', 0);
                                        $set('metadata.office_share', $total);
                                    } elseif ($state === 'shared') {
                                        $half = round($total / 2, 2);
                                        $set('metadata.client_share', $half);
                                        $set('metadata.office_share', max(0, $total - $half));
                                    } else {
                                        $set('metadata.client_share', $total);
                                        $set('metadata.office_share', 0);
                                    }
                                })
                                ->columnSpanFull(),

                            Grid::make(2)
                                ->visible(fn (callable $get) => ($get('metadata.cost_bearer') ?? '') === 'shared')
                                ->schema([
                                    TextInput::make('metadata.client_share')
                                        ->label(__('services_tracking.cost_bearers.client_share'))
                                        ->numeric()
                                        ->prefix(__('services_tracking.currency_sar'))
                                        ->default(0)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $total = (float) ($get('total_amount') ?? 0);
                                            $clientShare = (float) ($state ?? 0);
                                            $officeShare = max(0, $total - $clientShare);
                                            $set('metadata.office_share', $officeShare);
                                        }),

                                    TextInput::make('metadata.office_share')
                                        ->label(__('services_tracking.cost_bearers.office_share'))
                                        ->numeric()
                                        ->prefix(__('services_tracking.currency_sar'))
                                        ->default(0)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $total = (float) ($get('total_amount') ?? 0);
                                            $officeShare = (float) ($state ?? 0);
                                            $clientShare = max(0, $total - $officeShare);
                                            $set('metadata.client_share', $clientShare);
                                        }),
                                ])
                                ->columnSpanFull(),

                            TextInput::make('total_amount')
                                ->label(__('services_tracking.fields.total_amount'))
                                ->numeric()
                                ->prefix(__('services_tracking.currency_sar'))
                                ->default(0)
                                ->disabled(fn (callable $get, ?Model $record): bool => ($record?->exists ?? false) || $get('scope_type') !== 'external')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    $total = (float) ($state ?? 0);
                                    $costBearer = $get('metadata.cost_bearer') ?? 'client_paid';
                                    if ($costBearer === 'shared') {
                                        $clientShare = (float) ($get('metadata.client_share') ?? 0);
                                        if ($clientShare > $total || $clientShare <= 0) {
                                            $clientShare = round($total / 2, 2);
                                        }
                                        $officeShare = max(0, $total - $clientShare);
                                        $set('metadata.client_share', $clientShare);
                                        $set('metadata.office_share', $officeShare);
                                    } elseif ($costBearer === 'office_liability') {
                                        $set('metadata.client_share', 0);
                                        $set('metadata.office_share', $total);
                                    } else {
                                        $set('metadata.client_share', $total);
                                        $set('metadata.office_share', 0);
                                    }
                                }),

                            TextInput::make('paid_amount')
                                ->label(__('services_tracking.fields.paid_amount'))
                                ->numeric()
                                ->prefix(__('services_tracking.currency_sar'))
                                ->default(0)
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText(__('financial.summary.description')),

                            TextInput::make('remaining_amount')
                                ->label(__('services_tracking.fields.remaining_amount'))
                                ->numeric()
                                ->prefix(__('services_tracking.currency_sar'))
                                ->disabled()
                                ->dehydrated(false),
                        ]),

                    Section::make(__('services_tracking.sections.receipt_at_creation'))
                        ->description(__('services_tracking.sections.receipt_at_creation_desc'))
                        ->icon('heroicon-o-receipt-percent')
                        ->columns(2)
                        ->visible(fn ($record) => $record === null && (auth()->user()?->can('record_service_receipt') ?? false))
                        ->schema([
                            Hidden::make('receipt_request_key')
                                ->default(fn () => FinancialReceiptService::newRequestKey()),

                            Hidden::make('handover_request_key')
                                ->default(fn () => CashHandoverService::newRequestKey()),

                            Toggle::make('receipt_received')
                                ->label(__('financial.creation_receipt.received_from_client'))
                                ->default(false)
                                ->live()
                                ->afterStateUpdated(function (bool $state, callable $get, callable $set) use ($clientDue) {
                                    if ($state && blank($get('receipt_amount'))) {
                                        $due = $clientDue($get);
                                        $set('receipt_amount', $due > 0 ? $due : null);
                                    }
                                })
                                ->columnSpanFull(),

                            TextInput::make('receipt_amount')
                                ->label(__('financial.fields.amount'))
                                ->numeric()
                                ->minValue(0.01)
                                ->live(debounce: 400)
                                ->prefix(__('services_tracking.currency_sar'))
                                ->helperText(function (callable $get) use ($clientDue): ?string {
                                    $surplus = round((float) $get('receipt_amount') - $clientDue($get), 2);

                                    return $surplus > 0
                                        ? __('financial.creation_receipt.surplus_hint', ['amount' => number_format($surplus, 2)])
                                        : null;
                                })
                                ->required(fn (callable $get) => (bool) $get('receipt_received'))
                                ->visible(fn (callable $get) => (bool) $get('receipt_received')),

                            Select::make('receipt_payment_method')
                                ->label(__('financial.fields.payment_method'))
                                ->options(FinancialPaymentMethod::class)
                                ->default(FinancialPaymentMethod::CASH->value)
                                ->required(fn (callable $get) => (bool) $get('receipt_received'))
                                ->live()
                                ->visible(fn (callable $get) => (bool) $get('receipt_received')),

                            TextInput::make('receipt_reference_number')
                                ->label(__('financial.fields.reference_number'))
                                ->maxLength(100)
                                ->visible(fn (callable $get) => (bool) $get('receipt_received') && ! $isCashReceipt($get)),

                            Select::make('receipt_destination_type')
                                ->label(__('financial.fields.destination_type'))
                                ->options(__('financial.destination_types'))
                                ->visible(fn (callable $get) => (bool) $get('receipt_received') && ! $isCashReceipt($get)),

                            TextInput::make('receipt_destination_reference')
                                ->label(__('financial.fields.destination_reference'))
                                ->maxLength(100)
                                ->visible(fn (callable $get) => (bool) $get('receipt_received') && ! $isCashReceipt($get)),

                            FileUpload::make('receipt_proof_path')
                                ->label(__('financial.fields.proof'))
                                ->disk('public')
                                ->directory('financial-receipts')
                                ->openable()
                                ->downloadable()
                                ->maxSize(10240)
                                ->visible(fn (callable $get) => (bool) $get('receipt_received') && ! $isCashReceipt($get)),

                            DateTimePicker::make('receipt_received_at')
                                ->label(__('financial.fields.received_at'))
                                ->default(now())
                                ->visible(fn (callable $get) => (bool) $get('receipt_received')),

                            Toggle::make('receipt_request_handover')
                                ->label(__('financial.creation_receipt.request_handover'))
                                ->helperText(__('financial.creation_receipt.request_handover_hint'))
                                ->default(false)
                                ->columnSpanFull()
                                ->visible(fn (callable $get) => (bool) $get('receipt_received')
                                    && $isCashReceipt($get)
                                    && (auth()->user()?->can('request_cash_handover') ?? false)),
                        ]),

                    Section::make(__('services_tracking.sections.execution'))
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

                            CompanyFormService::makeEmployeeSelect(
                                name: 'executed_by',
                                label: __('services_tracking.fields.executed_by'),
                                required: false,
                                relationshipName: 'executor',
                            )
                                ->required(fn (callable $get) => (bool) $get('receipt_received') && $isCashReceipt($get))
                                ->default(fn () => auth()->user()?->employee?->id)
                                ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                                ->dehydrated(),

                            DatePicker::make('completion_date')
                                ->label(__('services_tracking.fields.completion_date'))
                                ->visible(fn (callable $get) => $get('service_status') === 'completed')
                                ->columnSpanFull(),
                        ]),

                    Section::make(__('services_tracking.settlement.section_title'))
                        ->description(__('services_tracking.settlement.section_desc'))
                        ->icon('heroicon-o-document-check')
                        ->columns(2)
                        ->schema([
                            FileUpload::make('metadata.gov_payment_receipt')
                                ->label(__('services_tracking.settlement.gov_receipt'))
                                ->disk('public')
                                ->directory('service-receipts')
                                ->openable()
                                ->downloadable()
                                ->maxSize(10240),

                            FileUpload::make('metadata.bank_deposit_receipt')
                                ->label(__('services_tracking.settlement.bank_deposit_receipt'))
                                ->disk('public')
                                ->directory('service-receipts')
                                ->openable()
                                ->downloadable()
                                ->maxSize(10240),
                        ]),

                    Section::make(__('services_tracking.fields.notes'))
                        ->schema([
                            Textarea::make('notes')
                                ->label(__('services_tracking.fields.notes'))
                                ->placeholder(__('services_tracking.placeholders.notes'))
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ]),
        ])
            ->skippable()
            ->columnSpanFull();
    }
}
