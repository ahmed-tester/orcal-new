<?php

namespace App\Filament\Resources\ViolationTransactions\Schemas;

use App\Enums\ViolationTransactionType;
use App\Models\ViolationTransaction;
use App\Services\CompanyFormService;
use App\Services\ViolationTransactionFormService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class ViolationTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('violations.transactions.sections.transaction_info'))
                    ->description(__('violations.transactions.sections.transaction_info_desc'))
                    ->columns(3)
                    ->schema([
                        CompanyFormService::makeCompanySelect('company_id', __('violations.transactions.fields.company'), true)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => ViolationTransactionFormService::handleCompanyChange($set)),

                        DatePicker::make('transaction_date')
                            ->label(__('violations.transactions.fields.transaction_date'))
                            ->default(now())
                            ->required(),

                        Select::make('transaction_type')
                            ->label(__('violations.transactions.fields.transaction_type'))
                            ->options(ViolationTransactionType::class)
                            ->default(ViolationTransactionType::INCOMING->value)
                            ->live()
                            ->required(),

                        ToggleButtons::make('link_type')
                            ->label(__('violations.transactions.fields.link_type'))
                            ->options(fn (callable $get) => ViolationTransactionFormService::getLinkTypeOptions($get('transaction_type')))
                            ->icons([
                                ViolationTransactionFormService::LINK_SINGLE => 'heroicon-m-document-text',
                                ViolationTransactionFormService::LINK_MULTIPLE => 'heroicon-m-queue-list',
                                ViolationTransactionFormService::LINK_UNLINKED => 'heroicon-m-banknotes',
                            ])
                            ->colors(fn (callable $get) => [
                                ViolationTransactionFormService::LINK_SINGLE => 'primary',
                                ViolationTransactionFormService::LINK_MULTIPLE => 'info',
                                ViolationTransactionFormService::LINK_UNLINKED => ViolationTransactionFormService::isOutgoingType($get('transaction_type')) ? 'warning' : 'success',
                            ])
                            ->default(ViolationTransactionFormService::LINK_SINGLE)
                            ->inline()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(fn ($state, callable $set) => ViolationTransactionFormService::handleLinkTypeChange($state, $set)
                            ),

                        Select::make('violation_id')
                            ->label(__('violations.transactions.fields.violation'))
                            ->placeholder(fn (callable $get) => filled($get('company_id')) ? null : __('violations.transactions.fields.select_company_first'))
                            ->options(fn (callable $get, ?ViolationTransaction $record) => ViolationTransactionFormService::getSingleViolationOptions($get('company_id'), $record?->violation_id))
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => ($get('link_type') ?? ViolationTransactionFormService::LINK_SINGLE) === ViolationTransactionFormService::LINK_SINGLE)
                            ->required(fn (callable $get) => ($get('link_type') ?? ViolationTransactionFormService::LINK_SINGLE) === ViolationTransactionFormService::LINK_SINGLE)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => ViolationTransactionFormService::handleSingleViolationUpdated($state, $set)
                            )
                            ->columnSpan(2),

                        Placeholder::make('select_company_notice')
                            ->hiddenLabel()
                            ->content(new HtmlString(
                                '<div style="padding: 10px 14px; border-radius: 8px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e4ed8; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;">'.
                                    '<span>ℹ️</span> '.e(__('violations.transactions.fields.select_company_first')).
                                '</div>'
                            ))
                            ->visible(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_MULTIPLE && blank($get('company_id')))
                            ->columnSpanFull(),

                        CheckboxList::make('metadata.selected_violation_ids')
                            ->label(__('violations.transactions.fields.selected_violations'))
                            ->options(fn (callable $get, ?ViolationTransaction $record) => ViolationTransactionFormService::getMultipleViolationOptions($get('company_id'), $record?->metadata['selected_violation_ids'] ?? []))
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->columnSpanFull()
                            ->extraAttributes([
                                'style' => 'max-height: 320px; overflow-y: auto; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc;',
                            ])
                            ->visible(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_MULTIPLE && filled($get('company_id')))
                            ->required(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_MULTIPLE)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, ?ViolationTransaction $record) => ViolationTransactionFormService::handleMultipleViolationsUpdated($state, $set, $record)
                            ),

                        Placeholder::make('unlinked_notice')
                            ->hiddenLabel()
                            ->content(fn (callable $get) => ViolationTransactionFormService::getUnlinkedNoticeHtml($get('transaction_type')))
                            ->visible(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_UNLINKED)
                            ->columnSpanFull(),

                        TextInput::make('amount')
                            ->label(__('violations.transactions.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->required()
                            ->helperText(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_MULTIPLE ? __('violations.transactions.fields.multiple_amount_hint') : null)
                            ->readonly(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_MULTIPLE)
                            ->extraInputAttributes(fn (callable $get) => $get('link_type') === ViolationTransactionFormService::LINK_MULTIPLE ? ['style' => 'background-color: #f8fafc; font-weight: bold;'] : []),

                        TextInput::make('voucher_number')
                            ->label(__('violations.transactions.fields.voucher_number'))
                            ->placeholder(__('violations.transactions.placeholders.auto_voucher'))
                            ->disabled(),

                        Textarea::make('description')
                            ->label(__('violations.transactions.fields.description'))
                            ->placeholder(__('violations.transactions.placeholders.description'))
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }
}
