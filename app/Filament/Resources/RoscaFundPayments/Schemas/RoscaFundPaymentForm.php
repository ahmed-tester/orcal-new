<?php

namespace App\Filament\Resources\RoscaFundPayments\Schemas;

use App\Enums\FundPaymentStatus;
use App\Models\RoscaFund;
use App\Models\RoscaFundMember;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoscaFundPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.rosca_fund_payments.sections.payment_info'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        Select::make('rosca_fund_id')
                            ->label(__('funds.rosca_fund_payments.fields.fund'))
                            ->relationship('roscaFund', 'fund_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('rosca_fund_member_id', null)),

                        Select::make('rosca_fund_member_id')
                            ->label(__('funds.rosca_fund_payments.fields.member'))
                            ->options(function (callable $get) {
                                $fundId = $get('rosca_fund_id');
                                if (!$fundId) {
                                    return RoscaFundMember::where('status', 'active')->pluck('full_name', 'id');
                                }
                                return RoscaFundMember::where('rosca_fund_id', $fundId)
                                    ->where('status', 'active')
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $member = RoscaFundMember::find($state);
                                    if ($member) {
                                        $set('rosca_fund_id', $member->rosca_fund_id);
                                        $set('scheduled_amount', $member->monthly_installment);
                                        $set('paid_amount', $member->monthly_installment);
                                        $set('payment_status', FundPaymentStatus::PAID->value);
                                    }
                                }
                            }),

                        DatePicker::make('period_month')
                            ->label(__('funds.rosca_fund_payments.fields.period_month'))
                            ->default(now())
                            ->required(),

                        TextInput::make('installment_order')
                            ->label(__('funds.rosca_fund_payments.fields.installment_order'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int)($state ?: 1))),

                        TextInput::make('scheduled_amount')
                            ->label(__('funds.rosca_fund_payments.fields.scheduled_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        TextInput::make('paid_amount')
                            ->label(__('funds.rosca_fund_payments.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $scheduled = (float)($get('scheduled_amount') ?? 0);
                                $paid = (float)$state;
                                if ($paid >= $scheduled && $scheduled > 0) {
                                    $set('payment_status', FundPaymentStatus::PAID->value);
                                    $set('payment_date', now()->toDateString());
                                } elseif ($paid > 0) {
                                    $set('payment_status', FundPaymentStatus::PARTIAL->value);
                                } else {
                                    $set('payment_status', FundPaymentStatus::UNPAID->value);
                                }
                            }),

                        Select::make('payment_status')
                            ->label(__('funds.rosca_fund_payments.fields.payment_status'))
                            ->options(FundPaymentStatus::class)
                            ->default(FundPaymentStatus::UNPAID->value)
                            ->required(),

                        DatePicker::make('payment_date')
                            ->label(__('funds.rosca_fund_payments.fields.payment_date')),

                        Toggle::make('is_bond_closed')
                            ->label(__('funds.rosca_fund_payments.fields.is_bond_closed'))
                            ->default(false),

                        Textarea::make('notes')
                            ->label(__('funds.rosca_fund_payments.fields.notes'))
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }
}
