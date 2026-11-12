<?php

namespace App\Services;

use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Coordinates the single-save flow of creating a service transaction with an
 * optional client collection and cash handover request. The receipt and
 * handover inputs are carried as separate form data, never on the transaction
 * model; authorization, money math and writes stay inside
 * FinancialReceiptService and CashHandoverService.
 */
class ServiceReceiptCreationService
{
    /**
     * Form keys carrying the collection inputs, stripped before the
     * transaction model is created.
     */
    private const RECEIPT_KEYS = [
        'receipt_received',
        'receipt_amount',
        'receipt_payment_method',
        'receipt_payer_name',
        'receipt_collector_employee_id',
        'receipt_reference_number',
        'receipt_destination_type',
        'receipt_destination_reference',
        'receipt_proof_path',
        'receipt_received_at',
        'receipt_request_handover',
        'receipt_request_key',
        'handover_request_key',
    ];

    /**
     * Split the form state into transaction data and a normalized receipt
     * payload. The submission key moves into the transaction metadata so a
     * re-submitted attempt can be detected even without a receipt.
     *
     * @param  array<string, mixed>  $data
     * @return array{transaction: array<string, mixed>, receipt: ?array<string, mixed>}
     */
    public static function extract(array $data): array
    {
        $receipt = [];

        foreach (self::RECEIPT_KEYS as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $targetKey = str_starts_with($key, 'receipt_')
                ? substr($key, strlen('receipt_'))
                : $key;

            $receipt[$targetKey] = $data[$key];
            unset($data[$key]);
        }

        $submissionKey = (string) ($data['submission_key'] ?? '');
        unset($data['submission_key']);

        if ($submissionKey !== '') {
            $data['metadata'] = array_merge($data['metadata'] ?? [], ['submission_key' => $submissionKey]);
        }

        $payload = null;
        $rawMethod = $receipt['payment_method'] ?? null;
        $hasMethod = $rawMethod instanceof FinancialPaymentMethod || ! empty($rawMethod);

        if (! empty($receipt['received'])
            && (float) ($receipt['amount'] ?? 0) > 0
            && $hasMethod
            && ! empty($receipt['request_key'])) {
            $payload = $receipt;
        }

        return ['transaction' => $data, 'receipt' => $payload];
    }

    /**
     * Block a re-submitted save attempt from creating a second transaction:
     * the request keys are stable per attempt and unique-indexed.
     * Uses an atomic cache lock to prevent concurrent duplicate submissions.
     *
     * @param  array<string, mixed>|null  $receipt
     */
    public static function assertNewAttempt(?array $receipt, ?string $submissionKey): void
    {
        $submissionKey = trim((string) $submissionKey);

        if ($submissionKey !== '') {
            $lock = Cache::lock('service_tx_submission:'.$submissionKey, 15);

            if (! $lock->get()) {
                throw ValidationException::withMessages([
                    'submission_key' => __('financial.errors.duplicate_submission'),
                    'receipt_received' => __('financial.errors.duplicate_submission'),
                ]);
            }

            if (ServiceTransaction::where('metadata->submission_key', $submissionKey)->exists()) {
                throw ValidationException::withMessages([
                    'submission_key' => __('financial.errors.duplicate_submission'),
                    'receipt_received' => __('financial.errors.duplicate_submission'),
                ]);
            }
        }

        $requestKey = $receipt['request_key'] ?? null;

        if ($requestKey && FinancialReceipt::where('request_key', $requestKey)->exists()) {
            throw ValidationException::withMessages([
                'receipt_received' => __('financial.errors.duplicate_submission'),
            ]);
        }
    }

