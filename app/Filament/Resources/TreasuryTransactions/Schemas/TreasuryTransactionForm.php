<?php

namespace App\Filament\Resources\TreasuryTransactions\Schemas;

use App\Enums\PaymentItemCategory;
use App\Enums\TreasuryTransactionType;
use App\Filament\Components\OrgSelect;
use App\Services\CompanyFormService;
use App\Services\TreasuryBeneficiaryService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TreasuryTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.treasury_transactions.sections.transaction_info'))
                    ->description(__('finance.treasury_transactions.sections.transaction_info_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('transaction_number')
                            ->label(__('finance.treasury_transactions.fields.transaction_number'))
                            ->placeholder(__('finance.treasury_transactions.placeholders.auto_trans_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        TextInput::make('voucher_number')
                            ->label(__('finance.treasury_transactions.fields.voucher_number'))
                            ->placeholder(__('finance.treasury_transactions.placeholders.auto_voucher_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('transaction_date')
                            ->label(__('finance.treasury_transactions.fields.transaction_date'))
                            ->default(now())
                            ->required(),

                        OrgSelect::company('company_id', __('finance.treasury_transactions.fields.company')),

                        Select::make('transaction_type')
                            ->label(__('finance.treasury_transactions.fields.transaction_type'))
                            ->options(TreasuryTransactionType::class)
                            ->default(TreasuryTransactionType::INCOMING->value)
                            ->required(),

                        Select::make('payment_collection_item_id')
                            ->label(__('finance.treasury_transactions.fields.payment_collection_item'))
                            ->relationship('paymentCollectionItem', 'sub_item')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $mainLabel = $record->main_item instanceof PaymentItemCategory
                                    ? $record->main_item->getLabel()
                                    : ($record->main_item ?? '');

                                return "{$mainLabel} — {$record->sub_item}";
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label(__('finance.treasury_transactions.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0)),

                        Select::make('beneficiary_type')
                            ->label(__('finance.treasury_transactions.fields.beneficiary_type'))
                            ->options(__('finance.treasury_transactions.beneficiary_types'))
                            ->default('other')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function (callable $set) {
                                $set('employee_id', null);
                                $set('recipient_or_payer', null);
                            }),

                        TreasuryBeneficiaryService::makeEmployeeSelect(),

                        TextInput::make('recipient_or_payer')
                            ->label(__('finance.treasury_transactions.fields.recipient_or_payer'))
                            ->placeholder(__('finance.treasury_transactions.placeholders.recipient_or_payer'))
                            ->required()
                            ->readOnly(fn (callable $get) => in_array($get('beneficiary_type'), ['saudi_employee', 'foreign_employee']))
                            ->helperText(fn (callable $get) => in_array($get('beneficiary_type'), ['saudi_employee', 'foreign_employee']) ? __('finance.treasury_transactions.helpers.selected_employee_name') : null),

                        CompanyFormService::makeEmployeeSelect(
                            name: 'executed_by',
                            label: __('finance.treasury_transactions.fields.executed_by'),
                            required: false,
                            relationshipName: 'executor',
                        )
                            ->default(fn () => auth()->user()?->employee?->id)
                            ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                            ->dehydrated(),

                        TextInput::make('balance_after')
                            ->label(__('finance.treasury_transactions.fields.balance_after'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Textarea::make('description')
                            ->label(__('finance.treasury_transactions.fields.description'))
                            ->placeholder(__('finance.treasury_transactions.placeholders.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
