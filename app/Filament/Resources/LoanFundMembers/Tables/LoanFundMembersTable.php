<?php

namespace App\Filament\Resources\LoanFundMembers\Tables;

use App\Enums\FundPaymentStatus;
use App\Models\LoanFundMember;
use App\Models\LoanFundMemberPayment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoanFundMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('funds.loan_fund_members.fields.full_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->national_id ? "🆔 {$record->national_id}" : null),

                TextColumn::make('phone')
                    ->label(__('funds.loan_fund_members.fields.phone'))
                    ->searchable(),

                TextColumn::make('loanFund.fund_name')
                    ->label(__('funds.loan_fund_members.fields.fund'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('monthly_installment')
                    ->label(__('funds.loan_fund_members.fields.monthly_installment'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('total_annual_amount')
                    ->label(__('funds.loan_fund_members.fields.total_annual_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('funds.loan_fund_members.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label(__('funds.loan_fund_members.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label(__('funds.loan_fund_members.fields.payment_status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('loan_fund_id')
                    ->label(__('funds.loan_fund_members.fields.fund'))
                    ->relationship('loanFund', 'fund_name'),

                SelectFilter::make('payment_status')
                    ->label(__('funds.loan_fund_members.fields.payment_status'))
                    ->options(FundPaymentStatus::class),
            ])
            ->actions([
                Action::make('recordPayment')
                    ->label(__('funds.loan_fund_members.actions.quick_pay'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->modalHeading(__('funds.loan_fund_members.actions.quick_pay_heading'))
                    ->form([
                        DatePicker::make('payment_date')
                            ->label(__('funds.loan_fund_member_payments.fields.payment_date'))
                            ->default(now())
                            ->required(),

                        DatePicker::make('period_month')
                            ->label(__('funds.loan_fund_member_payments.fields.period_month'))
                            ->default(now())
                            ->required(),

                        TextInput::make('amount')
                            ->label(__('funds.loan_fund_member_payments.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(fn (LoanFundMember $record) => $record->monthly_installment)
                            ->required(),

                        Select::make('payment_method')
                            ->label(__('funds.loan_fund_member_payments.fields.payment_method'))
                            ->options([
                                'cash'              => __('funds.loan_fund_member_payments.methods.cash'),
                                'bank_transfer'     => __('funds.loan_fund_member_payments.methods.bank_transfer'),
                                'payroll_deduction' => __('funds.loan_fund_member_payments.methods.payroll_deduction'),
                            ])
                            ->default('cash')
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('funds.loan_fund_member_payments.fields.notes'))
                            ->rows(2),
                    ])
                    ->action(function (LoanFundMember $record, array $data): void {
                        LoanFundMemberPayment::create([
                            'loan_fund_id'        => $record->loan_fund_id,
                            'loan_fund_member_id' => $record->id,
                            'payment_date'        => $data['payment_date'],
                            'period_month'        => $data['period_month'],
                            'amount'              => $data['amount'],
                            'payment_method'      => $data['payment_method'],
                            'payment_status'      => 'paid',
                            'notes'               => $data['notes'] ?? null,
                            'received_by'         => auth()->id(),
                        ]);

                        Notification::make()
                            ->title(__('funds.loan_fund_members.actions.quick_pay_success'))
                            ->success()
                            ->send();
                    }),

                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
