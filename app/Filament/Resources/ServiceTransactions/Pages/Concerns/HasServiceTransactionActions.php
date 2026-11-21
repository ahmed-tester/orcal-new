<?php

namespace App\Filament\Resources\ServiceTransactions\Pages\Concerns;

use App\Enums\OperationWorkflowStatus;
use App\Filament\Components\ApprovalActions;
use App\Filament\Components\ServiceFinancialActions;
use App\Filament\Components\UnifiedOperationsActions;
use App\Models\ServiceTransaction;
use App\Services\OperationsReconciliationService;
use App\Services\OperationsSettlementService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;

trait HasServiceTransactionActions
{
    protected function getHeaderActions(): array
    {
        [$approve, $unapprove] = ApprovalActions::make('approve_service_transaction', translationKey: 'services_tracking.actions');

        $workflowActions = [
            ServiceFinancialActions::confirmReceipt(),
            ServiceFinancialActions::refundCashSurplus(),
            ServiceFinancialActions::handoverQrAction(),
            ServiceFinancialActions::requestHandover(),
            ServiceFinancialActions::approveClaim(),
            ServiceFinancialActions::payReimbursement(),
            ServiceFinancialActions::recordReceipt(),
            ServiceFinancialActions::recordDisbursement(),
            $approve,
        ];

        $moreActions = [
            $unapprove,
            ServiceFinancialActions::allocateCashSurplus(),
            ServiceFinancialActions::reverseCashSurplusAllocation(),
            UnifiedOperationsActions::handoverBarcode(),
            ...$this->legacySettlementActions(),

            Action::make('run_reconciliation')
                ->label(__('services_tracking.reconciliation.action_btn'))
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function (ServiceTransaction $record): void {
                    $reconciled = OperationsReconciliationService::reconcile($record);
                    $openTasks = $reconciled->deficiencyTasks()->open()->count();
                    $statusEnum = OperationWorkflowStatus::tryFrom((string) $reconciled->workflow_status);

                    Notification::make()
                        ->title(__('services_tracking.reconciliation.success', ['status' => $statusEnum?->getLabel() ?? $reconciled->workflow_status]))
                        ->body($openTasks > 0 ? __('services_tracking.reconciliation.deficiencies_badge', ['count' => $openTasks]) : null)
                        ->color($statusEnum?->getColor() ?? 'info')
                        ->send();
                }),

            UnifiedOperationsActions::deficiencyTasks(),
            UnifiedOperationsActions::auditTrail(),
            UnifiedOperationsActions::escalate(),
            UnifiedOperationsActions::resolveEscalation(),
            DeleteAction::make()->hidden(fn (ServiceTransaction $record) => $record->hasFinancialRecords()),
        ];

        foreach ([...$workflowActions, ...$moreActions] as $action) {
            if ($action instanceof DeleteAction) {
                continue;
            }

            $action->after(function (): void {
                $this->getRecord()->refresh();
                $this->refreshFormData(['paid_amount', 'remaining_amount', 'payment_status', 'status', 'workflow_status', 'service_status', 'completion_date']);
            });
        }

