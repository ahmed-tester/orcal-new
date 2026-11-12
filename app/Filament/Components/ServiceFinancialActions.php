<?php

namespace App\Filament\Components;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialPaymentMethod;
use App\Models\CashHandover;
use App\Models\Employee;
use App\Models\FinancialDisbursement;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use App\Models\UnifiedSuspenseAmount;
use App\Services\BarcodeService;
use App\Services\CashHandoverService;
use App\Services\FinancialDisbursementService;
use App\Services\FinancialReceiptService;
use App\Services\FinancialSurplusRefundService;
use App\Services\OperationsSettlementService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceFinancialActions
{
    public static function recordReceipt(): Action
    {
        return Action::make('record_financial_receipt')
            ->label(__('financial.actions.record_receipt'))
            ->icon('heroicon-o-receipt-percent')
            ->color('success')
            ->modalWidth('2xl')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'record_service_receipt')
                && static::remainingToCollect($record) > 0)
            ->form([
                Hidden::make('request_key')->default(fn () => FinancialReceiptService::newRequestKey()),

                TextInput::make('amount')
                    ->label(__('financial.fields.amount'))
                    ->numeric()
                    ->required()
                    ->live(debounce: 400)
                    ->default(function (?ServiceTransaction $record): ?float {
                        $remaining = static::remainingToCollect($record);

                        return $remaining > 0 ? $remaining : null;
                    })
                    ->helperText(function (callable $get, ?ServiceTransaction $record): ?string {
                        $surplus = round((float) $get('amount') - static::remainingToCollect($record), 2);

                        return $surplus > 0
                            ? __('financial.creation_receipt.surplus_hint', ['amount' => number_format($surplus, 2)])
                            : null;
                    })
                    ->prefix(__('services_tracking.currency_sar')),

                Select::make('payment_method')
                    ->label(__('financial.fields.payment_method'))
                    ->options(FinancialPaymentMethod::class)
                    ->default(FinancialPaymentMethod::CASH->value)
                    ->required()
                    ->live(),

                Select::make('collector_employee_id')
                    ->label(__('financial.fields.collector'))
                    ->options(fn (?ServiceTransaction $record) => static::employees($record))
                    ->default(fn (?ServiceTransaction $record) => $record?->executed_by)
                    ->searchable(),

                TextInput::make('payer_name')
                    ->label(__('financial.fields.payer_name'))
                    ->default(fn (?ServiceTransaction $record) => $record?->display_beneficiary)
                    ->maxLength(255),

                TextInput::make('reference_number')
                    ->label(__('financial.fields.reference_number'))
                    ->maxLength(100),

                FileUpload::make('proof_path')
                    ->label(__('financial.fields.proof'))
                    ->disk('public')
                    ->directory('financial-receipts')
                    ->openable()
                    ->downloadable()
                    ->maxSize(10240),

                Select::make('destination_type')
                    ->label(__('financial.fields.destination_type'))
                    ->options(__('financial.destination_types'))
                    ->visible(fn (callable $get) => $get('payment_method') !== FinancialPaymentMethod::CASH->value),

                TextInput::make('destination_reference')
                    ->label(__('financial.fields.destination_reference'))
                    ->visible(fn (callable $get) => $get('payment_method') !== FinancialPaymentMethod::CASH->value)
                    ->maxLength(100),

                DateTimePicker::make('received_at')
                    ->label(__('financial.fields.received_at'))
                    ->default(now()),

                Toggle::make('confirm')
                    ->label(__('financial.actions.confirm_receipt'))
                    ->default(false),

                Textarea::make('notes')
                    ->label(__('financial.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (ServiceTransaction $record, array $data) {
                FinancialReceiptService::record($record, $data);

                Notification::make()
                    ->title(__('financial.actions.record_receipt'))
                    ->body(__('services_tracking.notifications.financial_receipt_recorded_title'))
                    ->success()
                    ->send();
            });
    }

    public static function confirmReceipt(): Action
    {
        return Action::make('confirm_financial_receipt')
            ->label(__('financial.actions.confirm_pending_receipt'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->modalWidth('2xl')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'confirm_service_receipt')
                && $record?->financialReceipts()->pending()->exists())
            ->form([
                Select::make('financial_receipt_id')
                    ->label(__('financial.actions.select_pending_receipt'))
                    ->options(fn (?ServiceTransaction $record) => $record?->financialReceipts()
                        ->pending()
                        ->get()
                        ->mapWithKeys(fn (FinancialReceipt $r) => [
                            $r->id => $r->receipt_number.' • '.number_format((float) $r->amount, 2).' '.__('services_tracking.currency_sar').' ('.($r->payment_method?->getLabel() ?? $r->payment_method).')',
                        ])
                        ->all())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $receipt = FinancialReceipt::find($state);
                        if ($receipt) {
                            $set('is_cash', $receipt->isCash());
                            $set('reference_number', $receipt->reference_number);
                            $set('destination_type', $receipt->destination_type);
                            $set('destination_reference', $receipt->destination_reference);
                        }
                    }),

                Hidden::make('is_cash')->default(true),

                TextInput::make('reference_number')
                    ->label(__('financial.fields.reference_number'))
                    ->maxLength(100),

                FileUpload::make('proof_path')
                    ->label(__('financial.fields.proof'))
                    ->disk('public')
                    ->directory('financial-receipts')
                    ->openable()
                    ->downloadable()
                    ->maxSize(10240),

                Select::make('destination_type')
                    ->label(__('financial.fields.destination_type'))
                    ->options(__('financial.destination_types'))
                    ->visible(fn (callable $get) => ! $get('is_cash')),

                TextInput::make('destination_reference')
                    ->label(__('financial.fields.destination_reference'))
                    ->visible(fn (callable $get) => ! $get('is_cash'))
                    ->maxLength(100),
            ])
            ->action(function (ServiceTransaction $record, array $data): void {
                $receipt = FinancialReceipt::whereKey($data['financial_receipt_id'])->firstOrFail();

                FinancialReceiptService::confirm($receipt, [
                    'reference_number' => $data['reference_number'] ?? null,
                    'proof_path' => $data['proof_path'] ?? null,
                    'destination_type' => $data['destination_type'] ?? null,
                    'destination_reference' => $data['destination_reference'] ?? null,
                ], auth()->user());

                Notification::make()
                    ->title(__('financial.notifications.receipt_confirmed', ['number' => $receipt->receipt_number]))
                    ->success()
                    ->send();

                static::notifyHandoverIntentOutcome($receipt->refresh());
            });
    }

    /**
     * QR code per submitted cash handover of the transaction, identified by
     * the handover number only.
     */
    public static function handoverQrAction(): Action
    {
        return Action::make('cash_handover_qr')
            ->label(__('financial.actions.handover_receipts_qr'))
            ->icon('heroicon-o-qr-code')
            ->color('warning')
            ->visible(fn (?ServiceTransaction $record) => $record instanceof ServiceTransaction
                && $record->isFinancialManaged()
                && static::submittedHandovers($record)->isNotEmpty())
            ->modalWidth('2xl')
            ->modalHeading(__('financial.handover_qr.modal_title'))
            ->modalDescription(__('financial.handover_qr.modal_desc'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->modalContent(function (?ServiceTransaction $record = null) {
                if (! $record) {
                    return '';
                }

                $cards = static::submittedHandovers($record)
                    ->map(fn (CashHandover $handover) => [
                        'handover' => $handover,
                        'qrSvg' => BarcodeService::generateQrSvg('HANDOVER:'.$handover->handover_number),
                        'signatureCode' => CashHandoverService::signatureCodeForDelivery($handover),
                    ])
                    ->all();

                return view('filament.modals.cash-handover-qr', ['cards' => $cards]);
            });
    }

    /**
     * Surface the deferred handover intent outcome stored by
     * FinancialReceiptService::confirm().
     */
    private static function notifyHandoverIntentOutcome(FinancialReceipt $receipt): void
    {
        $meta = $receipt->metadata ?? [];
        $status = $meta['handover_intent_status'] ?? null;

        if ($status === 'fulfilled') {
            Notification::make()
                ->title(__('financial.notifications.handover_intent_fulfilled'))
                ->success()
                ->send();
        } elseif ($status === 'failed') {
            Notification::make()
                ->title(__('financial.notifications.handover_intent_followup', [
                    'error' => $meta['handover_intent_error'] ?? '',
                ]))
                ->warning()
                ->send();
        }
    }

    /**
     * Submitted cash handovers linked to this transaction through its receipts.
     *
     * @return Collection<int, CashHandover>
     */
    private static function submittedHandovers(ServiceTransaction $record): Collection
    {
        return CashHandover::accessibleBy()
            ->submitted()
            ->whereHas('items', fn ($query) => $query->whereHas('receipt', fn ($receiptQuery) => $receiptQuery
                ->forSource(FinancialReceipt::SOURCE_SERVICE_TRANSACTION, (int) $record->id)))
            ->orderBy('id')
            ->get();
    }

    public static function requestHandover(): Action
    {
        return Action::make('request_financial_handover')
            ->label(__('financial.actions.request_handover'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('warning')
            ->modalWidth('3xl')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'request_cash_handover')
                && FinancialReceiptService::availableForHandover($record)->isNotEmpty())
            ->form(fn (?ServiceTransaction $record) => [
                Hidden::make('request_key')->default(fn () => CashHandoverService::newRequestKey()),

                Repeater::make('items')
                    ->label(__('financial.fields.handover_receipts'))
                    ->schema([
                        Select::make('financial_receipt_id')
                            ->label(__('financial.fields.handover_receipts'))
                            ->options(fn () => FinancialReceiptService::availableForHandover($record)
                                ->mapWithKeys(fn ($receipt) => [
                                    $receipt->id => $receipt->receipt_number.' • '.number_format($receipt->availableForHandover(), 2).' '.__('services_tracking.currency_sar'),
                                ])
                                ->all())
                            ->required()
                            ->distinct(),

                        TextInput::make('amount')
                            ->label(__('financial.fields.amount'))
                            ->numeric()
                            ->required()
                            ->prefix(__('services_tracking.currency_sar')),
                    ])
                    ->columns(2)
                    ->default(fn () => FinancialReceiptService::availableForHandover($record)
                        ->map(fn ($receipt) => [
                            'financial_receipt_id' => $receipt->id,
                            'amount' => $receipt->availableForHandover(),
                        ])
                        ->all())
                    ->minItems(1)
                    ->columnSpanFull(),

                Select::make('delivered_by_employee_id')
                    ->label(__('financial.fields.collector'))
                    ->options(fn (?ServiceTransaction $record) => static::employees($record))
                    ->default(fn (?ServiceTransaction $record) => $record?->executed_by)
                    ->searchable(),

                Textarea::make('notes')
                    ->label(__('financial.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (ServiceTransaction $record, array $data) {
                $amounts = collect($data['items'] ?? [])
                    ->filter(fn ($item) => ! empty($item['financial_receipt_id']))
                    ->mapWithKeys(fn ($item) => [(int) $item['financial_receipt_id'] => (float) $item['amount']])
                    ->all();

                CashHandoverService::requestForTransaction($record, $amounts, [
                    'request_key' => $data['request_key'],
                    'delivered_by_employee_id' => $data['delivered_by_employee_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                Notification::make()
                    ->title(__('financial.actions.request_handover'))
                    ->success()
                    ->send();
            });
    }

    public static function recordDisbursement(): Action
    {
        return Action::make('record_financial_disbursement')
            ->label(__('financial.actions.record_disbursement'))
            ->icon('heroicon-o-banknotes')
            ->color('primary')
            ->modalWidth('2xl')
            ->visible(function (?ServiceTransaction $record): bool {
                if (! static::visible($record, 'record_service_disbursement')) {
                    return false;
                }

                $summary = $record->financialSummary();

                return $summary['fee_defined'] && (float) $summary['fee_remaining'] > (float) $summary['awaiting_approval'];
            })
            ->form([
                Hidden::make('request_key')->default(fn () => FinancialDisbursementService::newRequestKey()),

                Select::make('disbursement_type')
                    ->label(__('financial.fields.disbursement_type'))
                    ->options(FinancialDisbursementType::class)
                    ->default(FinancialDisbursementType::TREASURY_DIRECT->value)
                    ->required()
                    ->live(),

                TextInput::make('amount')
                    ->label(__('financial.fields.amount'))
                    ->numeric()
                    ->required()
                    ->prefix(__('services_tracking.currency_sar')),

                TextInput::make('beneficiary_name')
                    ->label(__('financial.fields.beneficiary'))
                    ->maxLength(255),

                TextInput::make('reference_number')
                    ->label(__('financial.fields.reference_number'))
                    ->required()
                    ->maxLength(100),

                FileUpload::make('receipt_proof')
                    ->label(__('financial.fields.receipt_proof'))
                    ->disk('public')
                    ->directory('financial-disbursements')
                    ->openable()
                    ->downloadable()
                    ->maxSize(10240),

                Select::make('paid_by_employee_id')
                    ->label(__('financial.fields.paid_by_employee'))
                    ->options(fn (?ServiceTransaction $record) => static::employees($record))
                    ->searchable()
                    ->visible(fn (callable $get) => $get('disbursement_type') === FinancialDisbursementType::EMPLOYEE_PERSONAL->value),

                Textarea::make('notes')
                    ->label(__('financial.fields.notes'))
                    ->rows(2)
                    ->columnSpanFull(),
            ])
            ->action(function (ServiceTransaction $record, array $data) {
                FinancialDisbursementService::record($record, $data);

                Notification::make()
                    ->title(__('financial.actions.record_disbursement'))
                    ->success()
                    ->send();
            });
    }

    public static function approveClaim(): Action
    {
        return Action::make('approve_financial_claim')
            ->label(__('financial.actions.approve_claim'))
            ->icon('heroicon-o-check-badge')
            ->color('info')
            ->modalWidth('xl')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'approve_self_paid_disbursement')
                && static::claims($record, [FinancialDisbursementStatus::PENDING_APPROVAL])->isNotEmpty())
            ->form(fn (?ServiceTransaction $record) => [
                Select::make('disbursement_id')
                    ->label(__('financial.fields.beneficiary'))
                    ->options(static::claims($record, [FinancialDisbursementStatus::PENDING_APPROVAL])
                        ->mapWithKeys(fn (FinancialDisbursement $claim) => [
                            $claim->id => $claim->disbursement_number.' • '.number_format((float) $claim->amount, 2).' '.__('services_tracking.currency_sar'),
                        ])
                        ->all())
                    ->required(),

                Textarea::make('note')
                    ->label(__('financial.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (array $data) {
                $claim = FinancialDisbursement::findOrFail($data['disbursement_id']);
                FinancialDisbursementService::approve($claim, null, $data['note'] ?? null);

                Notification::make()
                    ->title(__('financial.actions.approve_claim'))
                    ->success()
                    ->send();
            });
    }

    public static function payReimbursement(): Action
    {
        return Action::make('pay_financial_reimbursement')
            ->label(__('financial.actions.pay_reimbursement'))
            ->icon('heroicon-o-wallet')
            ->color('success')
            ->modalWidth('xl')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'pay_employee_reimbursement')
                && static::claims($record, [
                    FinancialDisbursementStatus::APPROVED,
                    FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
                ])->isNotEmpty())
            ->form(fn (?ServiceTransaction $record) => [
                Hidden::make('request_key')->default(fn () => FinancialDisbursementService::newRequestKey('rmb')),

                Select::make('disbursement_id')
                    ->label(__('financial.fields.beneficiary'))
                    ->options(static::claims($record, [
                        FinancialDisbursementStatus::APPROVED,
                        FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
                    ])->mapWithKeys(fn (FinancialDisbursement $claim) => [
                        $claim->id => $claim->disbursement_number.' • '.__('financial.summary.employee_due').': '.number_format($claim->remainingDue(), 2),
                    ])->all())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $claim = FinancialDisbursement::find($state);
                        if ($claim) {
                            $set('amount', $claim->remainingDue());
                        }
                    }),

                TextInput::make('amount')
                    ->label(__('financial.fields.amount'))
                    ->numeric()
                    ->required()
                    ->prefix(__('services_tracking.currency_sar')),

                Textarea::make('notes')
                    ->label(__('financial.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (array $data) {
                $claim = FinancialDisbursement::findOrFail($data['disbursement_id']);
                FinancialDisbursementService::pay($claim, $data);

                Notification::make()
                    ->title(__('financial.actions.pay_reimbursement'))
                    ->success()
                    ->send();
            });
    }

    public static function refundCashSurplus(): Action
    {
        return Action::make('refund_cash_surplus')
            ->label(__('financial.actions.refund_surplus'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'confirm_cash_handover')
                && auth()->user()->can('create', TreasuryTransaction::class)
                && FinancialSurplusRefundService::quote($record) !== null)
            ->modalDescription(__('financial.refund.description'))
            ->modalSubmitActionLabel(__('financial.actions.confirm_surplus_refund'))
            ->fillForm(function (ServiceTransaction $record): array {
                $quote = FinancialSurplusRefundService::quote($record);
                $receipt = $quote['receipt'] ?? null;

                return [
                    'refund_request_key' => (string) Str::uuid(),
                    'refund_receipt_id' => $receipt?->id,
                    'refund_receipt_number' => $receipt?->receipt_number,
                    'refund_payer' => $receipt?->payer_name ?: $record->display_beneficiary,
                    'refund_amount' => $quote['amount'] ?? null,
                ];
            })
            ->schema([
                Hidden::make('refund_request_key'),
                Hidden::make('refund_receipt_id'),
                TextInput::make('refund_receipt_number')->label(__('financial.refund.receipt'))->disabled(),
                TextInput::make('refund_payer')->label(__('financial.fields.payer_name'))->disabled(),
                TextInput::make('refund_amount')
                    ->label(__('financial.refund.amount'))
                    ->prefix(__('services_tracking.currency_sar'))
                    ->disabled()
                    ->dehydrated(),
            ])
            ->action(function (ServiceTransaction $record, array $data, Action $action): void {
                try {
                    FinancialSurplusRefundService::confirm(
                        $record,
                        (int) ($data['refund_receipt_id'] ?? 0),
                        (float) ($data['refund_amount'] ?? 0),
                        (string) ($data['refund_request_key'] ?? ''),
                    );
                } catch (ValidationException $exception) {
                    Notification::make()->title($exception->validator->errors()->first())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title(__('financial.refund.success'))->success()->send();
            });
    }

    private static function remainingToCollect(?ServiceTransaction $record): float
    {
        if (! $record) {
            return 0.0;
        }

        $summary = $record->financialSummary();

        return max(0, round($summary['client_remaining'] - $summary['pending_receipts'], 2));
    }

    public static function allocateCashSurplus(): Action
    {
        return Action::make('allocate_cash_surplus')
            ->label(__('financial.actions.allocate_cash_surplus'))
            ->icon('heroicon-o-banknotes')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'manage_suspense_amounts')
                && static::availableCashSurpluses($record)->isNotEmpty())
            ->schema([
                Select::make('suspense_amount_id')
                    ->label(__('financial.summary.surplus'))
                    ->options(fn (ServiceTransaction $record) => static::availableCashSurpluses($record)
                        ->mapWithKeys(fn (UnifiedSuspenseAmount $surplus) => [
                            $surplus->id => $surplus->reference_number.' · '.number_format((float) $surplus->amount, 2)
                                .($surplus->notes ? ' · '.$surplus->notes : ''),
                        ]))
                    ->searchable()
                    ->required(),
                Textarea::make('notes')->label(__('financial.fields.notes')),
            ])
            ->action(function (ServiceTransaction $record, array $data) {
                $handover = CashHandover::accessibleBy()->confirmed()
                    ->where('company_id', $record->company_id)
                    ->where('metadata->suspense_amount_id', (int) $data['suspense_amount_id'])
                    ->firstOrFail();
                $surplus = UnifiedSuspenseAmount::findOrFail($handover->metadata['suspense_amount_id']);
                OperationsSettlementService::allocateSuspenseAmount($surplus, $record, $data['notes'] ?? null);
                Notification::make()->title(__('financial.actions.allocate_cash_surplus'))->success()->send();
            });
    }

    public static function reverseCashSurplusAllocation(): Action
    {
        return Action::make('reverse_cash_surplus_allocation')
            ->label(__('financial.actions.reverse_cash_surplus_allocation'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->visible(fn (?ServiceTransaction $record) => static::visible($record, 'manage_suspense_amounts')
                && $record->financialReceipts()->confirmed()->whereNotNull('metadata->cash_handover_id')->exists())
            ->schema([
                Select::make('receipt_id')
                    ->label(__('financial.fields.surplus_allocation_receipt'))
                    ->options(fn (ServiceTransaction $record) => $record->financialReceipts()->confirmed()
                        ->whereNotNull('metadata->cash_handover_id')->pluck('receipt_number', 'id'))
                    ->required(),
                Textarea::make('reason')->label(__('financial.fields.rejection_reason'))->required(),
            ])
            ->action(function (ServiceTransaction $record, array $data) {
                $receipt = $record->financialReceipts()->findOrFail($data['receipt_id']);
                OperationsSettlementService::reverseCashSurplusAllocation($record, $receipt, $data['reason']);
                Notification::make()->title(__('financial.actions.reverse_cash_surplus_allocation'))->success()->send();
            });
    }

    private static function availableCashSurpluses(ServiceTransaction $record): Collection
    {
        $remaining = (float) $record->financialSummary()['client_remaining'];

        if ($remaining <= 0) {
            return collect();
        }

        $ids = CashHandover::accessibleBy()->confirmed()
            ->where('company_id', $record->company_id)
            ->whereNotNull('metadata->suspense_amount_id')
            ->get(['metadata'])
            ->map(fn (CashHandover $handover) => $handover->metadata['suspense_amount_id']);

        return UnifiedSuspenseAmount::whereIn('id', $ids)
            ->where('company_id', $record->company_id)
            ->where('status', 'pending')
            ->where('amount', '<=', $remaining)
            ->latest('id')
            ->get();
    }

    private static function visible(?ServiceTransaction $record, string $permission): bool
    {
        return $record instanceof ServiceTransaction
            && $record->isFinancialManaged()
            && (auth()->user()?->can($permission) ?? false);
    }

    /**
     * @return array<int, string>
     */
    private static function employees(?ServiceTransaction $record): array
    {
        if (! $record) {
            return [];
        }

        return Employee::query()
            ->where('company_id', $record->company_id)
            ->with('user')
            ->get()
            ->mapWithKeys(fn (Employee $employee) => [
                $employee->id => $employee->user?->name ?? $employee->name ?? ('#'.$employee->id),
            ])
            ->all();
    }

    /**
     * @param  array<int, FinancialDisbursementStatus>  $statuses
     * @return Collection<int, FinancialDisbursement>
     */
    private static function claims(?ServiceTransaction $record, array $statuses)
    {
        if (! $record) {
            return collect();
        }

        return FinancialDisbursement::forSource(FinancialDisbursement::SOURCE_SERVICE_TRANSACTION, (int) $record->id)
            ->whereIn('status', array_map(fn (FinancialDisbursementStatus $status) => $status->value, $statuses))
            ->orderBy('id')
            ->get();
    }
}
