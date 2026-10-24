<?php

namespace App\Filament\Resources\ServiceTransactions\Tables;

use App\Enums\ApprovalStatus;
use App\Filament\Components\OrgSelect;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
                    ->color('primary'),

                TextColumn::make('status')
                    ->label(__('services_tracking.fields.status'))
                    ->badge()
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('metadata->status', $direction)),

                TextColumn::make('scope_type')
                    ->label(__('services_tracking.fields.scope_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'external' ? __('services_tracking.badges.external') : __('services_tracking.badges.internal'))
                    ->color(fn ($state) => $state === 'external' ? 'warning' : 'gray')
                    ->sortable(),

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

                OrgSelect::tableColumnCompany('company.trade_name', __('services_tracking.fields.company')),

                TextColumn::make('total_amount')
                    ->label(__('services_tracking.fields.total_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('services_tracking.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('remaining_amount')
                    ->label(__('services_tracking.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),

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
                    ->sortable(),

                TextColumn::make('transaction_date')
                    ->label(__('services_tracking.fields.transaction_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('services_tracking.fields.due_date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('executor.user.name')
                    ->label(__('services_tracking.fields.executed_by'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('services_tracking.fields.status'))
                    ->options(ApprovalStatus::class)
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('metadata->status', $data['value']);
                        }
                    }),

                SelectFilter::make('scope_type')
                    ->label(__('services_tracking.fields.scope_type'))
                    ->options([
                        'internal' => __('services_tracking.toggle_options.internal'),
                        'external' => __('services_tracking.toggle_options.external'),
                    ]),

                SelectFilter::make('service_status')
                    ->label(__('services_tracking.fields.service_status'))
                    ->options([
                        'active' => __('services_tracking.service_statuses.active'),
                        'completed' => __('services_tracking.service_statuses.completed'),
                        'inactive' => __('services_tracking.service_statuses.inactive'),
                    ]),

                SelectFilter::make('payment_status')
                    ->label(__('services_tracking.fields.payment_status'))
                    ->options([
                        'unpaid' => __('services_tracking.payment_statuses.unpaid'),
                        'partial' => __('services_tracking.payment_statuses.partial'),
                        'paid' => __('services_tracking.payment_statuses.paid'),
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('services_tracking.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()?->can('approve_service_transaction') && ! $record->isApproved())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = ApprovalStatus::APPROVED;
                        $record->save();

                        Notification::make()
                            ->title(__('services_tracking.actions.approved_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('unapprove')
                    ->label(__('services_tracking.actions.unapprove'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn ($record) => auth()->user()?->can('approve_service_transaction') && $record->isApproved())
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->status = ApprovalStatus::UNAPPROVED;
                        $record->save();

                        Notification::make()
                            ->title(__('services_tracking.actions.unapproved_success'))
                            ->warning()
                            ->send();
                    }),

                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
