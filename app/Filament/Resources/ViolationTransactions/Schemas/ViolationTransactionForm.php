<?php

namespace App\Filament\Resources\ViolationTransactions\Schemas;

use App\Enums\ViolationTransactionType;
use App\Models\Violation;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                        CompanyFormService::makeCompanySelect('company_id', __('violations.transactions.fields.company'), true),

                        DatePicker::make('transaction_date')
                            ->label(__('violations.transactions.fields.transaction_date'))
                            ->default(now())
                            ->required(),

                        Select::make('transaction_type')
                            ->label(__('violations.transactions.fields.transaction_type'))
                            ->options(ViolationTransactionType::class)
                            ->default(ViolationTransactionType::INCOMING->value)
                            ->required(),

                        Select::make('violation_id')
                            ->label(__('violations.transactions.fields.violation'))
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                $query = Violation::query();
                                if ($companyId) {
                                    $query->where('company_id', $companyId);
                                }
                                return $query->get()->mapWithKeys(function ($v) {
                                    $label = ($v->violation_number ?? "ID: {$v->id}") . " — {$v->issuing_authority} (" . number_format($v->remaining_amount, 2) . " SAR remaining)";
                                    return [$v->id => $label];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label(__('violations.transactions.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->required(),

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