        return [...$workflowActions, ActionGroup::make($moreActions)];
    }

    private function legacySettlementActions(): array
    {
        return [
            Action::make('attach_proofs')
                ->label(__('services_tracking.settlement.attach_proofs_btn'))
                ->icon('heroicon-o-document-check')
                ->color('info')
                ->visible(fn (ServiceTransaction $record) => ! $record->isFinancialManaged())
                ->modalWidth('2xl')
                ->modalHeading(__('services_tracking.settlement.section_title'))
                ->modalDescription(__('services_tracking.settlement.section_desc'))
                ->fillForm(fn ($record) => [
                    'gov_payment_receipt' => $record->metadata['gov_payment_receipt'] ?? null,
                    'bank_deposit_receipt' => $record->metadata['bank_deposit_receipt'] ?? null,
                ])
                ->form([
                    FileUpload::make('gov_payment_receipt')
                        ->label(__('services_tracking.settlement.gov_receipt'))
                        ->disk('public')
                        ->directory('service-receipts')
                        ->openable()
                        ->downloadable()
                        ->maxSize(10240),
                    FileUpload::make('bank_deposit_receipt')
                        ->label(__('services_tracking.settlement.bank_deposit_receipt'))
                        ->disk('public')
                        ->directory('service-receipts')
                        ->openable()
                        ->downloadable()
                        ->maxSize(10240),
                ])
                ->action(function ($record, array $data) {
                    $reconciled = OperationsSettlementService::attachProofs($record, $data);

                    Notification::make()
                        ->title(($reconciled->metadata['reconciliation_status'] ?? '') === 'matched'
                            ? __('services_tracking.settlement.matched')
                            : __('services_tracking.settlement.partial_proofs'))
                        ->color(($reconciled->metadata['reconciliation_status'] ?? '') === 'matched' ? 'success' : 'warning')
                        ->send();
                }),

            Action::make('record_disbursement')
                ->label(__('services_tracking.disbursement.gov_btn'))
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->visible(fn (ServiceTransaction $record) => ! $record->isFinancialManaged() && OperationsSettlementService::canUserDisburse($record))
                ->modalWidth('xl')
                ->modalHeading(fn ($record) => __('services_tracking.disbursement.gov_heading').' ('.$record->transaction_number.')')
                ->modalDescription(fn ($record) => $record->display_service_name.' • '.$record->display_beneficiary)
                ->modalSubmitActionLabel(__('services_tracking.disbursement.confirm_disbursement'))
                ->fillForm(fn ($record) => [
                    'amount' => (float) ($record->metadata['gov_fee_amount'] ?? $record->paid_amount),
                    'channel' => $record->metadata['gov_payment_channel'] ?? 'sadad',
                    'is_self_paid' => (bool) ($record->metadata['self_paid'] ?? false),
                    'notes' => $record->metadata['gov_disbursement_notes'] ?? null,
                ])
                ->form([
                    TextInput::make('custody_holder_display')
                        ->label(__('services_tracking.disbursement.current_custody_holder'))
                        ->default(fn ($record) => $record->executor?->user?->name ?? '—')
                        ->disabled()
                        ->dehydrated(false),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('amount')
                                ->label(__('services_tracking.disbursement.gov_amount'))
                                ->numeric()
                                ->prefix(__('services_tracking.currency_sar'))
                                ->required(),

                            Select::make('channel')
                                ->label(__('services_tracking.disbursement.channel'))
                                ->options([
                                    'sadad' => __('services_tracking.disbursement.channel_sadad'),
                                    'mada' => __('services_tracking.disbursement.channel_mada'),
                                    'qiwa' => __('services_tracking.disbursement.channel_qiwa'),
                                    'absher' => __('services_tracking.disbursement.channel_absher'),
                                    'balady' => __('services_tracking.disbursement.channel_balady'),
                                    'other' => __('services_tracking.disbursement.channel_other'),
                                ])
                                ->default('sadad')
                                ->required(),
                        ]),

                    FileUpload::make('receipt_path')
                        ->label(__('services_tracking.settlement.gov_receipt'))
                        ->disk('public')
                        ->directory('service-receipts')
                        ->openable()
                        ->downloadable()
                        ->maxSize(10240)
                        ->acceptedFileTypes(['application/pdf', 'image/*']),

                    Toggle::make('is_self_paid')
                        ->label(__('services_tracking.disbursement.is_self_paid'))
                        ->helperText(__('services_tracking.disbursement.is_self_paid_hint'))
                        ->live(),

                    TextInput::make('self_paid_employee_preview')
                        ->label(__('services_tracking.disbursement.self_paid_employee'))
                        ->default(fn ($record) => $record->executor?->user?->name ?? auth()->user()?->name ?? '—')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (callable $get) => (bool) $get('is_self_paid')),

                    Textarea::make('notes')
                        ->label(__('services_tracking.disbursement.gov_notes'))
                        ->placeholder(__('services_tracking.disbursement.gov_notes_placeholder'))
                        ->rows(2),
                ])
                ->action(function ($record, array $data) {
                    OperationsSettlementService::recordGovernmentDisbursement(
                        transaction: $record,
                        amount: (float) $data['amount'],
                        channel: (string) $data['channel'],
                        receiptPath: $data['receipt_path'] ?? null,
                        isSelfPaid: (bool) ($data['is_self_paid'] ?? false),
                        notes: $data['notes'] ?? null,
                    );

                    Notification::make()
                        ->title(__('services_tracking.disbursement.gov_success'))
                        ->success()
                        ->send();
                }),

            Action::make('approve_self_paid')
                ->label(__('services_tracking.disbursement.approve_self_paid_btn'))
                ->icon('heroicon-o-check-badge')
                ->color('warning')
                ->visible(fn (ServiceTransaction $record) => ! $record->isFinancialManaged()
                    && ($record->metadata['self_paid'] ?? false)
                    && ($record->metadata['self_paid_status'] ?? '') === 'pending_approval'
                    && (auth()->user()?->can('resolve_service_escalation') || (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner())))
                ->modalHeading(__('services_tracking.disbursement.approve_self_paid_heading'))
                ->modalDescription(fn ($record) => __('services_tracking.disbursement.approve_self_paid_confirm', [
                    'amount' => number_format((float) ($record->metadata['self_paid_amount'] ?? 0), 2),
                    'employee' => $record->executor?->user?->name ?? '—',
                ]))
                ->form([
                    Textarea::make('approval_note')
                        ->label(__('services_tracking.disbursement.approval_note'))
                        ->rows(2),
                ])
                ->action(function ($record, array $data) {
                    OperationsSettlementService::approveSelfPaidDisbursement(
                        transaction: $record,
                        approvalNote: $data['approval_note'] ?? null,
                    );

                    Notification::make()
                        ->title(__('services_tracking.disbursement.approve_self_paid_success'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getCachedHeaderActions(): array
    {
        $workflowActions = parent::getCachedHeaderActions();
        $moreActions = array_pop($workflowActions);

        if (! $moreActions instanceof ActionGroup) {
            return [];
        }

        // Keep action registration stable while presenting the next available action separately.
        $primary = null;
        $secondary = [];

        foreach ($workflowActions as $action) {
            if ($primary === null && $action->isVisible()) {
                $primary = $action;
                continue;
            }

            $secondary[] = clone $action;
        }

        $menu = ActionGroup::make([
            ...$secondary,
            ...array_map(fn (Action $action): Action => clone $action, $moreActions->getActions()),
        ])
            ->livewire($this)
            ->label(__('services_tracking.groups.transaction_actions'))
            ->icon('heroicon-m-ellipsis-vertical')
            ->color('gray')
            ->dropdownPlacement('bottom-end');

        return $primary ? [$primary, $menu] : [$menu];
    }
}
