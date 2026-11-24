<?php

namespace App\Filament\Components;

use App\Enums\FinancialPaymentMethod;
use App\Models\CashHandover;
use App\Models\Employee;
use App\Models\FinancialReceipt;
use App\Models\Invoice;
use App\Services\BarcodeService;
use App\Services\CashHandoverService;
use App\Services\FinancialReceiptService;
use App\Services\InvoiceFinancialService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Validation\ValidationException;

class InvoiceFinancialActions
{
    public static function recordReceipt(): Action
    {
        return Action::make('record_invoice_receipt')
            ->label(__('invoices.actions.record_receipt'))
            ->icon('heroicon-o-receipt-percent')
            ->color('success')
            ->modalWidth('2xl')
            ->visible(fn (?Invoice $record): bool => static::canCollect($record))
            ->form([
                Hidden::make('request_key')->default(fn () => FinancialReceiptService::newRequestKey()),

                Grid::make(['default' => 1, 'sm' => 2])
                    ->schema([
                        TextInput::make('amount')
                            ->label(__('financial.fields.amount'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->default(fn (?Invoice $record): ?float => $record && (float) $record->remaining_amount > 0
                                ? round((float) $record->remaining_amount, 2)
                                : null)
                            ->prefix(__('services_tracking.currency_sar')),

                        Select::make('payment_method')
                            ->label(__('financial.fields.payment_method'))
                            ->options(FinancialPaymentMethod::class)
                            ->default(FinancialPaymentMethod::CASH->value)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $value = $state instanceof FinancialPaymentMethod ? $state->value : $state;

                                if ($value === FinancialPaymentMethod::CASH->value) {
                                    $set('destination_type', null);
                                    $set('destination_reference', null);
                                    $set('reference_number', null);
                                    $set('proof_path', null);
                                }
                            }),

                        Select::make('collector_employee_id')
                            ->label(__('financial.fields.collector'))
                            ->options(fn (?Invoice $record): array => static::employees($record))
                            ->default(fn (): ?int => auth()->user()?->employee?->id)
                            ->disabled(fn (): bool => ! (auth()->user()?->isSuperAdminOrOwner() ?? false))
                            ->searchable(fn (): bool => auth()->user()?->isSuperAdminOrOwner() ?? false)
                            ->required(fn (Get $get): bool => static::isCash($get))
                            ->dehydrated(),

                        TextInput::make('payer_name')
                            ->label(__('financial.fields.payer_name'))
                            ->default(fn (?Invoice $record): ?string => $record?->customer?->name ?? $record?->employee?->user?->name)
                            ->dehydrated()
                            ->required(fn (Get $get): bool => static::isCash($get)),

                        Select::make('destination_type')
                            ->label(__('financial.fields.destination_type'))
                            ->options(__('financial.destination_types'))
                            ->visible(fn (Get $get): bool => static::isNonCash($get)),

                        TextInput::make('destination_reference')
                            ->label(__('financial.fields.destination_reference'))
                            ->visible(fn (Get $get): bool => static::isNonCash($get)),

                        TextInput::make('reference_number')
                            ->label(__('financial.fields.reference_number'))
                            ->visible(fn (Get $get): bool => static::isNonCash($get)),

                        FileUpload::make('proof_path')
                            ->label(__('financial.fields.proof'))
                            ->disk('public')
                            ->directory('financial-receipts')
                            ->openable()
                            ->downloadable()
                            ->visible(fn (Get $get): bool => static::isNonCash($get)),

                        Textarea::make('notes')
                            ->label(__('invoices.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (Invoice $record, array $data): void {
                $receipt = FinancialReceiptService::recordFor(
                    FinancialReceipt::SOURCE_INVOICE,
                    (int) $record->id,
                    $data,
                );

                Notification::make()
                    ->title(__('financial.notifications.receipt_recorded', ['number' => $receipt->receipt_number]))
                    ->success()
                    ->send();
            });
    }

    public static function confirmReceipt(): Action
    {
        return Action::make('confirm_invoice_receipt')
            ->label(__('invoices.actions.confirm_receipt'))
            ->icon('heroicon-o-check-badge')
            ->color('info')
            ->modalWidth('xl')
            ->visible(fn (?Invoice $record): bool => $record !== null
                && ! $record->isServiceMirror()
                && static::canPerform('confirm_service_receipt')
                && static::pendingReceipts($record)->isNotEmpty())
            ->form([
                Select::make('receipt_id')
                    ->label(__('financial.fields.receipt'))
                    ->options(fn (?Invoice $record): array => static::pendingReceipts($record)
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
            ->action(function (Invoice $record, array $data): void {
                $receipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $record->id)
                    ->findOrFail((int) $data['receipt_id']);

                FinancialReceiptService::confirm($receipt, $data);

                Notification::make()
                    ->title(__('financial.notifications.receipt_confirmed', ['number' => $receipt->receipt_number]))
                    ->success()
                    ->send();
            });
    }

    public static function requestHandover(): Action
    {
        return Action::make('request_invoice_handover')
            ->label(__('invoices.actions.request_handover'))
            ->icon('heroicon-o-inbox-arrow-down')
            ->color('warning')
            ->modalWidth('xl')
            ->visible(fn (?Invoice $record): bool => $record !== null
                && ! $record->isServiceMirror()
                && static::canPerform('request_cash_handover')
                && static::availableHandoverReceipts($record)->isNotEmpty())
            ->form([
                Hidden::make('request_key')->default(fn () => CashHandoverService::newRequestKey()),

                TextEntry::make('custody_info')
                    ->label(__('financial.fields.custody_available'))
                    ->state(function (?Invoice $record): string {
                        if (! $record) {
                            return '0.00';
                        }
                        $total = static::availableHandoverReceipts($record)->sum(fn (FinancialReceipt $r) => $r->availableForHandover());

                        return number_format((float) $total, 2).' '.__('services_tracking.currency_sar');
                    }),

                Textarea::make('notes')
                    ->label(__('invoices.fields.notes'))
                    ->rows(2),
            ])
            ->action(function (Invoice $record, array $data): void {
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
        return Action::make('invoice_handover_qr')
            ->label(__('financial.actions.handover_receipts_qr'))
            ->icon('heroicon-o-qr-code')
            ->color('warning')
            ->visible(fn (?Invoice $record): bool => $record !== null
                && ! $record->isServiceMirror()
                && static::submittedHandovers($record)->isNotEmpty())
            ->modalWidth('2xl')
            ->modalHeading(__('financial.handover_qr.modal_title'))
            ->modalDescription(__('financial.handover_qr.modal_desc'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->modalContent(function (?Invoice $record = null) {
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

    public static function reverseReceipt(): Action
    {
        return Action::make('reverse_invoice_receipt')
            ->label(__('invoices.actions.reverse_receipt'))
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalWidth('lg')
            ->visible(fn (?Invoice $record): bool => $record !== null
                && ! $record->isServiceMirror()
                && static::canPerform('reverse_financial_transaction')
                && static::confirmedReceipts($record)->isNotEmpty())
            ->form([
                Select::make('receipt_id')
                    ->label(__('financial.fields.receipt'))
                    ->options(fn (?Invoice $record): array => static::confirmedReceipts($record)
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
            ->action(function (Invoice $record, array $data): void {
                $receipt = FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $record->id)
                    ->findOrFail((int) $data['receipt_id']);

                FinancialReceiptService::reverse($receipt, (string) $data['reason']);

                Notification::make()
                    ->title(__('financial.notifications.receipt_reversed', ['number' => $receipt->receipt_number]))
                    ->success()
                    ->send();
            });
    }

    public static function financialSummary(): Action
    {
        return Action::make('invoice_financial_summary')
            ->label(__('invoices.actions.financial_summary'))
            ->icon('heroicon-o-banknotes')
            ->color('gray')
            ->modalWidth('2xl')
            ->modalHeading(__('invoices.actions.financial_summary'))
            ->modalDescription(fn (?Invoice $record): ?string => $record?->invoice_number)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('services_tracking.custody.close'))
            ->visible(fn (?Invoice $record): bool => $record !== null)
            ->modalContent(function (?Invoice $record = null) {
                if (! $record) {
                    return '';
                }

                if ($record->isServiceMirror()) {
                    return view('filament.modals.financial-summary', [
                        'notice' => [
                            'tone' => 'info',
                            'text' => __('invoices.notices.service_mirror'),
                        ],
                        'groups' => [],
                    ]);
                }

                $s = InvoiceFinancialService::summary($record);
                $currency = __('services_tracking.currency_sar');

                $due = (float) $s['due'];
                $applied = (float) $s['applied'];
                $remaining = (float) $s['remaining'];
                $custody = (float) $s['custody'];
                $treasury = (float) $s['treasury_received'];
                $pending = (float) $s['pending_receipts'];

                $percent = $due > 0
                    ? min(100, max(0, round(($applied / $due) * 100, 1)))
                    : ($applied > 0 ? 100 : 0);

                if ($due > 0 && $remaining <= 0) {
                    $status = __('financial.summary.status_fully_paid');
                    $statusTone = 'success';
                } elseif ($applied > 0) {
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
                            'title' => __('financial.summary.group_client'),
                            'columns' => 3,
                            'items' => [
                                [
                                    'label' => __('invoices.fields.total_amount'),
                                    'value' => number_format($due, 2),
                                    'currency' => $currency,
                                    'tone' => 'primary',
                                ],
                                [
                                    'label' => __('invoices.fields.paid_amount'),
                                    'value' => number_format($applied, 2),
                                    'currency' => $currency,
                                    'tone' => $applied > 0 ? 'success' : 'neutral',
                                ],
                                [
                                    'label' => __('invoices.fields.remaining_amount'),
                                    'value' => number_format($remaining, 2),
                                    'currency' => $currency,
                                    'tone' => $remaining > 0 ? 'danger' : 'success',
                                ],
                            ],
                        ],
                        [
                            'title' => __('financial.summary.group_custody'),
                            'columns' => 3,
                            'items' => [
                                [
                                    'label' => __('financial.summary.custody'),
                                    'value' => number_format($custody, 2),
                                    'currency' => $currency,
                                    'tone' => $custody > 0 ? 'warning' : 'neutral',
                                ],
                                [
                                    'label' => __('financial.summary.treasury_received'),
                                    'value' => number_format($treasury, 2),
                                    'currency' => $currency,
                                    'tone' => $treasury > 0 ? 'info' : 'neutral',
                                ],
                                [
                                    'label' => __('financial.summary.pending_receipts'),
                                    'value' => number_format($pending, 2),
                                    'currency' => $currency,
                                    'tone' => $pending > 0 ? 'warning' : 'neutral',
                                ],
                            ],
                        ],
                    ],
                    'footerNote' => __('financial.summary.description'),
                ]);
            });
    }

    private static function paymentMethodValue(Get $get): ?string
    {
        $method = $get('payment_method');

        return $method instanceof FinancialPaymentMethod
            ? $method->value
            : (is_string($method) ? $method : null);
    }

    private static function isCash(Get $get): bool
    {
        return static::paymentMethodValue($get) === FinancialPaymentMethod::CASH->value;
    }

    private static function isNonCash(Get $get): bool
    {
        $value = static::paymentMethodValue($get);

        return filled($value) && $value !== FinancialPaymentMethod::CASH->value;
    }

    private static function canCollect(?Invoice $record): bool
    {
        if (! $record || $record->isServiceMirror()) {
            return false;
        }

        if (! static::canPerform('record_service_receipt')) {
            return false;
        }

        if (! $record->isManaged() && $record->hasFinancialImpact()) {
            return false;
        }

        return round((float) $record->remaining_amount, 2) > 0;
    }

    private static function canPerform(string $permission): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isSuperAdmin() || $user->can($permission));
    }

    private static function pendingReceipts(?Invoice $record)
    {
        if (! $record) {
            return collect();
        }

        return FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $record->id)
            ->pending()
            ->orderByDesc('id')
            ->get();
    }

    private static function confirmedReceipts(?Invoice $record)
    {
        if (! $record) {
            return collect();
        }

        return FinancialReceipt::forSource(FinancialReceipt::SOURCE_INVOICE, (int) $record->id)
            ->confirmed()
            ->orderByDesc('id')
            ->get();
    }

    private static function availableHandoverReceipts(?Invoice $record)
    {
        if (! $record) {
            return collect();
        }

        $user = auth()->user();
        $collectorId = ($user && ! $user->isSuperAdminOrOwner()) ? $user->employee?->id : null;

        return FinancialReceiptService::availableForSource(
            FinancialReceipt::SOURCE_INVOICE,
            (int) $record->id,
            $collectorId,
        );
    }

    private static function submittedHandovers(Invoice $record)
    {
        return CashHandover::accessibleBy()
            ->submitted()
            ->whereHas('items', fn ($query) => $query->whereHas('receipt', fn ($receiptQuery) => $receiptQuery
                ->forSource(FinancialReceipt::SOURCE_INVOICE, (int) $record->id)))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private static function employees(?Invoice $record): array
    {
        if (! $record) {
            return [];
        }

        return Employee::query()
            ->with('user')
            ->where('company_id', $record->company_id)
            ->when($record->branch_id, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->get()
            ->mapWithKeys(fn (Employee $emp) => [$emp->id => $emp->user?->name ?? ('#'.$emp->id)])
            ->all();
    }
}
