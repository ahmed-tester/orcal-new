<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\GeneralStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Service;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
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
                // 1. البيانات الأساسية
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

                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('invoices.fields.company')),
                        \App\Services\CompanyFormService::makeBranchSelect('branch_id', __('invoices.fields.branch')),

                        \App\Services\CompanyFormService::makeInvoiceTypeSelect('invoice_type_id', __('invoices.fields.invoice_type')),

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
                            ->relationship('creator', 'name')
                            ->default(fn () => auth()->id())
                            ->searchable(),

                        Toggle::make('is_paid_by_applicant')
                            ->label(__('invoices.fields.is_paid_by_applicant'))
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),

                // 2. بنود وتفاصيل الخدمات (Repeater)
                Section::make(__('invoices.sections.items'))
                    ->description(__('invoices.sections.items_desc'))
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->label(__('invoices.sections.items'))
                            ->addActionLabel(__('invoices.fields.add_item'))
                            ->columns(6)
                            ->schema([
                                Select::make('service_id')
                                    ->label(__('invoices.fields.service'))
                                    ->options(fn (callable $get, ?string $state) => CompanyFormService::getAvailableServiceOptions($get('../../company_id'), $state))
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($service = Service::find($state)) {
                                            $set('item_name', $service->name);
                                            $set('unit_price', $service->price);
                                            $qty = (float) ($get('quantity') ?: 1);
                                            $subtotal = $qty * (float) $service->price;
                                            $taxRate = (float) ($get('tax_rate') ?: 0);
                                            $taxAmount = $subtotal * ($taxRate / 100);
                                            $set('subtotal', $subtotal);
                                            $set('tax_amount', $taxAmount);
                                            $set('total_amount', $subtotal + $taxAmount);
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
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $qty = (float) ($state ?: 0);
                                        $price = (float) ($get('unit_price') ?: 0);
                                        $subtotal = $qty * $price;
                                        $taxRate = (float) ($get('tax_rate') ?: 0);
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $set('subtotal', $subtotal);
                                        $set('tax_amount', $taxAmount);
                                        $set('total_amount', $subtotal + $taxAmount);
                                    })
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('unit_price')
                                    ->label(__('invoices.fields.unit_price'))
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $price = (float) ($state ?: 0);
                                        $qty = (float) ($get('quantity') ?: 1);
                                        $subtotal = $qty * $price;
                                        $taxRate = (float) ($get('tax_rate') ?: 0);
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $set('subtotal', $subtotal);
                                        $set('tax_amount', $taxAmount);
                                        $set('total_amount', $subtotal + $taxAmount);
                                    })
                                    ->required()
                                    ->columnSpan(1),

                                TextInput::make('tax_rate')
                                    ->label(__('invoices.fields.tax_rate'))
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $taxRate = (float) ($state ?: 0);
                                        $subtotal = (float) ($get('subtotal') ?: 0);
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $set('tax_amount', $taxAmount);
                                        $set('total_amount', $subtotal + $taxAmount);
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

                // 3. الإجماليات وحالة السداد
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
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
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
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $paid = (float) ($state ?: 0);
                                $total = (float) ($get('total_amount') ?: 0);
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

                        TextInput::make('remaining_amount')
                            ->label(__('invoices.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->readOnly(),

                        Select::make('payment_status')
                            ->label(__('invoices.fields.payment_status'))
                            ->options(PaymentStatus::class)
                            ->default(PaymentStatus::UNPAID)
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('invoices.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
