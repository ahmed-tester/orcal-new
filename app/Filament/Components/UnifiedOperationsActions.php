<?php

namespace App\Filament\Components;

use App\Enums\OperationWorkflowStatus;
use App\Models\UnifiedDeficiencyTask;
use App\Services\BarcodeService;
use App\Services\OperationsReconciliationService;
use App\Services\OperationsSettlementService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class UnifiedOperationsActions
{
    /**
     * Reusable Audit Trail timeline modal action.
     */
    public static function auditTrail(): Action
    {
        return Action::make('audit_trail')
            ->label(__('services_tracking.audit.btn'))
            ->icon('heroicon-o-shield-check')
            ->color('gray')
            ->modalHeading(__('services_tracking.audit.modal_title'))
            ->modalDescription(__('services_tracking.audit.modal_desc'))
            ->modalWidth('3xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->modalContent(fn (Model $record) => view('filament.modals.audit-trail-timeline', [
                'record' => $record,
                'logs' => method_exists($record, 'auditLogs') ? $record->auditLogs()->get() : collect(),
            ]));
    }

    /**
     * Reusable Deficiency Tasks list and resolution modal action.
     */
    public static function deficiencyTasks(): Action
    {
        return Action::make('deficiency_tasks')
            ->label(__('services_tracking.reconciliation.tasks_btn'))
            ->icon('heroicon-o-clipboard-document-list')
            ->color(fn (Model $record) => (method_exists($record, 'deficiencyTasks') && $record->deficiencyTasks()->open()->count() > 0) ? 'danger' : 'gray')
            ->badge(fn (Model $record) => (method_exists($record, 'deficiencyTasks') && ($c = $record->deficiencyTasks()->open()->count()) > 0) ? (string) $c : null)
            ->modalHeading(__('services_tracking.reconciliation.tasks_modal_title'))
            ->modalDescription(__('services_tracking.reconciliation.tasks_modal_desc'))
            ->modalWidth('2xl')
            ->modalContent(fn (Model $record) => view('filament.modals.deficiency-tasks-list', [
                'record' => $record,
                'tasks' => method_exists($record, 'deficiencyTasks') ? $record->deficiencyTasks()->latest()->get() : collect(),
            ]))
            ->modalSubmitAction(fn ($action) => (auth()->user()?->can('resolve_deficiency_task') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner())) ? $action : false)
            ->modalSubmitActionLabel(__('services_tracking.reconciliation.resolve_btn'))
            ->form(fn (Model $record) => [
                Select::make('task_id')
                    ->label(__('services_tracking.reconciliation.select_task_to_resolve'))
                    ->options(
                        method_exists($record, 'deficiencyTasks')
                            ? $record->deficiencyTasks()->open()->pluck('title', 'id')
                            : []
                    )
                    ->visible(fn () => method_exists($record, 'deficiencyTasks')
                        && $record->deficiencyTasks()->open()->exists()
                        && (auth()->user()?->can('resolve_deficiency_task') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner())))
                    ->required(),
                Textarea::make('resolution_notes')
                    ->label(__('services_tracking.reconciliation.resolution_notes'))
                    ->rows(2)
                    ->visible(fn () => method_exists($record, 'deficiencyTasks')
                        && $record->deficiencyTasks()->open()->exists()
                        && (auth()->user()?->can('resolve_deficiency_task') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner()))),
            ])
            ->action(function (Model $record, array $data) {
                if (! empty($data['task_id'])) {
                    $task = UnifiedDeficiencyTask::find($data['task_id']);
                    if ($task) {
                        OperationsReconciliationService::resolveTask(
                            task: $task,
                            notes: $data['resolution_notes'] ?? null,
                            userId: auth()->id(),
                        );

                        Notification::make()
                            ->title(__('services_tracking.reconciliation.task_resolved_success'))
                            ->success()
                            ->send();
                    }
                }
            });
    }

    /**
     * Reusable Handover Barcode / QR code modal action.
     */
    public static function handoverBarcode(string $codeField = 'transaction_number'): Action
    {
        return Action::make('handover_barcode')
            ->label(__('services_tracking.custody.handover_barcode_btn'))
            ->icon('heroicon-o-qr-code')
            ->color('warning')
            ->visible(fn (?Model $record = null) => ! ($record instanceof \App\Models\ServiceTransaction && $record->isFinancialManaged()))
            ->modalWidth('3xl')
            ->modalHeading(__('services_tracking.custody.barcode_modal_title'))
            ->modalDescription(__('services_tracking.custody.barcode_modal_desc'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->modalContent(function (?Model $record = null, ?Action $action = null) use ($codeField) {
                $record = $record ?? $action?->getRecord();
                if (! $record) {
                    return '';
                }

                $code = (string) ($record->{$codeField} ?? $record->getKey());
                $meta = $record->metadata ?? [];
                $empPin = (string) ($meta['employee_signature_code'] ?? str_pad((string) ((($record->id ?: 1) * 73) % 9000 + 1000), 4, '0', STR_PAD_LEFT));
                $qrPayload = __('services_tracking.custody.qr_payload_format', [
                    'code' => $code,
                    'pin' => $empPin,
                ]);
                $qrSvg = BarcodeService::generateQrSvg($qrPayload);

                return view('filament.modals.service-handover-barcode', [
                    'record' => $record,
                    'barcodeSvg' => $qrSvg,
                ]);
            });
    }

    /**
     * Reusable Escalate to Management action.
     */
    public static function escalate(): Action
    {
        return Action::make('escalate')
            ->label(__('services_tracking.escalation.btn'))
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->visible(fn (Model $record) => ! ($record->metadata['is_escalated'] ?? false)
                && ($record->workflow_status ?? null) !== OperationWorkflowStatus::COMPLETED->value
                && (auth()->user()?->can('escalate_service_transaction') || auth()->user()?->can('update_service_transaction') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner())))
            ->modalHeading(__('services_tracking.escalation.heading'))
            ->modalDescription(__('services_tracking.escalation.desc'))
            ->modalWidth('xl')
            ->form([
                Select::make('reason')
                    ->label(__('services_tracking.escalation.reason'))
                    ->options([
                        'client_unresponsive' => __('services_tracking.escalation.reason_client_unresponsive'),
                        'platform_technical_error' => __('services_tracking.escalation.reason_platform_error'),
                        'pricing_discrepancy' => __('services_tracking.escalation.reason_pricing'),
                        'policy_block' => __('services_tracking.escalation.reason_policy'),
                        'other' => __('services_tracking.escalation.reason_other'),
                    ])
                    ->default('client_unresponsive')
                    ->required(),
                Textarea::make('notes')
                    ->label(__('services_tracking.escalation.notes'))
                    ->rows(3)
                    ->required(),
                FileUpload::make('attachment')
                    ->label(__('services_tracking.escalation.attachment'))
                    ->disk('public')
                    ->directory('service-escalations')
                    ->openable()
                    ->downloadable()
                    ->maxSize(10240),
            ])
            ->action(function (Model $record, array $data) {
                OperationsSettlementService::escalateToManagement(
                    transaction: $record,
                    data: $data,
                    userId: auth()->id(),
                );

                Notification::make()
                    ->title(__('services_tracking.escalation.success'))
                    ->danger()
                    ->send();
            });
    }

    /**
     * Reusable Resolve Escalation action.
     */
    public static function resolveEscalation(): Action
    {
        return Action::make('resolve_escalation')
            ->label(__('services_tracking.escalation.resolve_btn'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Model $record) => (bool) ($record->metadata['is_escalated'] ?? false)
                && (auth()->user()?->can('resolve_service_escalation') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner())))
            ->modalHeading(__('services_tracking.escalation.resolve_heading'))
            ->modalDescription(__('services_tracking.escalation.resolve_confirm'))
            ->form([
                Textarea::make('resolution_notes')
                    ->label(__('services_tracking.escalation.resolve_notes'))
                    ->rows(3)
                    ->required(),
            ])
            ->action(function (Model $record, array $data) {
                OperationsSettlementService::resolveEscalation(
                    transaction: $record,
                    resolutionNotes: $data['resolution_notes'] ?? null,
                    userId: auth()->id(),
                );

                Notification::make()
                    ->title(__('services_tracking.escalation.resolve_success'))
                    ->success()
                    ->send();
            });
    }
}

