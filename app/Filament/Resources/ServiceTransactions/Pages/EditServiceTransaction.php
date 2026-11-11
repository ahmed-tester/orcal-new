<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Enums\OperationWorkflowStatus;
use App\Filament\Components\UnifiedOperationsActions;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Services\OperationsReconciliationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditServiceTransaction extends EditRecord
{
    protected static string $resource = ServiceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UnifiedOperationsActions::handoverBarcode()
                ->color('warning'),

            Action::make('run_reconciliation')
                ->label(__('services_tracking.reconciliation.action_btn'))
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function () {
                    $record = $this->getRecord();
                    $reconciled = OperationsReconciliationService::reconcile($record);
                    $openTasks = $reconciled->deficiencyTasks()->open()->count();
                    $statusEnum = OperationWorkflowStatus::tryFrom((string) $reconciled->workflow_status);
                    $statusLabel = $statusEnum?->getLabel() ?? (string) $reconciled->workflow_status;

                    Notification::make()
                        ->title(__('services_tracking.reconciliation.success', ['status' => $statusLabel]))
                        ->body($openTasks > 0 ? __('services_tracking.reconciliation.deficiencies_badge', ['count' => $openTasks]) : null)
                        ->color($statusEnum?->getColor() ?? 'info')
                        ->send();

                    $this->refreshFormData(['workflow_status']);
                }),

            UnifiedOperationsActions::escalate(),
            UnifiedOperationsActions::resolveEscalation(),

            ActionGroup::make([
                UnifiedOperationsActions::deficiencyTasks(),
                UnifiedOperationsActions::auditTrail(),
                DeleteAction::make()
                    ->hidden(fn (): bool => $this->getRecord()->hasFinancialRecords()),
            ])
                ->label(__('services_tracking.groups.audit_actions'))
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $recordModel = $this->getRecord();
        OperationsReconciliationService::reconcile($recordModel);
        $this->refreshFormData(['workflow_status', 'metadata', 'service_status']);
    }

    protected function afterSave(): void
    {
        OperationsReconciliationService::reconcile($this->getRecord());
    }
}
