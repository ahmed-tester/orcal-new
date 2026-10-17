<?php

namespace App\Filament\Resources\ContractVouchers\Schemas;

use App\Models\Customer;
use App\Models\Offer;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContractVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                // ─── 1. البيانات الأساسية ─────────────────────────────────────
                Section::make(__('contract_vouchers.sections.general_info'))
                    ->description(__('contract_vouchers.sections.general_info_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        Select::make('voucher_type')
                            ->label(__('contract_vouchers.fields.voucher_type'))
                            ->options([
                                'receipt' => __('contract_vouchers.types.receipt'),
                                'payment' => __('contract_vouchers.types.payment'),
                                'request' => __('contract_vouchers.types.request'),
                            ])
                            ->default('receipt')
                            ->required()
                            ->live(),

                        TextInput::make('voucher_number')
                            ->label(__('contract_vouchers.fields.voucher_number'))
                            ->placeholder(__('contract_vouchers.placeholders.auto'))
                            ->disabled()
                            ->dehydrated()
                            ->hiddenOn('create'),

                        CompanyFormService::makeCompanySelect('company_id', __('contract_vouchers.fields.company')),

                        DatePicker::make('voucher_date')
                            ->label(__('contract_vouchers.fields.voucher_date'))
                            ->default(now())
                            ->native(false)
                            ->required(),

                        TextInput::make('amount')
                            ->label(__('contract_vouchers.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required(),

                        Select::make('offer_id')
                            ->label(__('contract_vouchers.fields.offer'))
                            ->relationship('offer', 'offer_number')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && ($offer = Offer::with(['customer', 'company'])->find($state))) {
                                    $set('contract_number', $offer->offer_number);
                                    $set('contract_date', $offer->offer_date?->format('Y-m-d'));
                                    $set('contract_subject', $offer->subject);
                                    $set('customer_id', $offer->customer_id);
                                    $set('second_party_name', $offer->customer?->name);
                                    $set('first_party_name', $offer->company?->trade_name);
                                }
                            }),

                        Select::make('customer_id')
                            ->label(__('contract_vouchers.fields.customer'))
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && ($cust = Customer::find($state))) {
                                    $set('second_party_name', $cust->name);
                                }
                            }),

                        Select::make('status')
                            ->label(__('contract_vouchers.fields.status'))
                            ->options([
                                'draft'     => __('contract_vouchers.statuses.draft'),
                                'approved'  => __('contract_vouchers.statuses.approved'),
                                'cancelled' => __('contract_vouchers.statuses.cancelled'),
                            ])
                            ->default('draft')
                            ->required(),
                    ]),

                // ─── 2. بيانات وطريقة الدفع (للسندات المالية) ────────────────
                Section::make(__('contract_vouchers.sections.payment_details'))
                    ->description(__('contract_vouchers.sections.payment_details_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->visible(fn ($get) => in_array($get('voucher_type'), ['receipt', 'payment']))
                    ->columns(3)
                    ->schema([
                        Select::make('payment_method')
                            ->label(__('contract_vouchers.fields.payment_method'))
                            ->options([
                                'cash'          => __('contract_vouchers.payment_methods.cash'),
                                'check'         => __('contract_vouchers.payment_methods.check'),
                                'bank_transfer' => __('contract_vouchers.payment_methods.bank_transfer'),
                            ])
                            ->default('cash')
                            ->live()
                            ->columnSpan(1),

                        // حقول الدفع النقدي
                        TextInput::make('delivered_by')
                            ->label(__('contract_vouchers.fields.delivered_by'))
                            ->placeholder(__('contract_vouchers.placeholders.delivered_by'))
                            ->visible(fn ($get) => $get('payment_method') === 'cash')
                            ->columnSpan(1),

                        TextInput::make('received_by')
                            ->label(__('contract_vouchers.fields.received_by'))
                            ->placeholder(__('contract_vouchers.placeholders.received_by'))
                            ->visible(fn ($get) => $get('payment_method') === 'cash')
                            ->columnSpan(1),

                        // حقول الشيك
                        TextInput::make('check_number')
                            ->label(__('contract_vouchers.fields.check_number'))
                            ->placeholder(__('contract_vouchers.placeholders.check_number'))
                            ->visible(fn ($get) => $get('payment_method') === 'check')
                            ->columnSpan(1),

                        TextInput::make('bank_name')
                            ->label(__('contract_vouchers.fields.bank_name'))
                            ->placeholder(__('contract_vouchers.placeholders.bank_name'))
                            ->visible(fn ($get) => $get('payment_method') === 'check')
                            ->columnSpan(1),

                        DatePicker::make('due_date')
                            ->label(__('contract_vouchers.fields.due_date'))
                            ->native(false)
                            ->visible(fn ($get) => $get('payment_method') === 'check')
                            ->columnSpan(1),

                        // حقول الحوالة البنكية
                        TextInput::make('transfer_bank_name')
                            ->label(__('contract_vouchers.fields.transfer_bank_name'))
                            ->placeholder(__('contract_vouchers.placeholders.bank_name'))
                            ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                            ->columnSpan(1),

                        TextInput::make('account_number')
                            ->label(__('contract_vouchers.fields.account_number'))
                            ->placeholder(__('contract_vouchers.placeholders.account_number'))
                            ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                            ->columnSpan(1),

                        TextInput::make('reference_number')
                            ->label(__('contract_vouchers.fields.reference_number'))
                            ->placeholder(__('contract_vouchers.placeholders.reference_number'))
                            ->visible(fn ($get) => $get('payment_method') === 'bank_transfer')
                            ->columnSpan(1),
                    ]),

                // ─── 3. بيانات التعاقد وطلب الدفعة (خانات مرنة) ──────────────
                Section::make(__('contract_vouchers.sections.contract_details'))
                    ->description(__('contract_vouchers.sections.contract_details_desc'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_party_name')
                            ->label(__('contract_vouchers.fields.first_party_name'))
                            ->placeholder(__('contract_vouchers.placeholders.first_party')),

                        TextInput::make('second_party_name')
                            ->label(__('contract_vouchers.fields.second_party_name'))
                            ->placeholder(__('contract_vouchers.placeholders.second_party')),

                        TextInput::make('contract_number')
                            ->label(__('contract_vouchers.fields.contract_number'))
                            ->placeholder(__('contract_vouchers.placeholders.contract_number')),

                        DatePicker::make('contract_date')
                            ->label(__('contract_vouchers.fields.contract_date'))
                            ->native(false),

                        Textarea::make('contract_subject')
                            ->label(__('contract_vouchers.fields.contract_subject'))
                            ->placeholder(__('contract_vouchers.placeholders.contract_subject'))
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('payment_reason')
                            ->label(__('contract_vouchers.fields.payment_reason'))
                            ->placeholder(__('contract_vouchers.placeholders.payment_reason'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // ─── 4. ملاحظات إضافية ─────────────────────────────────────────
                Section::make(__('contract_vouchers.sections.notes_section'))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label(__('contract_vouchers.fields.notes'))
                            ->placeholder(__('contract_vouchers.placeholders.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
