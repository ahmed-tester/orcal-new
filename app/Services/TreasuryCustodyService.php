<?php

namespace App\Services;

use App\Enums\PaymentItemCategory;
use App\Enums\TreasuryTransactionType;
use App\Models\PaymentCollectionItem;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\Facades\DB;

class TreasuryCustodyService
{
    /**
     * Parse raw scanned code into transaction number and optional PIN.
     * Supports formats like: "رقم المعاملة: SRV-C1-0004 - كود التوقيع: 2796", "SRV-C1-0004 - PIN: 2796", "SRV-C1-0004|2796".
     *
     * @return array{code: string, pin: ?string}
     */
    public static function parseScannedCode(string $raw): array
    {
        $raw = trim($raw);

        // Normalize Arabic digits
        $raw = strtr($raw, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        // Format 1: Parentheses format: "رقم المعاملة: SRV-XXX (كود التوقيع: 1234)" or "SRV-XXX (PIN: 1234)" or "SRV-XXX (1234)"
        if (preg_match('/(?:رقم\s*المعاملة|المعاملة|Transaction)?\s*:?\s*([A-Za-z0-9\-]+)\s*[\(\[\{]\s*(?:كود\s*التوقيع|كود|توقيع|PIN|pin)?\s*:?\s*(\d{3,8})\s*[\)\]\}]/u', $raw, $matches)) {
            return [
                'code' => trim($matches[1]),
                'pin' => trim($matches[2]),
            ];
        }

        // Format 2: Arabic or English labeled format with dash/pipe/colon/slash: "SRV-XXX - كود التوقيع: 1234" or "SRV-XXX - PIN: 1234"
        if (preg_match('/(?:رقم\s*المعاملة|المعاملة|Transaction)?\s*:?\s*([A-Za-z0-9\-]+)\s*[-|:\/]\s*(?:كود\s*التوقيع|كود|توقيع|PIN|pin)\s*:?\s*(\d{3,8})/ui', $raw, $matches)) {
            return [
                'code' => trim($matches[1]),
                'pin' => trim($matches[2]),
            ];
        }

        // Format 3: Explicit Pipe delimiter: "SRV-XXX|1234" or "SRV-XXX | 1234" (pipes never exist inside transaction codes)
        if (str_contains($raw, '|')) {
            [$c, $p] = explode('|', $raw, 2);
            $c = preg_replace('/^(?:رقم\s*المعاملة|المعاملة|Transaction)\s*:?\s*/u', '', trim($c));
            $p = trim($p);
            if (preg_match('/^\D*(\d{3,8})\D*$/', $p, $pMatches)) {
                return [
                    'code' => trim($c),
                    'pin' => $pMatches[1],
                ];
            }
        }

        $cleanCode = preg_replace('/^(?:رقم\s*المعاملة|المعاملة|Transaction)\s*:?\s*/u', '', $raw);

        return [
            'code' => trim($cleanCode),
            'pin' => null,
        ];
    }

    /**
     * Look up a ServiceTransaction by transaction number and return its preview info.
     */
    public static function lookupTransaction(string $code, ?int $companyId = null): ?array
    {
        $parsed = static::parseScannedCode($code);
        $code = $parsed['code'];

        if (empty($code)) {
            return null;
        }

        $query = ServiceTransaction::where('transaction_number', $code);

        $authorizedIds = CompanyFormService::getAuthorizedCompanyIds();
        if (! empty($authorizedIds)) {
            $query->whereIn('company_id', $authorizedIds);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $tx = $query->first();
        if (! $tx) {
            return null;
        }

        return [
            'id' => $tx->id,
            'transaction_number' => $tx->transaction_number,
            'service_name' => $tx->display_service_name,
            'beneficiary' => $tx->display_beneficiary,
            'employee' => $tx->executor?->user?->name ?? '—',
            'amount' => (float) $tx->paid_amount,
            'custody_status' => $tx->metadata['custody_status'] ?? 'on_employee',
            'is_received' => in_array($tx->metadata['custody_status'] ?? '', ['in_treasury', 'settled'], true),
        ];
    }

    /**
     * Process cash custody handover from employee to treasury.
     */
    public static function receiveCustody(
        string $code,
        ?int $receivedBy = null,
        ?string $receiptNote = null,
        ?string $signatureCode = null,
    ): array {
        $parsed = static::parseScannedCode($code);
        $code = $parsed['code'];
        if (empty($signatureCode) && ! empty($parsed['pin'])) {
            $signatureCode = $parsed['pin'];
        }

        if (empty($code)) {
            return [
                'success' => false,
                'message' => __('services_tracking.custody.transaction_not_found'),
            ];
        }

        return DB::transaction(function () use ($code, $receivedBy, $receiptNote, $signatureCode) {
            $query = ServiceTransaction::where('transaction_number', $code)->lockForUpdate();

            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds();
            if (! empty($authorizedIds)) {
                $query->whereIn('company_id', $authorizedIds);
            }

            $serviceTx = $query->first();

            if (! $serviceTx) {
                return [
                    'success' => false,
                    'message' => __('services_tracking.custody.transaction_not_found'),
                ];
            }

            if ($serviceTx->isFinancialManaged()) {
                return [
                    'success' => false,
                    'message' => __('financial.errors.use_managed_handover'),
                    'transaction' => $serviceTx,
                ];
            }

            $currentStatus = $serviceTx->metadata['custody_status'] ?? 'on_employee';
            if (in_array($currentStatus, ['in_treasury', 'settled'], true)) {
                return [
                    'success' => false,
                    'message' => __('services_tracking.custody.already_received'),
                    'transaction' => $serviceTx,
                ];
            }

            if ($currentStatus === 'not_applicable') {
                return [
                    'success' => false,
                    'message' => __('services_tracking.custody.not_cash_bearer'),
                    'transaction' => $serviceTx,
                ];
            }

            // Verify custody holder authorization
            $currentUser = auth()->user();
            $isSuper = $currentUser && method_exists($currentUser, 'isSuperAdminOrOwner') && $currentUser->isSuperAdminOrOwner();
            $isExecutor = $currentUser && $currentUser->employee && (int) $serviceTx->executed_by === (int) $currentUser->employee->id;

            if (! $isSuper && ! $isExecutor) {
                $expectedCode = (string) ($serviceTx->metadata['employee_signature_code'] ?? str_pad((string) ((($serviceTx->id ?: 1) * 73) % 9000 + 1000), 4, '0', STR_PAD_LEFT));
                $rawProvided = trim((string) $signatureCode);
                $providedCode = strtr($rawProvided, [
                    '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                    '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
                ]);

                if (empty($providedCode)) {
                    return [
                        'success' => false,
                        'message' => __('services_tracking.custody.signature_code_required'),
                        'transaction' => $serviceTx,
                    ];
                }

                if ($providedCode !== $expectedCode) {
                    return [
                        'success' => false,
                        'message' => __('services_tracking.custody.invalid_signature_code'),
                        'transaction' => $serviceTx,
                    ];
                }
            }

            $amount = (float) $serviceTx->paid_amount;
            if ($amount <= 0) {
                $amount = (float) $serviceTx->total_amount;
            }

            $companyId = $serviceTx->company_id
                ?? auth()->user()?->company_id
                ?? CompanyFormService::getDefaultCompanyId();
            if (empty($companyId)) {
                return [
                    'success' => false,
                    'message' => __('services_tracking.custody.missing_company'),
                    'transaction' => $serviceTx,
                ];
            }

            $payerName = $serviceTx->executor?->user?->name ?? $serviceTx->beneficiary_name ?? __('services_tracking.custody.cash_payer');
            $description = __('services_tracking.custody.treasury_description', [
                'number' => $serviceTx->transaction_number,
                'service' => $serviceTx->display_service_name,
            ]);

            // Every treasury transaction must be linked to a PaymentCollectionItem - unified services item for custody handover
            $custodyItem = PaymentCollectionItem::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'main_item' => PaymentItemCategory::SERVICES,
                    'sub_item' => __('services_tracking.custody.payment_item_name'),
                ],
                ['service_price' => 0],
            );

            $treasuryTx = TreasuryTransaction::create([
                'company_id' => $companyId,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => TreasuryTransactionType::INCOMING,
                'payment_collection_item_id' => $custodyItem->id,
                'recipient_or_payer' => $payerName,
                'amount' => $amount,
                'description' => $description,
                'executed_by' => $serviceTx->executed_by,
                'metadata' => [
                    'source' => 'service_custody_handover',
                    'service_transaction_id' => $serviceTx->id,
                    'transaction_number' => $serviceTx->transaction_number,
                    'received_by_user_id' => $receivedBy ?? auth()->id(),
                    'received_at' => now()->toDateTimeString(),
                    'receipt_note' => $receiptNote,
                ],
            ]);

            $meta = $serviceTx->metadata ?? [];
            $meta['custody_status'] = 'in_treasury';
            $meta['custody_note'] = $receiptNote;
            $meta['treasury_transaction_id'] = $treasuryTx->id;
            $meta['treasury_received_at'] = now()->toDateTimeString();
            $meta['treasury_received_by'] = $receivedBy ?? auth()->id();
            $serviceTx->metadata = $meta;
            $serviceTx->save();

            OperationsAuditService::record(
                companyId: $companyId,
                transactionId: $serviceTx->id,
                actionType: 'custody_received',
                oldStatus: 'employee_custody',
                newStatus: $serviceTx->workflow_status,
                payload: [
                    'amount' => $amount,
                    'treasury_transaction_id' => $treasuryTx->id,
                    'receipt_note' => $receiptNote,
                ],
                userId: $receivedBy ?? auth()->id(),
            );

            OperationsReconciliationService::reconcile($serviceTx);

            OperationsNotificationService::notifyCustodyReceived(
                transaction: $serviceTx,
                amount: $amount,
                receivedByUserId: $receivedBy,
            );

            return [
                'success' => true,
                'message' => __('services_tracking.custody.receive_success'),
                'transaction' => $serviceTx,
                'treasury_transaction' => $treasuryTx,
            ];
        });
    }

