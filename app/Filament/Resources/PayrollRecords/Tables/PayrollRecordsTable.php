<?php

namespace App\Filament\Resources\PayrollRecords\Tables;

use App\Enums\PaymentStatus;
use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'employee.user',
                'employee.details.guarantor',
                'company',
            ]))
            ->heading(fn () => __('finance.payroll_records.table_heading', [
                'count' => \App\Filament\Resources\PayrollRecords\PayrollRecordResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('record_number')
                    ->label(__('finance.payroll_records.fields.record_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('payroll_month')
                    ->label(__('finance.payroll_records.fields.payroll_month'))
                    ->date('Y-m')
                    ->sortable(),

                TextColumn::make('operation_type')
                    ->label(__('finance.payroll_records.fields.operation_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'collection' ? __('finance.payroll_records.fields.operation_types.collection') : __('finance.payroll_records.fields.operation_types.payroll'))
                    ->color(fn ($state) => $state === 'collection' ? 'purple' : 'success'),

                TextColumn::make('employee.user.name')
                    ->label(__('finance.payroll_records.fields.employee'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->employee?->employee_number ? '🆔 ' . $record->employee->employee_number : null),

                TextColumn::make('employee.details.guarantor.name')
                    ->label(__('guarantors.single'))
                    ->placeholder('—')
                    ->toggleable(),

                OrgSelect::tableColumnCompany('company.trade_name', __('finance.payroll_records.fields.company')),

                TextColumn::make('basic_salary')
                    ->label(__('finance.payroll_records.fields.basic_salary'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('additional_amount')
                    ->label(__('finance.payroll_records.fields.additional_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('deduction_amount')
                    ->label(__('finance.payroll_records.fields.deduction_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('net_amount')
                    ->label(__('finance.payroll_records.fields.net_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('paid_amount')
                    ->label(__('finance.payroll_records.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('remaining_amount')
                    ->label(__('finance.payroll_records.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('payment_status')
                    ->label(__('finance.payroll_records.fields.payment_status'))
                    ->badge(),
            ])
            ->filters([
                OrgSelect::tableFilterCompany('company_id', __('finance.payroll_records.fields.company')),
                SelectFilter::make('operation_type')
                    ->label(__('finance.payroll_records.fields.operation_type'))
                    ->options([
                        'payroll'    => __('finance.payroll_records.fields.operation_types.payroll'),
                        'collection' => __('finance.payroll_records.fields.operation_types.collection'),
                    ]),
                SelectFilter::make('payment_status')
                    ->label(__('finance.payroll_records.fields.payment_status'))
                    ->options(PaymentStatus::class),
                SelectFilter::make('guarantor_id')
                    ->label(__('guarantors.filters.filter_by_guarantor'))
                    ->options(function () {
                        $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds();
                        $query = \App\Models\Guarantor::query()->where('status', 'active');
                        if (!empty($companyIds)) {
                            $query->whereIn('company_id', $companyIds);
                        }
                        return $query->pluck('name', 'id');
                    })
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('employee.details', function ($q) use ($data) {
                                $q->where('guarantor_id', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('payroll_month')
                    ->label(__('finance.payroll_records.actions.filter_month'))
                    ->form([
                        \Filament\Forms\Components\TextInput::make('month')
                            ->label(__('finance.payroll_records.actions.filter_month_format'))
                            ->placeholder(now()->format('Y-m')),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['month'])) {
                            try {
                                $c = \Carbon\Carbon::parse($data['month']);
                                $query->whereYear('payroll_month', $c->year)
                                      ->whereMonth('payroll_month', $c->month);
                            } catch (\Exception) {}
                        }
                    }),
            ])
            ->recordActions([
                static::getPaySalaryAction(),
                ActionGroup::make([
                    \Filament\Actions\Action::make('print')
                        ->label(__('finance.payroll_records.actions.print_record'))
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn (\App\Models\PayrollRecord $record) => route('payroll-records.print', $record))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkPaySalary')
                        ->label(__('finance.payroll_records.actions.bulk_disburse'))
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->form([
                            \Filament\Forms\Components\DatePicker::make('payment_date')
                                ->label(__('finance.payroll_records.actions.payment_date'))
                                ->default(now())
                                ->required(),
                            \Filament\Forms\Components\Select::make('payment_method')
                                ->label(__('finance.payroll_records.actions.payment_method'))
                                ->options([
                                    'cash'          => __('finance.payroll_records.actions.payment_methods.cash'),
                                    'bank_transfer' => __('finance.payroll_records.actions.payment_methods.bank_transfer'),
                                    'cheque'        => __('finance.payroll_records.actions.payment_methods.cheque'),
                                ])
                                ->default('cash')
                                ->required(),
                            \Filament\Forms\Components\Textarea::make('notes')
                                ->label(__('finance.payroll_records.actions.payment_notes'))
                                ->rows(2),
                        ])
                        ->action(function (\Illuminate\Support\Collection $records, array $data) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ((float) $record->remaining_amount > 0 && $record->payment_status !== PaymentStatus::PAID) {
                                    try {
                                        \App\Services\PayrollCalculationService::disburseSalary(
                                            record: $record,
                                            amount: (float) $record->remaining_amount,
                                            paymentDate: $data['payment_date'],
                                            paymentMethod: $data['payment_method'],
                                            notes: $data['notes'] ?? null
                                        );
                                        $count++;
                                    } catch (\Throwable $e) {}
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->title(__('finance.payroll_records.actions.bulk_disburse_success', ['count' => $count]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    protected static function getPaySalaryAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('paySalary')
            ->label(fn (\App\Models\PayrollRecord $record) => $record->operation_type === 'collection' ? __('finance.payroll_records.actions.collect_amount') : __('finance.payroll_records.actions.pay_salary'))
            ->icon(fn (\App\Models\PayrollRecord $record) => $record->operation_type === 'collection' ? 'heroicon-o-arrow-down-tray' : 'heroicon-o-banknotes')
            ->color(fn (\App\Models\PayrollRecord $record) => $record->operation_type === 'collection' ? 'purple' : 'success')
            ->visible(fn (\App\Models\PayrollRecord $record) => (float) $record->remaining_amount > 0 && $record->payment_status !== PaymentStatus::PAID)
            ->modalHeading(fn (\App\Models\PayrollRecord $record) => $record->operation_type === 'collection'
                ? __('finance.payroll_records.actions.collect_heading', ['employee' => $record->employee?->user?->name ?? "#{$record->employee_id}"])
                : __('finance.payroll_records.actions.pay_salary_heading', [
                    'employee' => $record->employee?->user?->name ?? "#{$record->employee_id}",
                ])
            )
            ->modalDescription(fn (\App\Models\PayrollRecord $record) => __('finance.payroll_records.actions.pay_salary_description', [
                'month'     => \Carbon\Carbon::parse($record->payroll_month)->format('Y-m'),
                'net'       => number_format((float) $record->net_amount, 2),
                'remaining' => number_format((float) $record->remaining_amount, 2),
            ]))
            ->form([
                \Filament\Forms\Components\TextInput::make('amount')
                    ->label(__('finance.payroll_records.actions.amount_to_pay'))
                    ->numeric()
                    ->prefix('SAR')
                    ->default(fn (\App\Models\PayrollRecord $record) => (float) $record->remaining_amount)
                    ->minValue(0.01)
                    ->maxValue(fn (\App\Models\PayrollRecord $record) => (float) $record->remaining_amount)
                    ->required(),

                \Filament\Forms\Components\DatePicker::make('payment_date')
                    ->label(__('finance.payroll_records.actions.payment_date'))
                    ->default(now())
                    ->required(),

                \Filament\Forms\Components\Select::make('payment_method')
                    ->label(__('finance.payroll_records.actions.payment_method'))
                    ->options([
                        'cash'          => __('finance.payroll_records.actions.payment_methods.cash'),
                        'bank_transfer' => __('finance.payroll_records.actions.payment_methods.bank_transfer'),
                        'cheque'        => __('finance.payroll_records.actions.payment_methods.cheque'),
                    ])
                    ->default('cash')
                    ->required(),

                \Filament\Forms\Components\Textarea::make('notes')
                    ->label(__('finance.payroll_records.actions.payment_notes'))
                    ->rows(2),
            ])
            ->action(function (\App\Models\PayrollRecord $record, array $data) {
                try {
                    \App\Services\PayrollCalculationService::disburseSalary(
                        record: $record,
                        amount: (float) $data['amount'],
                        paymentDate: $data['payment_date'] ?? null,
                        paymentMethod: $data['payment_method'] ?? 'cash',
                        notes: $data['notes'] ?? null
                    );

                    \Filament\Notifications\Notification::make()
                        ->title(__('finance.payroll_records.notifications.disbursement_success_title'))
                        ->body(__('finance.payroll_records.notifications.disbursement_success_body'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    \Filament\Notifications\Notification::make()
                        ->title(__('finance.payroll_records.notifications.disbursement_failed_title'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
