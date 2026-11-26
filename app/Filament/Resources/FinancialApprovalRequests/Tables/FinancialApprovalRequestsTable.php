<?php

namespace App\Filament\Resources\FinancialApprovalRequests\Tables;

use App\Enums\Payroll\FinancialApprovalStatus;
use App\Filament\Resources\FinancialApprovalRequests\FinancialApprovalRequestResource;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Services\Payroll\FinancialApprovalService;
use App\Services\Payroll\PayrollApprovalScopeService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinancialApprovalRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(static::columns())
            ->filters([
                SelectFilter::make('status')
                    ->label(__('financial.approval.columns.status'))
                    ->options(FinancialApprovalStatus::class),
            ])
            ->recordUrl(fn (FinancialApprovalRequest $record): ?string => auth()->user() !== null
                && PayrollApprovalScopeService::canViewRequest(auth()->user(), $record)
                ? FinancialApprovalRequestResource::getUrl('view', ['record' => $record])
                : null)
            ->recordActions([
                static::viewDetailsAction(),
                ...static::workflowActions(),
            ])
            ->defaultSort('id', 'desc');
    }

    /**
     * Shared workflow actions for both the requests table and the dedicated view page.
     *
     * @return array<int, Action>
     */
    public static function workflowActions(): array
    {
        return [
            static::approveTreasuryAction(),
            static::approveManagerAction(),
            static::rerouteAction(),
            static::decisionReasonAction(
                name: 'return_request',
                labelKey: 'financial.approval.actions.return',
                icon: 'heroicon-o-arrow-uturn-left',
                color: 'gray',
                handler: fn (FinancialApprovalRequest $record, string $reason) => FinancialApprovalService::returnRequest($record, auth()->user(), $reason),
                successKey: 'financial.approval.notifications.returned',
                notificationColor: 'warning',
            ),
            static::decisionReasonAction(
                name: 'reject_request',
                labelKey: 'financial.approval.actions.reject',
                icon: 'heroicon-o-x-circle',
                color: 'danger',
                handler: fn (FinancialApprovalRequest $record, string $reason) => FinancialApprovalService::rejectRequest($record, auth()->user(), $reason),
                successKey: 'financial.approval.notifications.rejected',
                notificationColor: 'danger',
            ),
        ];
    }

    /**
     * @return array<int, TextColumn>
     */
    protected static function columns(): array
    {
        return [
            TextColumn::make('request_number')
                ->label(__('financial.approval.columns.request_number'))
                ->searchable()
                ->sortable()
                ->badge()
                ->color('primary'),

            TextColumn::make('payroll_month')
                ->label(__('financial.approval.columns.payroll_month'))
                ->placeholder('-')
                ->sortable(),

            TextColumn::make('companies.trade_name')
                ->label(__('financial.approval.columns.companies'))
                ->badge()
                ->separator('، '),

            TextColumn::make('employees_count')
                ->label(__('financial.approval.columns.employees_count'))
                ->numeric()
                ->sortable(),

            TextColumn::make('total_amount')
                ->label(__('financial.approval.columns.total_amount'))
                ->numeric(decimalPlaces: 2)
                ->suffix(' '.__('services_tracking.currency_sar'))
                ->sortable(),

            TextColumn::make('submitter.name')
                ->label(__('financial.approval.columns.submitter'))
                ->searchable(),

            TextColumn::make('treasuryReviewer.name')
                ->label(__('financial.approval.columns.treasury_reviewer'))
                ->placeholder('-'),

            TextColumn::make('managerUser.name')
                ->label(__('financial.approval.columns.manager'))
                ->placeholder('-'),

            TextColumn::make('status')
                ->label(__('financial.approval.columns.status'))
                ->badge(),

            TextColumn::make('submitted_at')
                ->label(__('financial.approval.columns.submitted_at'))
                ->dateTime('Y-m-d H:i')
                ->sortable(),
        ];
    }

    protected static function viewDetailsAction(): Action
    {
        return Action::make('view_details')
            ->label(__('financial.approval.actions.view_details'))
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->visible(fn (FinancialApprovalRequest $record): bool => auth()->user() !== null
                && PayrollApprovalScopeService::canViewRequest(auth()->user(), $record))
            ->url(fn (FinancialApprovalRequest $record): string => FinancialApprovalRequestResource::getUrl('view', ['record' => $record]));
    }

    protected static function approveTreasuryAction(): Action
    {
        return Action::make('approve_treasury')
            ->label(__('financial.approval.actions.approve_treasury'))
            ->icon('heroicon-o-check-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalWidth('md')
            ->visible(fn (FinancialApprovalRequest $record): bool => $record->status === FinancialApprovalStatus::PENDING_TREASURY
                && auth()->user() !== null
                && PayrollApprovalScopeService::coversAllCompanies(auth()->user(), $record->companies->modelKeys()))
            ->form([
                Textarea::make('notes')
                    ->label(__('financial.approval.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (FinancialApprovalRequest $record, array $data): void {
                try {
                    FinancialApprovalService::approveByTreasury($record, auth()->user(), $data['notes'] ?? null);

                    Notification::make()
                        ->title(__('financial.approval.notifications.treasury_approved'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('financial.approval.notifications.action_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function approveManagerAction(): Action
    {
        return Action::make('approve_manager')
            ->label(__('financial.approval.actions.approve_manager'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->modalWidth('md')
            ->modalDescription(__('financial.approval.modals.approve_manager_desc'))
            ->visible(fn (FinancialApprovalRequest $record): bool => $record->status === FinancialApprovalStatus::PENDING_MANAGER
                && auth()->user() !== null
                && PayrollApprovalScopeService::isAssignedManager(auth()->user(), $record))
            ->form([
                Textarea::make('notes')
                    ->label(__('financial.approval.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (FinancialApprovalRequest $record, array $data): void {
                try {
                    FinancialApprovalService::approveByManager($record, auth()->user(), $data['notes'] ?? null);

                    Notification::make()
                        ->title(__('financial.approval.notifications.manager_approved'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('financial.approval.notifications.execution_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function rerouteAction(): Action
    {
        return Action::make('reroute_request')
            ->label(__('financial.approval.actions.reroute'))
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('info')
            ->modalWidth('md')
            ->modalDescription(__('financial.approval.modals.reroute_desc'))
            ->visible(fn (FinancialApprovalRequest $record): bool => auth()->user() !== null
                && PayrollApprovalScopeService::canRerouteRequest(auth()->user(), $record))
            ->form([
                Textarea::make('reason')
                    ->label(__('financial.approval.fields.reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (FinancialApprovalRequest $record, array $data): void {
                try {
                    FinancialApprovalService::rerouteRequest($record, auth()->user(), (string) $data['reason']);

                    Notification::make()
                        ->title(__('financial.approval.notifications.rerouted'))
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('financial.approval.notifications.action_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function decisionReasonAction(
        string $name,
        string $labelKey,
        string $icon,
        string $color,
        callable $handler,
        string $successKey,
        string $notificationColor,
    ): Action {
        return Action::make($name)
            ->label(__($labelKey))
            ->icon($icon)
            ->color($color)
            ->modalWidth('md')
            ->visible(fn (FinancialApprovalRequest $record): bool => static::canDecide($record))
            ->form([
                Textarea::make('reason')
                    ->label(__('financial.approval.fields.reason'))
                    ->required()
                    ->rows(3),
            ])
            ->action(function (FinancialApprovalRequest $record, array $data) use ($handler, $successKey, $notificationColor): void {
                try {
                    $handler($record, (string) $data['reason']);

                    Notification::make()
                        ->title(__($successKey))
                        ->{$notificationColor}()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title(__('financial.approval.notifications.action_failed'))
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function canDecide(FinancialApprovalRequest $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($record->status === FinancialApprovalStatus::PENDING_TREASURY) {
            return PayrollApprovalScopeService::coversAllCompanies($user, $record->companies->modelKeys());
        }

        if ($record->status === FinancialApprovalStatus::PENDING_MANAGER) {
            return PayrollApprovalScopeService::isAssignedManager($user, $record);
        }

        return false;
    }
}
