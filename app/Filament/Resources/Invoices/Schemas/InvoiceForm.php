<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\FinancialPaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Service;
use App\Services\CompanyFormService;
use App\Services\FinancialReceiptService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('invoices.sections.invoice_info'))
                    ->description(__('invoices.sections.invoice_info_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label(__('invoices.fields.invoice_number'))
                            ->placeholder('توليد تلقائي (Auto-generated)')
                            ->disabled()
                            ->dehydrated(),

                        CompanyFormService::makeCompanySelect('company_id', __('invoices.fields.company')),
                        CompanyFormService::makeBranchSelect('branch_id', __('invoices.fields.branch'))->live(),

                        CompanyFormService::makeInvoiceTypeSelect('invoice_type_id', __('invoices.fields.invoice_type')),

                        Select::make('customer_id')
                            ->label(__('invoices.fields.customer'))
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('employee_id')
                            ->label(__('invoices.fields.employee'))
                            ->options(fn (callable $get) => Employee::with('user')
                                ->when($get('company_id'), fn ($q, $cid) => $q->where('company_id', $cid))
                                ->get()
                                ->mapWithKeys(fn ($emp) => [$emp->id => $emp->user?->name ?? "موظف #{$emp->id}"])
                            )
                            ->searchable()
                            ->preload(),

                        DatePicker::make('issue_date')
                            ->label(__('invoices.fields.issue_date'))
                            ->default(now())
                            ->native(false)
                            ->required(),

                        DatePicker::make('due_date')
                            ->label(__('invoices.fields.due_date'))
                            ->native(false),

                        Select::make('created_by')
                            ->label(__('invoices.fields.created_by'))
                            ->options(function (callable $get, ?Invoice $record): array {
                                $companyId = $get('company_id');
                                if (! $companyId) {
                                    return [];
                                }

                                $options = Employee::query()
                                    ->with('user')
                                    ->where('company_id', $companyId)
                                    ->when($get('branch_id'), fn ($q, $bid) => $q->where('branch_id', $bid))
                                    ->whereNotNull('user_id')
                                    ->get()
                                    ->mapWithKeys(fn (Employee $emp) => [$emp->user_id => $emp->user?->name ?? ('#'.$emp->user_id)])
                                    ->all();

                                if ($record?->created_by && ! array_key_exists($record->created_by, $options) && $record->creator) {
                                    $options[$record->created_by] = $record->creator->name;
                                }

                                return $options;
                            })
                            ->default(fn () => auth()->user()?->employee ? auth()->id() : null)
                            ->searchable()
                            ->preload(),

                        Hidden::make('creation_request_key')
                            ->default(fn () => FinancialReceiptService::newRequestKey())
                            ->dehydrated(false),

                        Hidden::make('creation_received_amount_manually_edited')
                            ->default(false)
                            ->dehydrated(false),

                        Toggle::make('is_paid_by_applicant')
                            ->label(__('invoices.fields.is_paid_by_applicant'))
                            ->default(false)
                            ->live()
                            ->inline(false)
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->afterStateUpdated(function ($state, callable $set, callable $get, string $operation): void {
                                $set('creation_received_amount_manually_edited', false);

                                if ($state) {
                                    $total = round((float) ($get('total_amount') ?: 0), 2);
                                    $set('creation_received_amount', $total > 0 ? $total : null);
                                } else {
                                    $set('creation_received_amount', null);
                                }

                                if ($operation === 'create') {
                                    static::syncCreationPaymentPreview($set, $get);
                                }
                            }),

                        TextInput::make('creation_received_amount')
                            ->label(__('invoices.fields.received_amount'))
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix(__('services_tracking.currency_sar'))
                            ->live(debounce: 350)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, string $operation): void {
                                $set('creation_received_amount_manually_edited', filled($state));

                                if ($operation === 'create') {
                                    static::syncCreationPaymentPreview($set, $get);
                                }
                            })
                            ->visible(fn (callable $get, string $operation): bool => $operation === 'create' && (bool) $get('is_paid_by_applicant'))
                            ->required(fn (callable $get, string $operation): bool => $operation === 'create' && (bool) $get('is_paid_by_applicant'))
                            ->dehydrated(false),

                        Select::make('creation_payment_method')
                            ->label(__('financial.fields.payment_method'))
                            ->options(FinancialPaymentMethod::class)
                            ->default(FinancialPaymentMethod::CASH->value)
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, string $operation): void {
                                if ($operation === 'create') {
                                    static::syncCreationPaymentPreview($set, $get);
                                }
                            })
                            ->visible(fn (callable $get, string $operation): bool => $operation === 'create' && (bool) $get('is_paid_by_applicant'))
                            ->required(fn (callable $get, string $operation): bool => $operation === 'create' && (bool) $get('is_paid_by_applicant'))
                            ->dehydrated(false),
                    ]),

                Section::make(__('invoices.sections.items'))
                    ->description(__('invoices.sections.items_desc'))
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label(__('invoices.sections.items'))
                            ->addActionLabel(__('invoices.fields.add_item'))
                            ->disabled(fn ($record): bool => $record !== null && ($record->isServiceMirror() || $record->hasFinancialImpact()))
                            ->live()
                            ->afterStateUpdated(function (?array $state, callable $set, callable $get): void {
                                static::syncInvoiceTotals($state ?? [], $set, $get);
                            })
                            ->columns(6)
                            ->schema([
                                Select::make('service_id')
                                    ->label(__('invoices.fields.service'))
                                    ->options(fn (callable $get, ?string $state) => CompanyFormService::getAvailableServiceOptions($get('../../company_id'), $state))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        if ($service = Service::find($state)) {
                                            $set('item_name', $service->name);
                                            $set('unit_price', $service->price);
                                            static::recalculateItemRow($set, $get, unitPrice: (float) $service->price);
                                        }
                                    })
                                    ->columnSpan(2),

                                TextInput::make('item_name')
                                    ->label(__('invoices.fields.item_name'))
                                    ->required()
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label(__('invoices.fields.quantity'))
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->live(debounce: 350)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        static::recalculateItemRow($set, $get, quantity: (float) ($state ?: 0));
                                    })
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label(__('invoices.fields.unit_price'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live(debounce: 350)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        static::recalculateItemRow($set, $get, unitPrice: (float) ($state ?: 0));
                                    })
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('tax_rate')
                                    ->label(__('invoices.fields.tax_rate'))
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live(debounce: 350)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                        static::recalculateItemRow($set, $get, taxRate: (float) ($state ?: 0));
                                    })
                                    ->columnSpan(1),

                                TextInput::make('tax_amount')
                                    ->label(__('invoices.fields.tax_amount'))
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly()
                                    ->columnSpan(1),

                                TextInput::make('total_amount')
                                    ->label(__('invoices.fields.item_total'))
                                    ->numeric()
                                    ->default(0)
                                    ->readOnly()
                                    ->columnSpan(1),

                                TextInput::make('notes')
                                    ->label(__('invoices.fields.notes'))
                                    ->placeholder('ملاحظة على البند...')
                                    ->columnSpan(3),
                            ])
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('invoices.sections.payment_info'))
                    ->description(__('invoices.sections.payment_info_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(4)
                    ->schema([
                        TextInput::make('total_amount')
                            ->label(__('invoices.fields.total_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get, string $operation): void {
                                if ($operation === 'create') {
                                    static::syncCreationPaymentPreview($set, $get);

                                    return;
                                }

                                $total = (float) ($state ?: 0);
                                $paid = (float) ($get('paid_amount') ?: 0);
                                $remaining = max(0, $total - $paid);
                                $set('remaining_amount', $remaining);

                                if ($paid >= $total && $total > 0) {
                                    $set('payment_status', PaymentStatus::PAID);
                                } elseif ($paid > 0) {
                                    $set('payment_status', PaymentStatus::PARTIALLY_PAID);
                                } else {
                                    $set('payment_status', PaymentStatus::UNPAID);
                                }
                            })
                            ->required(),

                        TextInput::make('paid_amount')
                            ->label(__('invoices.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(__('invoices.notices.receipt_derived')),

                        TextInput::make('remaining_amount')
                            ->label(__('invoices.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false),

                        Select::make('payment_status')
                            ->label(__('invoices.fields.payment_status'))
                            ->options(PaymentStatus::class)
                            ->default(PaymentStatus::UNPAID)
                            ->disabled()
                            ->dehydrated(false),

                        Textarea::make('notes')
                            ->label(__('invoices.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function recalculateItemRow(
        callable $set,
        callable $get,
        ?float $quantity = null,
        ?float $unitPrice = null,
        ?float $taxRate = null,
    ): void {
        $qty = $quantity ?? (float) ($get('quantity') ?: 0);
        $price = $unitPrice ?? (float) ($get('unit_price') ?: 0);
        $rate = $taxRate ?? (float) ($get('tax_rate') ?: 0);

        $subtotal = round($qty * $price, 2);
        $taxAmount = round($subtotal * ($rate / 100), 2);
        $itemTotal = round($subtotal + $taxAmount, 2);

        $set('subtotal', $subtotal);
        $set('tax_amount', $taxAmount);
        $set('total_amount', $itemTotal);

        static::syncInvoiceTotals($get('../../items') ?? [], $set, $get, '../../');
    }

    /**
     * @param  array<int|string, array<string, mixed>>  $items
     */
    private static function syncInvoiceTotals(
        array $items,
        callable $set,
        callable $get,
        string $prefix = '',
    ): void {
        $itemsTotal = round(collect($items)->sum(fn ($item) => (float) ($item['total_amount'] ?? 0)), 2);

        $set($prefix.'total_amount', $itemsTotal);

        if ($get($prefix.'is_paid_by_applicant') && ! $get($prefix.'creation_received_amount_manually_edited')) {
            $set($prefix.'creation_received_amount', $itemsTotal > 0 ? $itemsTotal : null);
        }

        if ($prefix !== '' || ! $get('id')) {
            static::syncCreationPaymentPreview($set, $get, $prefix);

            return;
        }

        $paid = (float) ($get($prefix.'paid_amount') ?: 0);
        $set($prefix.'remaining_amount', max(0, $itemsTotal - $paid));
    }

    private static function syncCreationPaymentPreview(callable $set, callable $get, string $prefix = ''): void
    {
        $total = round(max(0, (float) ($get($prefix.'total_amount') ?: 0)), 2);
        $isPaid = (bool) $get($prefix.'is_paid_by_applicant');
        $rawMethod = $get($prefix.'creation_payment_method');
        $method = $rawMethod instanceof FinancialPaymentMethod ? $rawMethod->value : (string) $rawMethod;
        $isCash = $method === FinancialPaymentMethod::CASH->value;

        $paid = ($isPaid && $isCash)
            ? round(min($total, max(0, (float) ($get($prefix.'creation_received_amount') ?: 0))), 2)
            : 0.0;

        $remaining = round(max(0, $total - $paid), 2);
        $status = match (true) {
            $paid >= $total && $total > 0 => PaymentStatus::PAID,
            $paid > 0 => PaymentStatus::PARTIALLY_PAID,
            default => PaymentStatus::UNPAID,
        };

        $set($prefix.'paid_amount', $paid);
        $set($prefix.'remaining_amount', $remaining);
        $set($prefix.'payment_status', $status);
    }
}
