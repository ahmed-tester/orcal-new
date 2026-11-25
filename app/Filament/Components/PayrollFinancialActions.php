<?php

namespace App\Filament\Components;

use App\Enums\FinancialHandoverStatus;
use App\Enums\PaymentStatus;
use App\Models\CashHandover;
use App\Models\FinancialReceipt;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Services\BarcodeService;
use App\Services\CashHandoverService;
use App\Services\Payroll\EmployeeSalaryProfileService;
use App\Services\FinancialReceiptService;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\PayrollDeductionSettlementService;
use App\Services\Payroll\PayrollDisbursementService;
use App\Services\Payroll\PayrollRecordFinancialService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PayrollFinancialActions
{
    public static function recalculateSaudiPolicy(): Action
    {
        return Action::make('recalculate_saudi_policy')
            ->label(__('finance.payroll_records.actions.recalculate_policy'))
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->modalWidth('lg')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isPayroll()
                && ! $record->hasFinancialImpact()
                && $record->employee !== null
                && EmployeeSalaryProfileService::isEligibleSaudiEmployee($record->employee)
                && (auth()->user()?->can('update', $record) ?? false))
            ->modalHeading(fn (PayrollRecord $record): string => __('finance.payroll_records.actions.recalculate_policy_heading', [
                'employee' => $record->employee?->user?->name ?? '—',
            ]))
            ->modalDescription(__('finance.payroll_records.actions.recalculate_policy_desc'))
            ->form(function (PayrollRecord $record): array {
                $monthStr = Carbon::parse($record->payroll_month)->format('Y-m');
                $preview = PayrollCalculationService::calculateEmployeeFinancials(
                    $record->employee_id,
                    $monthStr,
                    $record->id,
                    $record,
                    true
                );
                $currentNet = (float) $record->net_amount;
                $newNet = (float) ($preview['net_amount'] ?? $currentNet);
                $diff = round($newNet - $currentNet, 2);

                return [
                    TextEntry::make('current_summary')
                        ->hiddenLabel()
                        ->state(__('finance.payroll_records.actions.recalculate_current_net', [
                            'net' => number_format($currentNet, 2),
                            'deduction' => number_format((float) $record->absence_deduction, 2),
                        ])),
                    TextEntry::make('new_summary')
                        ->hiddenLabel()
                        ->state(__('finance.payroll_records.actions.recalculate_new_net', [
                            'net' => number_format($newNet, 2),
                            'deduction' => number_format((float) ($preview['absence_deduction'] ?? 0), 2),
                            'diff' => number_format($diff, 2),
                        ])),
                ];
            })
            ->action(function (PayrollRecord $record): void {
                PayrollCalculationService::recalculateExistingRecord($record);

                Notification::make()
                    ->title(__('finance.payroll_records.actions.recalculate_success'))
                    ->success()
                    ->send();
            });
    }

    public static function confirmCollectionReceipt(): Action
    {
        return Action::make('confirm_collection_receipt')
            ->label(__('invoices.actions.confirm_receipt'))
            ->icon('heroicon-o-check-badge')
            ->color('info')
            ->modalWidth('xl')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isCollection()
                && static::canPerform('confirm_service_receipt')
                && static::pendingCollectionReceipts($record)->isNotEmpty())
            ->form([
                Select::make('receipt_id')
                    ->label(__('financial.fields.receipt'))
                    ->options(fn (?PayrollRecord $record): array => static::pendingCollectionReceipts($record)
                        ->mapWithKeys(fn (FinancialReceipt $r) => [
                            $r->id => $r->receipt_number.' — '.number_format((float) $r->amount, 2).' '.__('services_tracking.currency_sar'),
                        ])
                        ->all())
                    ->required(),

                Select::make('destination_type')
                    ->label(__('financial.fields.destination_type'))
                    ->options([
                        'company_bank' => __('financial.destinations.company_bank'),
                        'pos_terminal' => __('financial.destinations.pos_terminal'),
                    ])
                    ->required(),

                TextInput::make('destination_reference')
                    ->label(__('financial.fields.destination_reference'))
                    ->required(),

                TextInput::make('reference_number')
                    ->label(__('financial.fields.reference_number'))
                    ->required(),

                FileUpload::make('proof_path')
                    ->label(__('financial.fields.proof'))
                    ->disk('public')
                    ->directory('financial-receipts')
                    ->required(),
            ])
            ->action(function (PayrollRecord $record, array $data): void {
                $receipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, (int) $record->id)
                    ->findOrFail((int) $data['receipt_id']);

                FinancialReceiptService::confirm($receipt, $data);

                Notification::make()
                    ->title(__('financial.notifications.receipt_confirmed', ['number' => $receipt->receipt_number]))
                    ->success()
                    ->send();
            });
    }

    public static function requestCollectionHandover(): Action
    {
        return Action::make('request_collection_handover')
            ->label(__('invoices.actions.request_handover'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('warning')
            ->modalWidth('xl')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isCollection()
                && static::canPerform('request_cash_handover')
                && static::availableHandoverReceipts($record)->isNotEmpty())
            ->form([
                Hidden::make('request_key')->default(fn () => CashHandoverService::newRequestKey()),

                TextEntry::make('custody_info')
                    ->label(__('financial.fields.custody_available'))
                    ->state(function (?PayrollRecord $record): string {
                        if (! $record) {
                            return '0.00';
                        }
                        $total = static::availableHandoverReceipts($record)->sum(fn (FinancialReceipt $r) => $r->availableForHandover());

                        return number_format((float) $total, 2).' '.__('services_tracking.currency_sar');
                    }),

                Textarea::make('notes')
                    ->label(__('finance.payroll_records.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (PayrollRecord $record, array $data): void {
                $receipts = static::availableHandoverReceipts($record);
                if ($receipts->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => __('financial.errors.handover_items_required'),
                    ]);
                }

                $items = $receipts->mapWithKeys(fn (FinancialReceipt $r) => [$r->id => $r->availableForHandover()])->all();
                $handover = CashHandoverService::request($items, $data);

                Notification::make()
                    ->title(__('financial.notifications.handover_requested', ['number' => $handover->handover_number]))
                    ->success()
                    ->send();
            });
    }

    public static function handoverQr(): Action
    {
        return Action::make('collection_handover_qr')
            ->label(__('financial.actions.handover_receipts_qr'))
            ->icon('heroicon-o-qr-code')
            ->color('warning')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isCollection()
                && static::submittedHandovers($record)->isNotEmpty())
            ->modalWidth('2xl')
            ->modalHeading(__('financial.handover_qr.modal_title'))
            ->modalDescription(__('financial.handover_qr.modal_desc'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->modalContent(function (?PayrollRecord $record = null) {
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

    public static function reverseCollectionReceipt(): Action
    {
        return Action::make('reverse_collection_receipt')
            ->label(__('invoices.actions.reverse_receipt'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalWidth('lg')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isCollection()
                && static::canPerform('reverse_financial_transaction')
                && static::confirmedCollectionReceipts($record)->isNotEmpty())
            ->form([
                Select::make('receipt_id')
                    ->label(__('financial.fields.receipt'))
                    ->options(fn (?PayrollRecord $record): array => static::confirmedCollectionReceipts($record)
                        ->mapWithKeys(fn (FinancialReceipt $r) => [
                            $r->id => $r->receipt_number.' — '.number_format((float) $r->amount, 2).' '.__('services_tracking.currency_sar'),
                        ])
                        ->all())
                    ->required(),

                Textarea::make('reason')
                    ->label(__('financial.fields.reversal_reason'))
                    ->required()
                    ->rows(2),
            ])
            ->action(function (PayrollRecord $record, array $data, Action $action): void {
                static::runWithValidationNotification($action, function () use ($record, $data): void {
                    $receipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, (int) $record->id)
                        ->findOrFail((int) $data['receipt_id']);

                    FinancialReceiptService::reverse($receipt, (string) $data['reason']);

                    Notification::make()
                        ->title(__('financial.notifications.receipt_reversed', ['number' => $receipt->receipt_number]))
                        ->success()
                        ->send();
                });
            });
    }

    public static function deductCollectionFromSalary(): Action
    {
        return Action::make('deduct_collection_from_salary')
            ->label(__('financial.payroll_actions.deduct_from_salary'))
            ->icon('heroicon-o-arrows-right-left')
            ->color('primary')
            ->modalWidth('lg')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isCollection()
                && static::canPerform('disburse_payroll_salary')
                && (float) PayrollRecordFinancialService::collectionSummary($record)['available_to_settle'] > 0
                && static::openPayrollOptions($record) !== [])
            ->form([
                Hidden::make('request_key')->default(fn () => 'ded:'.Str::uuid()->toString()),

                Select::make('payroll_record_id')
                    ->label(__('financial.payroll_actions.target_payroll_record'))
                    ->options(fn (?PayrollRecord $record): array => $record ? static::openPayrollOptions($record) : [])
                    ->required(),

                TextInput::make('amount')
                    ->label(__('financial.fields.amount'))
                    ->numeric()
                    ->minValue(0.01)
                    ->default(fn (?PayrollRecord $record): ?float => $record
                        ? (float) PayrollRecordFinancialService::collectionSummary($record)['available_to_settle']
                        : null)
                    ->required()
                    ->prefix(__('services_tracking.currency_sar')),
            ])
            ->action(function (PayrollRecord $record, array $data): void {
                $payroll = PayrollRecord::findOrFail((int) $data['payroll_record_id']);
                $requestKey = (string) ($data['request_key'] ?? ('ded:'.Str::uuid()->toString()));

                PayrollDeductionSettlementService::reserve(
                    collection: $record,
                    payroll: $payroll,
                    amount: (float) $data['amount'],
                    requestKey: $requestKey,
                );

                $freshPayroll = $payroll->refresh();
                $paySummary = PayrollRecordFinancialService::payrollSummary($freshPayroll);
                if ((float) $paySummary['available_for_disbursement'] <= 0) {
                    $reservedKeys = collect($freshPayroll->metadata['linked_collection_deductions'] ?? [])
                        ->where('status', 'reserved')
                        ->keys()
                        ->all();
                    if ($reservedKeys !== []) {
                        PayrollDeductionSettlementService::confirmBatch($freshPayroll, $reservedKeys, null, true);
                    }
                }

                Notification::make()
                    ->title(__('financial.payroll_actions.deduction_recorded'))
                    ->success()
                    ->send();
            });
    }

    public static function confirmPendingPayrollTransfer(): Action
    {
        return Action::make('confirm_pending_payroll_transfer')
            ->label(__('financial.payroll_actions.confirm_pending_transfer'))
            ->icon('heroicon-o-check-badge')
            ->color('info')
            ->modalWidth('xl')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isPayroll()
                && static::canPerform('disburse_payroll_salary')
                && ! empty($record->metadata['pending_bank_transfers']))
            ->form([
                Select::make('request_key')
                    ->label(__('financial.payroll_actions.pending_transfer_request'))
                    ->options(function (?PayrollRecord $record): array {
                        if (! $record) {
                            return [];
                        }

                        return collect($record->metadata['pending_bank_transfers'] ?? [])
                            ->mapWithKeys(fn (array $req, string $key) => [
                                $key => number_format((float) ($req['amount'] ?? 0), 2).' '.__('services_tracking.currency_sar').' — '.($req['payment_date'] ?? ''),
                            ])
                            ->all();
                    })
                    ->required(),

                TextInput::make('destination_reference')
                    ->label(__('financial.fields.destination_reference')),

                TextInput::make('reference_number')
                    ->label(__('financial.fields.reference_number'))
                    ->required(),

                FileUpload::make('proof_path')
                    ->label(__('financial.fields.proof'))
                    ->disk('public')
                    ->directory('payroll-transfers')
                    ->required(),

                Textarea::make('notes')
                    ->label(__('finance.payroll_records.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (PayrollRecord $record, array $data): void {
                $payment = PayrollDisbursementService::confirmPendingTransfer(
                    $record,
                    (string) $data['request_key'],
                    $data,
                );

                Notification::make()
                    ->title(__('financial.payroll_actions.transfer_confirmed', ['number' => $payment->payment_number]))
                    ->success()
                    ->send();
            });
    }

    public static function reversePayrollPayment(): Action
    {
        return Action::make('reverse_payroll_payment')
            ->label(__('financial.payroll_actions.reverse_payment'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalWidth('lg')
            ->visible(fn (?PayrollRecord $record): bool => $record !== null
                && $record->isPayroll()
                && $record->isManaged()
                && static::canPerform('reverse_financial_transaction')
                && static::activePayrollPayments($record)->isNotEmpty())
            ->form([
                Select::make('payment_id')
                    ->label(__('financial.payroll_actions.payment_label'))
                    ->options(fn (?PayrollRecord $record): array => static::activePayrollPayments($record)
                        ->mapWithKeys(fn (PayrollPayment $p) => [
                            $p->id => $p->payment_number.' — '.number_format((float) $p->amount, 2).' '.__('services_tracking.currency_sar'),
                        ])
                        ->all())
                    ->required(),

                Textarea::make('reason')
                    ->label(__('financial.fields.reversal_reason'))
                    ->required()
                    ->rows(2),
            ])
            ->action(function (PayrollRecord $record, array $data): void {
                $payment = PayrollPayment::where('payroll_record_id', $record->id)
                    ->findOrFail((int) $data['payment_id']);

                PayrollDisbursementService::reverse($payment, (string) $data['reason']);

                Notification::make()
                    ->title(__('financial.payroll_actions.payment_reversed'))
                    ->success()
                    ->send();
            });
    }

    public static function financialSummary(): Action
    {
        return Action::make('payroll_financial_summary')
            ->label(__('invoices.actions.financial_summary'))
            ->icon('heroicon-o-banknotes')
            ->color('gray')
            ->modalWidth('2xl')
            ->modalHeading(__('invoices.actions.financial_summary'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->visible(fn (?PayrollRecord $record): bool => $record !== null)
            ->modalContent(function (?PayrollRecord $record = null) {
                if (! $record) {
                    return '';
                }

                $currency = __('services_tracking.currency_sar');

                if ($record->isCollection()) {
                    $s = PayrollRecordFinancialService::collectionSummary($record);
                    $due = (float) $s['due'];
                    $settled = (float) $s['receipt_received'] + (float) $s['deduction_settled'];
                    $remaining = (float) $s['remaining'];
                    $percent = $due > 0 ? min(100, max(0, round(($settled / $due) * 100, 1))) : ($settled > 0 ? 100 : 0);

                    if ($due > 0 && $remaining <= 0) {
                        $status = __('financial.summary.status_fully_paid');
                        $statusTone = 'success';
                    } elseif ($settled > 0) {
                        $status = __('financial.summary.status_partial_paid');
                        $statusTone = 'warning';
                    } else {
                        $status = __('financial.summary.status_unpaid');
                        $statusTone = 'neutral';
                    }

                    return view('filament.modals.financial-summary', [
                        'progress' => [
                            'percent' => $percent,
                            'status' => $status,
                            'tone' => $statusTone,
                        ],
                        'groups' => [
                            [
                                'columns' => 3,
                                'items' => [
                                    [
                                        'label' => __('financial.payroll_actions.summary_labels.due'),
                                        'value' => number_format($due, 2),
                                        'currency' => $currency,
                                        'tone' => 'primary',
                                    ],
                                    [
                                        'label' => __('financial.payroll_actions.summary_labels.receipt_received'),
                                        'value' => number_format((float) $s['receipt_received'], 2),
                                        'currency' => $currency,
                                        'tone' => (float) $s['receipt_received'] > 0 ? 'success' : 'neutral',
                                    ],
                                    [
                                        'label' => __('financial.payroll_actions.summary_labels.deduction_settled'),
                                        'value' => number_format((float) $s['deduction_settled'], 2),
                                        'currency' => $currency,
                                        'tone' => (float) $s['deduction_settled'] > 0 ? 'info' : 'neutral',
                                    ],
                                    [
                                        'label' => __('financial.payroll_actions.summary_labels.deduction_reserved'),
                                        'value' => number_format((float) $s['deduction_reserved'], 2),
                                        'currency' => $currency,
                                        'tone' => (float) $s['deduction_reserved'] > 0 ? 'warning' : 'neutral',
                                    ],
                                    [
                                        'label' => __('financial.payroll_actions.summary_labels.custody'),
                                        'value' => number_format((float) $s['custody'], 2),
                                        'currency' => $currency,
                                        'tone' => (float) $s['custody'] > 0 ? 'warning' : 'neutral',
                                    ],
                                    [
                                        'label' => __('financial.payroll_actions.summary_labels.remaining'),
                                        'value' => number_format($remaining, 2),
                                        'currency' => $currency,
                                        'tone' => $remaining > 0 ? 'danger' : 'success',
                                    ],
                                ],
                            ],
                        ],
                        'footerNote' => __('financial.summary.description'),
                    ]);
                }

                $s = PayrollRecordFinancialService::payrollSummary($record);
                $effectiveNet = (float) $s['effective_net'];
                $paid = (float) $s['paid'];
                $remaining = (float) $s['remaining'];
                $percent = $effectiveNet > 0 ? min(100, max(0, round(($paid / $effectiveNet) * 100, 1))) : ($paid > 0 ? 100 : 0);

                if ($effectiveNet > 0 && $remaining <= 0) {
                    $status = __('financial.summary.status_fully_paid');
                    $statusTone = 'success';
                } elseif ($paid > 0) {
                    $status = __('financial.summary.status_partial_paid');
                    $statusTone = 'warning';
                } else {
                    $status = __('financial.summary.status_unpaid');
                    $statusTone = 'neutral';
                }

                return view('filament.modals.financial-summary', [
                    'progress' => [
                        'percent' => $percent,
                        'status' => $status,
                        'tone' => $statusTone,
                    ],
                    'groups' => [
                        [
                            'columns' => 3,
                            'items' => [
                                [
                                    'label' => __('financial.payroll_actions.summary_labels.gross_net'),
                                    'value' => number_format((float) $s['gross_net'], 2),
                                    'currency' => $currency,
                                    'tone' => 'primary',
                                ],
                                [
                                    'label' => __('financial.payroll_actions.summary_labels.deduction_confirmed'),
                                    'value' => number_format((float) $s['deduction_confirmed'], 2),
                                    'currency' => $currency,
                                    'tone' => (float) $s['deduction_confirmed'] > 0 ? 'warning' : 'neutral',
                                ],
                                [
                                    'label' => __('financial.payroll_actions.summary_labels.effective_net'),
                                    'value' => number_format($effectiveNet, 2),
                                    'currency' => $currency,
                                    'tone' => 'info',
                                ],
                                [
                                    'label' => __('financial.payroll_actions.summary_labels.paid'),
                                    'value' => number_format($paid, 2),
                                    'currency' => $currency,
                                    'tone' => $paid > 0 ? 'success' : 'neutral',
                                ],
                                [
                                    'label' => __('financial.payroll_actions.summary_labels.remaining'),
                                    'value' => number_format($remaining, 2),
                                    'currency' => $currency,
                                    'tone' => $remaining > 0 ? 'danger' : 'success',
                                ],
                            ],
                        ],
                    ],
                    'footerNote' => __('financial.summary.description'),
                ]);
            });
    }

    private static function runWithValidationNotification(Action $action, callable $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title($exception->validator->errors()->first())
                ->danger()
                ->send();

            $action->halt();
        }
    }

    private static function canPerform(string $permission): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isSuperAdmin() || $user->can($permission));
    }

    private static function pendingCollectionReceipts(?PayrollRecord $record)
    {
        if (! $record) {
            return collect();
        }

        return FinancialReceipt::forSource(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, (int) $record->id)
            ->pending()
            ->orderByDesc('id')
            ->get();
    }

    private static function confirmedCollectionReceipts(?PayrollRecord $record)
    {
        if (! $record) {
            return collect();
        }

        return FinancialReceipt::forSource(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, (int) $record->id)
            ->confirmed()
            ->whereDoesntHave('handoverItems', fn ($query) => $query->whereHas('handover', fn ($handoverQuery) => $handoverQuery->whereIn('status', [
                FinancialHandoverStatus::SUBMITTED->value,
                FinancialHandoverStatus::CONFIRMED->value,
            ])))
            ->orderByDesc('id')
            ->get()
            ->reject(fn (FinancialReceipt $receipt): bool => $receipt->isAllocatedTreasuryCash())
            ->values();
    }

    private static function availableHandoverReceipts(?PayrollRecord $record)
    {
        if (! $record) {
            return collect();
        }

        $user = auth()->user();
        $collectorId = ($user && ! $user->isSuperAdminOrOwner()) ? $user->employee?->id : null;

        return FinancialReceiptService::availableForSource(
            FinancialReceipt::SOURCE_PAYROLL_COLLECTION,
            (int) $record->id,
            $collectorId,
        );
    }

    private static function submittedHandovers(PayrollRecord $record)
    {
        return CashHandover::accessibleBy()
            ->submitted()
            ->whereHas('items', fn ($query) => $query->whereHas('receipt', fn ($receiptQuery) => $receiptQuery
                ->forSource(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, (int) $record->id)))
            ->orderBy('id')
            ->get();
    }

    private static function activePayrollPayments(?PayrollRecord $record)
    {
        if (! $record) {
            return collect();
        }

        return $record->payments()
            ->orderByDesc('id')
            ->get()
            ->filter(fn (PayrollPayment $p) => empty($p->metadata['reversed_at']));
    }

    /**
     * @return array<int, string>
     */
    private static function openPayrollOptions(PayrollRecord $collection): array
    {
        return PayrollRecord::query()
            ->where('company_id', $collection->company_id)
            ->where('employee_id', $collection->employee_id)
            ->where('operation_type', 'payroll')
            ->where('payment_status', '!=', PaymentStatus::PAID->value)
            ->orderByDesc('payroll_month')
            ->get()
            ->mapWithKeys(fn (PayrollRecord $p) => [
                $p->id => $p->record_number.' ('.Carbon::parse($p->payroll_month)->format('Y-m').') — '.__('financial.payroll_actions.remaining_label', ['amount' => number_format((float) $p->remaining_amount, 2)]),
            ])
            ->all();
    }
}
