<?php

namespace App\Filament\Resources\DebtCollections\Schemas;

use App\Models\Debt;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DebtCollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('debts.collections.sections.collection_info'))
                    ->description(__('debts.collections.sections.collection_info_desc'))
                    ->icon('heroicon-o-receipt-percent')
                    ->columns(2)
                    ->schema([
                        TextInput::make('voucher_number')
                            ->label(__('debts.collections.fields.voucher_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('collection_date')
                            ->label(__('debts.collections.fields.collection_date'))
                            ->default(now())
                            ->required(),

                        Select::make('debt_id')
                            ->label(__('debts.collections.fields.debt'))
                            ->relationship('debt', 'id', fn ($query) => $query->whereIn('status', ['pending', 'partial', 'overdue']))
                            ->getOptionLabelFromRecordUsing(fn (Debt $record) => "{$record->debt_number} — {$record->beneficiary_name} • " . __('debts.fields.remaining_amount') . ': ' . number_format($record->remaining_amount, 2) . ' SAR')
                            ->searchable(['debt_number', 'external_name'])
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $debt = Debt::find($state);
                                    if ($debt) {
                                        $set('amount', $debt->remaining_amount);
                                        $set('company_id', $debt->company_id);
                                        $set('employee_id', $debt->employee_id);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('amount')
                            ->label(__('debts.collections.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        Select::make('payment_method')
                            ->label(__('debts.collections.fields.payment_method'))
                            ->options([
                                'cash'          => __('debts.collections.payment_methods.cash'),
                                'bank_transfer' => __('debts.collections.payment_methods.bank_transfer'),
                                'cheque'        => __('debts.collections.payment_methods.cheque'),
                            ])
                            ->default('cash')
                            ->required()
                            ->reactive(),

                        Select::make('treasury_transaction_id')
                            ->label(__('debts.collections.fields.treasury_transaction'))
                            ->relationship('treasuryTransaction', 'voucher_number', fn ($query) => $query->where('transaction_type', 'incoming'))
                            ->searchable()
                            ->preload()
                            ->visible(fn (callable $get) => ($get('payment_method') ?? 'cash') === 'cash'),

                        Select::make('executed_by')
                            ->label(__('debts.collections.fields.executed_by'))
                            ->relationship('executor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "موظف #{$record->id}")
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()?->employee?->id)
                            ->disabled(fn () => ! (auth()->user()?->isSuperAdmin() ?? false))
                            ->dehydrated(),

                        Textarea::make('description')
                            ->label(__('debts.collections.fields.description'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
