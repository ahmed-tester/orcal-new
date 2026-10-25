<?php

namespace App\Filament\Resources\InstallmentPayments\Tables;

use App\Enums\ApprovalStatus;
use App\Enums\InstallmentPaymentSource;
use App\Filament\Components\ApprovalActions;
use App\Filament\Components\OrgSelect;
use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InstallmentPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('finance.installment_payments.table_heading', [
                'count' => InstallmentPaymentResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('finance.installment_payments.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label(__('finance.installment_payments.fields.status'))
                    ->badge()
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('metadata->status', $direction)),

                TextColumn::make('payment_type')
                    ->label(__('finance.installment_payments.fields.payment_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'advance' ? __('finance.installment_payments.types.advance') : __('finance.installment_payments.types.installment'))
                    ->color(fn ($state) => $state === 'advance' ? 'success' : 'info')
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label(__('finance.installment_payments.fields.payment_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('installment.beneficiary_name')
                    ->label(__('finance.installment_payments.fields.beneficiary'))
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('installment', function ($q) use ($search) {
                            $q->where('external_name', 'like', "%{$search}%")
                                ->orWhere('external_national_id', 'like', "%{$search}%")
                                ->orWhere('external_phone', 'like', "%{$search}%")
                                ->orWhereHas('employee.user', fn ($emp) => $emp->where('name', 'like', "%{$search}%"));
                        });
                    })
                    ->sortable()
                    ->weight('bold')
                    ->description(function ($record) {
                        $inst = $record->installment;
                        if (! $inst) {
                            return null;
                        }
                        if ($inst->beneficiary_type === 'external') {
                            return __('finance.installments.badges.external').($inst->external_phone ? " | {$inst->external_phone}" : '');
                        }

                        return $record->employee?->employee_number ? $record->employee->employee_number : null;
                    }),

                OrgSelect::tableColumnCompany('company.trade_name', __('finance.installment_payments.fields.company')),

                TextColumn::make('installment.installment_number')
                    ->label(__('finance.installment_payments.fields.installment'))
                    ->badge()
                    ->color('info')
                    ->description(fn ($record) => $record->installment?->installmentType?->name),

                TextColumn::make('amount')
                    ->label(__('finance.installment_payments.fields.amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('payment_source')
                    ->label(__('finance.installment_payments.fields.payment_source'))
                    ->badge(),

                TextColumn::make('executor.user.name')
                    ->label(__('finance.installment_payments.fields.executed_by'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('finance.installment_payments.fields.status'))
                    ->options(ApprovalStatus::class)
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('metadata->status', $data['value']);
                        }
                    }),

                SelectFilter::make('payment_type')
                    ->label(__('finance.installment_payments.fields.payment_type'))
                    ->options([
                        'installment' => __('finance.installment_payments.types.installment'),
                        'advance' => __('finance.installment_payments.types.advance'),
                    ]),

                OrgSelect::tableFilterCompany('company_id', __('finance.installment_payments.fields.company')),

                SelectFilter::make('payment_source')
                    ->label(__('finance.installment_payments.fields.payment_source'))
                    ->options(InstallmentPaymentSource::class),
            ])
            ->recordActions([
                ...ApprovalActions::make('approve_installment_payment', translationKey: 'finance.installment_payments.actions'),

                Action::make('print_voucher')
                    ->label(__('finance.installment_payments.actions.print_voucher'))
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn ($record) => route('system.print', ['type' => 'installment-payment', 'id' => $record->id]))
                    ->openUrlInNewTab(),

                ActionGroup::make([
                    Action::make('print_voucher_menu')
                        ->label(__('finance.installment_payments.actions.print_voucher'))
                        ->icon('heroicon-o-printer')
                        ->url(fn ($record) => route('system.print', ['type' => 'installment-payment', 'id' => $record->id]))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