    /**
     * Record the collection on a freshly created transaction, confirming and
     * requesting the handover as the user's permissions allow. Everything runs
     * inside the caller's transaction so any failure rolls back the whole save.
     *
     * @param  array<string, mixed>|null  $receipt
     * @return array<string, mixed>|null
     */
    public static function process(ServiceTransaction $transaction, ?array $receipt): ?array
    {
        if ($receipt === null) {
            return null;
        }

        $user = auth()->user();

        $canConfirm = (bool) ($user?->can('confirm_service_receipt'));
        $canRequestHandover = (bool) ($user?->can('request_cash_handover'));
        $wantsHandover = ! empty($receipt['request_handover']) && $canRequestHandover;

        $rawMethod = $receipt['payment_method'] ?? null;
        $method = $rawMethod instanceof FinancialPaymentMethod
            ? $rawMethod
            : (is_string($rawMethod) || is_numeric($rawMethod) ? FinancialPaymentMethod::tryFrom((string) $rawMethod) : null);

        if (! $method instanceof FinancialPaymentMethod) {
            throw ValidationException::withMessages([
                'receipt_payment_method' => __('financial.errors.invalid_payment_method'),
            ]);
        }

        $collectorId = $transaction->executed_by;

        if ($method->isCash() && ! $collectorId) {
            throw ValidationException::withMessages([
                'data.executed_by' => __('financial.errors.executor_required_for_cash_receipt'),
            ]);
        }

        $payerName = $transaction->display_beneficiary;

        if ($method->isCash() && (blank($payerName) || $payerName === '—')) {
            throw ValidationException::withMessages([
                'data.beneficiary_name' => __('financial.errors.payer_required'),
            ]);
        }

        $recordData = [
            'request_key' => (string) $receipt['request_key'],
            'amount' => round((float) $receipt['amount'], 2),
            'payment_method' => $method->value,
            'payer_name' => $payerName,
            'collector_employee_id' => $collectorId,
            'reference_number' => $receipt['reference_number'] ?? null,
            'proof_path' => $receipt['proof_path'] ?? null,
            'destination_type' => $receipt['destination_type'] ?? null,
            'destination_reference' => $receipt['destination_reference'] ?? null,
            'received_at' => $receipt['received_at'] ?? now(),
        ];

        $nonCashIncomplete = ! $method->isCash()
            && (empty($recordData['reference_number'])
                || empty($recordData['destination_type'])
                || empty($recordData['destination_reference'])
                || empty($recordData['proof_path']));

        if ($canConfirm && ! $nonCashIncomplete) {
            $recordData['confirm'] = true;
        }

        // Recorder cannot confirm: attach the explicit handover request as an
        // intent on the pending receipt. FinancialReceiptService::confirm()
        // converts it once into a real handover request for the original
        // requester's permissions and scope.
        if (! $canConfirm && $method->isCash() && $wantsHandover) {
            $recordData['metadata'] = [
                'handover_intent' => [
                    'request_key' => (string) ($receipt['handover_request_key'] ?? CashHandoverService::newRequestKey()),
                    'amount' => round((float) $receipt['amount'], 2),
                    'requested_by' => $user->id,
                    'requested_at' => now()->toDateTimeString(),
                ],
            ];
        }

        $receiptModel = FinancialReceiptService::record($transaction, $recordData);

        $confirmed = $receiptModel->status === FinancialReceiptStatus::CONFIRMED;

        $handover = null;

        if ($confirmed && $method->isCash() && $wantsHandover) {
            $handover = CashHandoverService::requestForTransaction($transaction, [
                $receiptModel->id => round((float) $receiptModel->amount, 2),
            ], [
                'request_key' => (string) ($receipt['handover_request_key'] ?? CashHandoverService::newRequestKey()),
            ]);
        }

        return [
            'transaction' => $transaction,
            'receipt' => $receiptModel,
            'confirmed' => $confirmed,
            'handover' => $handover,
            'handover_intent' => isset($recordData['metadata']['handover_intent']),
            'non_cash_needs_proof' => $nonCashIncomplete,
        ];
    }

    /**
     * Show the post-save state of the collection to the user.
     *
     * @param  array<string, mixed>|null  $outcome
     */
    public static function notify(?array $outcome): void
    {
        if ($outcome === null) {
            return;
        }

        [$body, $color] = match (true) {
            $outcome['confirmed'] && $outcome['handover'] !== null => [
                __('financial.creation_receipt.confirmed_with_handover'), 'success',
            ],
            $outcome['confirmed'] => [__('financial.creation_receipt.confirmed'), 'success'],
            $outcome['handover_intent'] => [__('financial.creation_receipt.pending_with_intent'), 'warning'],
            $outcome['non_cash_needs_proof'] => [__('financial.creation_receipt.pending_needs_proof'), 'warning'],
            default => [__('financial.creation_receipt.pending'), 'warning'],
        };

        Notification::make()
            ->title(__('financial.actions.record_receipt'))
            ->body($body)
            ->color($color)
            ->send();
    }
}