    /**
     * Build the shared "receive custody" header action used by treasury screens.
     */
    public static function receiveCustodyHeaderAction(): Action
    {
        return Action::make('receive_custody')
            ->label(__('services_tracking.custody.receive_custody_btn'))
            ->icon('heroicon-o-qr-code')
            ->color('success')
            ->modalHeading(__('services_tracking.custody.receive_modal_title'))
            ->modalDescription(__('services_tracking.custody.receive_modal_desc'))
            ->modalSubmitActionLabel(__('services_tracking.custody.confirm_receipt_btn'))
            ->form([
                Grid::make(2)
                    ->schema([
                        TextInput::make('transaction_code')
                            ->label(__('services_tracking.fields.transaction_number'))
                            ->placeholder(__('services_tracking.custody.code_placeholder'))
                            ->prefixIcon('heroicon-o-qr-code')
                            ->required()
                            ->autofocus()
                            ->live(debounce: 400)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $parsed = static::parseScannedCode((string) $state);
                                    if (! empty($parsed['pin'])) {
                                        $set('transaction_code', $parsed['code']);
                                        $set('signature_code', $parsed['pin']);
                                        $state = $parsed['code'];
                                    }
                                    $preview = static::lookupTransaction($state);
                                    if ($preview) {
                                        $set('preview_amount', number_format($preview['amount'], 2).' '.__('services_tracking.currency_sar'));
                                        $set('preview_service', $preview['service_name']);
                                        $set('preview_employee', $preview['employee']);
                                        $set('preview_status', $preview['is_received']
                                            ? __('services_tracking.custody.preview_received')
                                            : __('services_tracking.custody.preview_ready'));
                                    } else {
                                        $set('preview_amount', null);
                                        $set('preview_service', null);
                                        $set('preview_employee', null);
                                        $set('preview_status', null);
                                    }
                                }
                            }),

                        TextInput::make('signature_code')
                            ->label(__('services_tracking.dashboard.employee_signature'))
                            ->placeholder('4 أرقام (مثال: 8492)')
                            ->prefixIcon('heroicon-o-key')
                            ->maxLength(10)
                            ->helperText(__('services_tracking.custody.signature_code_hint'))
                            ->required(fn () => ! (method_exists(auth()->user(), 'isSuperAdminOrOwner') && auth()->user()->isSuperAdminOrOwner())),
                    ]),

                Textarea::make('custody_note')
                    ->label(__('services_tracking.custody.receipt_note'))
                    ->placeholder(__('services_tracking.custody.receipt_note_placeholder'))
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),

                Grid::make(3)
                    ->visible(fn (callable $get) => filled($get('preview_service')))
                    ->schema([
                        TextInput::make('preview_service')
                            ->label(__('services_tracking.fields.service'))
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('preview_employee')
                            ->label(__('services_tracking.custody.employee_holder'))
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('preview_amount')
                            ->label(__('services_tracking.custody.cash_amount'))
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ])
            ->action(function (array $data) {
                $result = static::receiveCustody(
                    code: $data['transaction_code'],
                    receiptNote: $data['custody_note'] ?? null,
                    signatureCode: $data['signature_code'] ?? null,
                );

                if (! empty($result['success'])) {
                    $result['message'] && Notification::make()
                        ->title($result['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title($result['message'])
                        ->danger()
                        ->send();
                }
            });
    }
}
