<?php

namespace App\Filament\Resources\ServiceTransactions\Tables;

use App\Enums\OperationWorkflowStatus;
use App\Filament\Components\OrgSelect;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Models\ServiceTransaction;
use App\Services\OperationsReconciliationService;
use App\Services\OperationsSettlementService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ServiceTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('services_tracking.table_heading', [
                'count' => ServiceTransactionResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('transaction_number')
                    ->label(__('services_tracking.fields.transaction_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('services_tracking.fields.status'))
                    ->badge()
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('metadata->status', $direction)),

                TextColumn::make('scope_type')
                    ->label(__('services_tracking.fields.scope_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'external' ? __('services_tracking.badges.external') : __('services_tracking.badges.internal'))
                    ->color(fn ($state) => $state === 'external' ? 'warning' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('display_service_name')
                    ->label(__('services_tracking.fields.service'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('service_name', 'like', "%{$search}%")
                            ->orWhereHas('service', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->weight('bold')
                    ->description(fn ($record) => $record->scope_type === 'internal' && $record->service_target ? ($record->service_target === 'company' ? __('services_tracking.badges.company') : __('services_tracking.badges.employee')) : null),

                TextColumn::make('display_beneficiary')
                    ->label(__('services_tracking.fields.beneficiary_name'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('beneficiary_name', 'like', "%{$search}%")
                            ->orWhere('beneficiary_phone', 'like', "%{$search}%")
                            ->orWhereHas('employee.user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('company', fn ($q) => $q->where('trade_name', 'like', "%{$search}%"));
                    })
                    ->description(fn ($record) => $record->scope_type === 'external' ? ($record->beneficiary_phone ?? null) : null),

                OrgSelect::tableColumnCompany('company.trade_name', __('services_tracking.fields.company'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_amount')
                    ->label(__('services_tracking.fields.total_amount'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' '.__('services_tracking.currency_sar'))
                    ->sortable()
                    ->description(fn ($record) => (float) $record->remaining_amount > 0 ? (__('services_tracking.fields.remaining_amount').': '.number_format($record->remaining_amount, 2).' '.__('services_tracking.currency_sar')) : null),

                TextColumn::make('paid_amount')
                    ->label(__('services_tracking.fields.paid_amount'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' '.__('services_tracking.currency_sar'))
                    ->sortable()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remaining_amount')
                    ->label(__('services_tracking.fields.remaining_amount'))
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).' '.__('services_tracking.currency_sar'))
                    ->sortable()
                    ->weight('bold')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('metadata.custody_status')
                    ->label(__('services_tracking.custody.label'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'on_employee' => __('services_tracking.custody.on_employee'),
                        'in_treasury' => __('services_tracking.custody.in_treasury'),
                        'settled' => __('services_tracking.custody.settled'),
                        default => __('services_tracking.custody.not_applicable'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'on_employee' => 'warning',
                        'in_treasury' => 'info',
                        'settled' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('metadata.reconciliation_status')
                    ->label(__('services_tracking.settlement.reconciliation_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'matched' ? __('services_tracking.settlement.matched') : __('services_tracking.settlement.pending_proofs'))
                    ->color(fn ($state) => $state === 'matched' ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('service_status')
                    ->label(__('services_tracking.fields.service_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'completed' => __('services_tracking.badges.completed'),
                        'inactive' => __('services_tracking.badges.inactive'),
                        default => __('services_tracking.badges.active'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'completed' => 'success',
                        'inactive' => 'gray',
                        default => 'info',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('workflow_status')
                    ->label(__('services_tracking.fields.workflow_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => OperationWorkflowStatus::tryFrom((string) $state)?->getLabel() ?? (string) $state)
                    ->color(fn ($state) => OperationWorkflowStatus::tryFrom((string) $state)?->getColor() ?? 'gray')
                    ->description(function ($record) {
                        $openCount = $record->deficiencyTasks()->open()->count();

                        return $openCount > 0
                            ? __('services_tracking.reconciliation.deficiencies_badge', ['count' => $openCount])
                            : null;
                    })
                    ->sortable(),

                TextColumn::make('transaction_date')
                    ->label(__('services_tracking.fields.transaction_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('services_tracking.fields.due_date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('executor.user.name')
                    ->label(__('services_tracking.fields.executed_by'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Handled by top Filter Bar
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('services_tracking.actions.open_transaction'))
                    ->url(fn (ServiceTransaction $record) => ServiceTransactionResource::getUrl(
                        ServiceTransactionResource::canEdit($record) ? 'edit' : 'view',
                        ['record' => $record],
                    )),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('create_batch_deposit')
                        ->label(__('services_tracking.batch_deposit.btn'))
                        ->icon('heroicon-o-building-library')
                        ->color('success')
                        ->visible(fn () => auth()->user()?->can('manage_batch_deposits') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner()))
                        ->modalWidth('xl')
                        ->modalHeading(__('services_tracking.batch_deposit.modal_heading'))
                        ->modalDescription(__('services_tracking.batch_deposit.modal_desc'))
                        ->form([
                            Select::make('bank_name')
                                ->label(__('services_tracking.batch_deposit.bank_name'))
                                ->options([
                                    'alrajhi' => __('services_tracking.banks.alrajhi'),
                                    'alahli' => __('services_tracking.banks.alahli'),
                                    'riyad' => __('services_tracking.banks.riyad'),
                                    'alinma' => __('services_tracking.banks.alinma'),
                                    'anb' => __('services_tracking.banks.anb'),
                                    'albilad' => __('services_tracking.banks.albilad'),
                                    'bsf' => __('services_tracking.banks.bsf'),
                                    'sabb' => __('services_tracking.banks.sabb'),
                                    'other' => __('services_tracking.banks.other'),
                                ])
                                ->default('alrajhi')
                                ->required(),
                            TextInput::make('reference_number')
                                ->label(__('services_tracking.batch_deposit.reference_number'))
                                ->required(),
                            DatePicker::make('deposit_date')
                                ->label(__('services_tracking.batch_deposit.deposit_date'))
                                ->default(now())
                                ->required(),
                            FileUpload::make('receipt_path')
                                ->label(__('services_tracking.batch_deposit.receipt'))
                                ->disk('public')
                                ->directory('service-receipts')
                                ->openable()
                                ->downloadable()
                                ->maxSize(10240),
                            Textarea::make('notes')
                                ->label(__('services_tracking.batch_deposit.notes'))
                                ->rows(2),
                        ])
                        ->action(function (Collection $records, array $data) {
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title(__('services_tracking.batch_deposit.no_items'))
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $deposit = OperationsSettlementService::processBatchDeposit($records, $data);

                            Notification::make()
                                ->title(__('services_tracking.batch_deposit.success', [
                                    'number' => $deposit->deposit_number,
                                    'count' => $records->count(),
                                ]))
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('batch_reconcile')
                        ->label(__('services_tracking.reconciliation.batch_reconcile_btn'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $res = OperationsReconciliationService::reconcileCollection($records);

                            Notification::make()
                                ->title(__('services_tracking.reconciliation.batch_reconcile_success', [
                                    'count' => $records->count(),
                                    'matched' => $res['matched'],
                                    'mismatched' => $res['mismatched'],
                                ]))
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            $hasFinancial = $records->contains(fn (ServiceTransaction $tx) => $tx->hasFinancialRecords());

                            if ($hasFinancial) {
                                Notification::make()
                                    ->title(__('financial.errors.cannot_delete_with_financial_records'))
                                    ->danger()
                                    ->send();

                                $action->halt();
                            }
                        }),
                ]),
            ]);
    }
}
