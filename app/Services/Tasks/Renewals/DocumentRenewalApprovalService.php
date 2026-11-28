<?php

namespace App\Services\Tasks\Renewals;

use App\Enums\FinancialCashDirection;
use App\Enums\PaymentItemCategory;
use App\Enums\TaskStatus;
use App\Filament\Pages\DocumentRenewals;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\TaskAssignmentItem;
use App\Models\TaskAssignmentItemReview;
use App\Models\TreasuryTransaction;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\FinancialTreasuryService;
use App\Services\OperationsAuditService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class DocumentRenewalApprovalService
{
    /**
     * Submit renewal invoice + proposed new expiry date for treasury review.
     * Does NOT deduct treasury or mutate the official source date yet.
     *
     * @param  array{
     *     fee_mode: string,
     *     amount?: float|int|string|null,
     *     new_expiry_date: string,
     *     payment_method?: ?string,
     *     reference_number?: ?string,
     *     proof_path?: ?string,
     *     document_attachment?: ?string,
     *     notes?: ?string,
     *     already_paid_treasury_transaction_id?: ?int
     * }  $payload
     */
    public static function submitForTreasury(User $user, UnifiedDeficiencyTask $request, array $payload): UnifiedDeficiencyTask
    {
        $feeMode = (string) ($payload['fee_mode'] ?? '');
        if (! in_array($feeMode, [Invoice::RENEWAL_FEE_WITH_FEE, Invoice::RENEWAL_FEE_NO_FEE], true)) {
            throw ValidationException::withMessages([
                'fee_mode' => __('document_renewals.errors.invalid_fee_mode'),
            ]);
        }

        $rawNewExpiry = trim((string) ($payload['new_expiry_date'] ?? ''));
        if ($rawNewExpiry === '') {
            throw ValidationException::withMessages([
                'new_expiry_date' => __('document_renewals.errors.new_expiry_required'),
            ]);
        }

        try {
            $newExpiryDate = Carbon::parse($rawNewExpiry)->toDateString();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'new_expiry_date' => __('document_renewals.errors.invalid_new_expiry'),
            ]);
        }

        $amount = round((float) ($payload['amount'] ?? 0), 2);
        $alreadyPaidTxId = filled($payload['already_paid_treasury_transaction_id'] ?? null)
            ? (int) $payload['already_paid_treasury_transaction_id']
            : null;

        if ($feeMode === Invoice::RENEWAL_FEE_NO_FEE) {
            if ($amount !== 0.0 || $alreadyPaidTxId !== null) {
                throw ValidationException::withMessages([
                    'amount' => __('document_renewals.errors.no_fee_must_be_zero'),
                ]);
            }
        } else {
            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('document_renewals.errors.positive_fee_required'),
                ]);
            }
        }

        return DB::transaction(function () use ($user, $request, $feeMode, $amount, $newExpiryDate, $alreadyPaidTxId, $payload): UnifiedDeficiencyTask {
            // Fixed lock order: Request -> Invoice -> TaskAssignmentItem
            $locked = UnifiedDeficiencyTask::query()
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if (! DocumentRenewalAccessService::canEditRenewalWork($user, $locked)) {
                throw new AuthorizationException(__('document_renewals.errors.cannot_submit'));
            }

            $documentType = (string) ($locked->metadata['document_type'] ?? '');
            $cycleExpiry = (string) ($locked->metadata['cycle_expiry_date'] ?? '');

            if ($newExpiryDate <= $cycleExpiry) {
                throw ValidationException::withMessages([
                    'new_expiry_date' => __('document_renewals.errors.new_expiry_must_be_after_cycle'),
                ]);
            }

            $source = $locked->taskable;
            if (! $source || RenewalDocumentRegistry::extractExpiryDate($documentType, $source) !== $cycleExpiry) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.source_expiry_changed'),
                ]);
            }

            $invoiceId = (int) ($locked->metadata['invoice_id'] ?? 0);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);

            if (! $invoice->isRenewalExpense() || (int) ($invoice->metadata['renewal_request_id'] ?? 0) !== (int) $locked->id) {
                throw ValidationException::withMessages([
                    'invoice' => __('document_renewals.errors.invalid_linked_invoice'),
                ]);
            }

            if ($feeMode === Invoice::RENEWAL_FEE_NO_FEE && ((float) $invoice->paid_amount > 0 || $invoice->hasFinancialImpact())) {
                throw ValidationException::withMessages([
                    'fee_mode' => __('document_renewals.errors.no_fee_conflicts_with_payment'),
                ]);
            }

            $item = TaskAssignmentItem::query()
                ->where('renewal_request_id', $locked->id)
                ->lockForUpdate()
                ->firstOrFail();

            $nextVersion = ((int) ($locked->metadata['submission_version'] ?? 0)) + 1;
            $now = now();

            $submissionData = [
                'version' => $nextVersion,
                'fee_mode' => $feeMode,
                'amount' => $amount,
                'new_expiry_date' => $newExpiryDate,
                'payment_method' => $feeMode === Invoice::RENEWAL_FEE_WITH_FEE ? ($payload['payment_method'] ?? 'cash') : null,
                'reference_number' => $feeMode === Invoice::RENEWAL_FEE_WITH_FEE ? ($payload['reference_number'] ?? null) : null,
                'proof_path' => $feeMode === Invoice::RENEWAL_FEE_WITH_FEE ? ($payload['proof_path'] ?? null) : null,
                'document_attachment' => $payload['document_attachment'] ?? null,
                'notes' => filled($payload['notes'] ?? null) ? trim((string) $payload['notes']) : null,
                'already_paid_treasury_transaction_id' => $alreadyPaidTxId,
                'submitted_by_user_id' => $user->id,
                'submitted_at' => $now->toIso8601String(),
            ];

            $fingerprint = self::computeFingerprint(
                requestId: (int) $locked->id,
                version: $nextVersion,
                feeMode: $feeMode,
                amount: $amount,
                newExpiryDate: $newExpiryDate,
                alreadyPaidTxId: $alreadyPaidTxId,
            );
            $submissionData['fingerprint'] = $fingerprint;

            // Update invoice items cleanly (single renewal fee line when with_fee, cleared when no_fee)
            $invoice->items()->delete();
            if ($feeMode === Invoice::RENEWAL_FEE_WITH_FEE) {
                $invoice->items()->create([
                    'item_name' => __('document_renewals.invoice.fee_line_item', [
                        'type' => RenewalDocumentRegistry::label($documentType),
                    ]),
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'subtotal' => $amount,
                    'tax_rate' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $amount,
                ]);
            }

            $invoiceMeta = is_array($invoice->metadata) ? $invoice->metadata : [];
            $invoiceMeta['renewal_fee_mode'] = $feeMode;
            $invoiceMeta['renewal_workflow_status'] = 'pending_treasury';
            $invoiceMeta['submission_version'] = $nextVersion;
            $invoiceMeta['submission_fingerprint'] = $fingerprint;
            $invoiceMeta['payment_method'] = $submissionData['payment_method'];
            $invoiceMeta['reference_number'] = $submissionData['reference_number'];
            $invoiceMeta['proof_path'] = $submissionData['proof_path'];

            $invoice->update([
                'total_amount' => $amount,
                'paid_amount' => 0.00,
                'remaining_amount' => $amount,
                'notes' => $submissionData['notes'] ?? $invoice->notes,
                'metadata' => $invoiceMeta,
            ]);

            // Create TaskAssignmentItemReview attempt
            $attempt = ((int) TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $item->id)
                ->max('attempt_number')) + 1;

            $review = TaskAssignmentItemReview::query()->create([
                'task_assignment_item_id' => $item->id,
                'attempt_number' => $attempt,
                'submitted_by' => $user->id,
                'submitted_at' => $now,
                'submission_note' => $submissionData['notes'],
                'decision' => TaskAssignmentItemReview::DECISION_PENDING,
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($item, $now, $submissionData): void {
                $item->update([
                    'status' => TaskStatus::PENDING_REVIEW,
                    'submitted_at' => $now,
                    'latest_submission_note' => $submissionData['notes'],
                ]);
            });

            $meta = is_array($locked->metadata) ? $locked->metadata : [];
            $history = is_array($meta['submission_history'] ?? null) ? $meta['submission_history'] : [];
            $history[] = array_merge($submissionData, ['review_id' => $review->id, 'status' => 'pending_treasury']);

            $meta['submission_version'] = $nextVersion;
            $meta['submission_fingerprint'] = $fingerprint;
            $meta['renewal_fee_mode'] = $feeMode;
            $meta['proposed_new_expiry_date'] = $newExpiryDate;
            $meta['submitted_by_user_id'] = $user->id;
            $meta['submitted_at'] = $now->toIso8601String();
            $meta['latest_review_id'] = $review->id;
            $meta['latest_submission'] = $submissionData;
            $meta['submission_history'] = $history;

            $locked->update([
                'status' => UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY,
                'metadata' => $meta,
            ]);

            OperationsAuditService::record(
                companyId: (int) $locked->company_id,
                transactionId: null,
                actionType: 'document_renewal_submitted_to_treasury',
                oldStatus: $request->status,
                newStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY,
                payload: [
                    'renewal_request_id' => $locked->id,
                    'invoice_id' => $invoice->id,
                    'version' => $nextVersion,
                    'fee_mode' => $feeMode,
                    'amount' => $amount,
                    'proposed_new_expiry_date' => $newExpiryDate,
                ],
                userId: $user->id,
            );

            DB::afterCommit(function () use ($locked, $user): void {
                self::notifyTreasuryReviewers($locked, $user);
            });

            return $locked->fresh();
        }, 3);
    }

    /**
     * Return a submitted renewal request for correction with mandatory reason.
     * Does NOT deduct treasury, update source date, reset due_date, or award score.
     */
    public static function returnForCorrection(
        User $reviewer,
        UnifiedDeficiencyTask $request,
        string $reason,
        int $expectedVersion,
    ): UnifiedDeficiencyTask {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'reason' => __('document_renewals.errors.return_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($reviewer, $request, $cleanReason, $expectedVersion): UnifiedDeficiencyTask {
            $locked = UnifiedDeficiencyTask::query()
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if (! DocumentRenewalAccessService::canApproveTreasury($reviewer, $locked)) {
                throw new AuthorizationException(__('document_renewals.errors.cannot_review_treasury'));
            }

            $currentVersion = (int) ($locked->metadata['submission_version'] ?? 0);
            if ($currentVersion !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'version' => __('document_renewals.errors.stale_submission_version'),
                ]);
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail((int) ($locked->metadata['invoice_id'] ?? 0));
            $item = TaskAssignmentItem::query()
                ->where('renewal_request_id', $locked->id)
                ->lockForUpdate()
                ->firstOrFail();

            $review = TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $item->id)
                ->where('decision', TaskAssignmentItemReview::DECISION_PENDING)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();

            $review->update([
                'decision' => TaskAssignmentItemReview::DECISION_RETURNED,
                'decided_by' => $reviewer->id,
                'decided_at' => $now,
                'return_category' => TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT,
                'decision_reason' => $cleanReason,
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($item, $cleanReason): void {
                $item->update([
                    'status' => TaskStatus::RETURNED_FOR_REVISION,
                    'latest_return_reason' => $cleanReason,
                    'returned_count' => (int) $item->returned_count + 1,
                    'defect_return_count' => (int) $item->defect_return_count + 1,
                ]);
            });

            $invoiceMeta = is_array($invoice->metadata) ? $invoice->metadata : [];
            $invoiceMeta['renewal_workflow_status'] = 'returned';
            $invoiceMeta['latest_return_reason'] = $cleanReason;
            $invoice->update(['metadata' => $invoiceMeta]);

            $meta = is_array($locked->metadata) ? $locked->metadata : [];
            $history = is_array($meta['submission_history'] ?? null) ? $meta['submission_history'] : [];
            if ($history !== []) {
                $lastIdx = count($history) - 1;
                $history[$lastIdx]['status'] = 'returned';
                $history[$lastIdx]['return_reason'] = $cleanReason;
                $history[$lastIdx]['decided_by_user_id'] = $reviewer->id;
                $history[$lastIdx]['decided_at'] = $now->toIso8601String();
            }
            $meta['submission_history'] = $history;
            $meta['latest_return_reason'] = $cleanReason;

            $locked->update([
                'status' => UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED,
                'metadata' => $meta,
            ]);

            OperationsAuditService::record(
                companyId: (int) $locked->company_id,
                transactionId: null,
                actionType: 'document_renewal_returned',
                oldStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY,
                newStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED,
                payload: [
                    'renewal_request_id' => $locked->id,
                    'version' => $currentVersion,
                    'reason' => $cleanReason,
                ],
                userId: $reviewer->id,
            );

            DB::afterCommit(function () use ($locked, $cleanReason): void {
                self::notifyAssigneeResult(
                    $locked,
                    'document_renewal_returned',
                    __('document_renewals.notifications.returned_title'),
                    __('document_renewals.notifications.returned_body', [
                        'title' => $locked->title,
                        'reason' => $cleanReason,
                    ]),
                    'heroicon-o-arrow-uturn-left',
                    'warning',
                );
            });

            return $locked->fresh();
        }, 3);
    }

    /**
     * Final treasury approval: atomically posts/links treasury (only for with_fee),
     * updates the official source document expiry date, closes request + item,
     * and awards on-time or late score based on the accepted submission date.
     */
    public static function approve(
        User $reviewer,
        UnifiedDeficiencyTask $request,
        int $expectedVersion,
        ?string $note = null,
    ): UnifiedDeficiencyTask {
        $cleanNote = filled($note) ? trim((string) $note) : null;

        return DB::transaction(function () use ($reviewer, $request, $expectedVersion, $cleanNote): UnifiedDeficiencyTask {
            // Fixed lock order: Request -> Invoice -> TaskAssignmentItem -> Source
            $locked = UnifiedDeficiencyTask::query()
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if (! DocumentRenewalAccessService::canApproveTreasury($reviewer, $locked)) {
                throw new AuthorizationException(__('document_renewals.errors.cannot_review_treasury'));
            }

            $meta = is_array($locked->metadata) ? $locked->metadata : [];
            $currentVersion = (int) ($meta['submission_version'] ?? 0);
            if ($currentVersion !== $expectedVersion) {
                throw ValidationException::withMessages([
                    'version' => __('document_renewals.errors.stale_submission_version'),
                ]);
            }

            if (! empty($meta['approved_at']) || ! empty($meta['treasury_transaction_id'])) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.already_approved'),
                ]);
            }

            $invoice = Invoice::query()->lockForUpdate()->findOrFail((int) ($meta['invoice_id'] ?? 0));
            $item = TaskAssignmentItem::query()
                ->where('renewal_request_id', $locked->id)
                ->lockForUpdate()
                ->firstOrFail();

            $review = TaskAssignmentItemReview::query()
                ->where('task_assignment_item_id', $item->id)
                ->where('decision', TaskAssignmentItemReview::DECISION_PENDING)
                ->orderByDesc('attempt_number')
                ->lockForUpdate()
                ->firstOrFail();

            $latestSubmission = is_array($meta['latest_submission'] ?? null) ? $meta['latest_submission'] : [];
            $feeMode = (string) ($latestSubmission['fee_mode'] ?? $meta['renewal_fee_mode'] ?? '');
            $newExpiryDate = (string) ($latestSubmission['new_expiry_date'] ?? $meta['proposed_new_expiry_date'] ?? '');
            $alreadyPaidTxId = filled($latestSubmission['already_paid_treasury_transaction_id'] ?? null)
                ? (int) $latestSubmission['already_paid_treasury_transaction_id']
                : null;
            $invoiceAmount = round((float) $invoice->total_amount, 2);

            // Verify fingerprint matches live invoice + submission snapshot
            $expectedFingerprint = self::computeFingerprint(
                requestId: (int) $locked->id,
                version: $currentVersion,
                feeMode: $feeMode,
                amount: $invoiceAmount,
                newExpiryDate: $newExpiryDate,
                alreadyPaidTxId: $alreadyPaidTxId,
            );

            if (
                ($meta['submission_fingerprint'] ?? null) !== $expectedFingerprint
                || ($invoice->metadata['submission_fingerprint'] ?? null) !== $expectedFingerprint
            ) {
                throw ValidationException::withMessages([
                    'version' => __('document_renewals.errors.submission_fingerprint_mismatch'),
                ]);
            }

            // Lock and verify source record consistency
            $documentType = (string) ($meta['document_type'] ?? '');
            $cycleExpiry = (string) ($meta['cycle_expiry_date'] ?? '');
            $source = self::lockSourceModel($locked);

            if (! $source) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.source_missing'),
                ]);
            }

            if (RenewalDocumentRegistry::extractExpiryDate($documentType, $source) !== $cycleExpiry) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.source_expiry_changed'),
                ]);
            }

            $now = now();
            $treasuryTransaction = null;

            // 1. Financial settlement step
            if ($feeMode === Invoice::RENEWAL_FEE_NO_FEE) {
                $itemsSum = round((float) $invoice->items()->sum('total_amount'), 2);
                if ($invoiceAmount !== 0.0 || $itemsSum !== 0.0 || $alreadyPaidTxId !== null || $invoice->hasFinancialImpact()) {
                    throw ValidationException::withMessages([
                        'fee_mode' => __('document_renewals.errors.no_fee_must_be_zero'),
                    ]);
                }

                $invoiceMeta = is_array($invoice->metadata) ? $invoice->metadata : [];
                $invoiceMeta['renewal_workflow_status'] = 'approved_no_fee';
                $invoiceMeta['approved_by_user_id'] = $reviewer->id;
                $invoiceMeta['approved_at'] = $now->toIso8601String();
                $invoice->update(['metadata' => $invoiceMeta]);
            } elseif ($feeMode === Invoice::RENEWAL_FEE_WITH_FEE) {
                if ($invoiceAmount <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => __('document_renewals.errors.positive_fee_required'),
                    ]);
                }

                if ($alreadyPaidTxId !== null) {
                    $treasuryTransaction = TreasuryTransaction::query()->lockForUpdate()->findOrFail($alreadyPaidTxId);
                    FinancialTreasuryService::assertLinkable(
                        transaction: $treasuryTransaction,
                        companyId: (int) $locked->company_id,
                        direction: FinancialCashDirection::OUTGOING,
                        amount: $invoiceAmount,
                    );
                } else {
                    $treasuryTransaction = FinancialTreasuryService::post(
                        companyId: (int) $locked->company_id,
                        direction: FinancialCashDirection::OUTGOING,
                        amount: $invoiceAmount,
                        description: __('document_renewals.treasury.disbursement_description', [
                            'type' => RenewalDocumentRegistry::label($documentType),
                            'target' => (string) ($meta['source_label'] ?? ''),
                            'invoice' => (string) $invoice->invoice_number,
                        ]),
                        metadata: [
                            'source' => 'document_renewal',
                            'renewal_request_id' => $locked->id,
                            'invoice_id' => $invoice->id,
                            'document_type' => $documentType,
                            'payment_method' => $latestSubmission['payment_method'] ?? 'cash',
                            'reference_number' => $latestSubmission['reference_number'] ?? null,
                            'proof_path' => $latestSubmission['proof_path'] ?? null,
                        ],
                        executedByEmployeeId: $reviewer->employee?->id,
                        counterparty: (string) ($meta['source_label'] ?? ''),
                        category: PaymentItemCategory::SERVICES,
                        transactionDate: $now->copy()->timezone(config('app.timezone'))->toDateString(),
                    );
                }

                $invoiceMeta = is_array($invoice->metadata) ? $invoice->metadata : [];
                $invoiceMeta['renewal_workflow_status'] = 'approved_paid';
                $invoiceMeta['treasury_transaction_id'] = $treasuryTransaction->id;
                $invoiceMeta['approved_by_user_id'] = $reviewer->id;
                $invoiceMeta['approved_at'] = $now->toIso8601String();

                $invoice->update([
                    'paid_amount' => $invoiceAmount,
                    'remaining_amount' => 0.00,
                    'metadata' => $invoiceMeta,
                ]);
            } else {
                throw ValidationException::withMessages([
                    'fee_mode' => __('document_renewals.errors.invalid_fee_mode'),
                ]);
            }

            // 2. Atomic source expiry date update (strictly whitelisted field only)
            self::applyNewExpiryToSource($documentType, $source, $newExpiryDate);

            // 3. Score calculation using ACCEPTED submission date vs due_date
            $snapshot = is_array($meta['settings_snapshot'] ?? null)
                ? $meta['settings_snapshot']
                : RenewalDocumentRegistry::settingsSnapshot();

            $weight = (float) $item->weight;
            $latePct = (float) ($snapshot['late_completion_percentage'] ?? RenewalDocumentRegistry::lateCompletionPercentage());

            $acceptedSubmittedAt = $review->submitted_at->copy();
            $acceptedDate = $acceptedSubmittedAt->copy()->timezone(config('app.timezone'))->toDateString();
            $dueDateString = $item->due_date?->toDateString();

            $isOnTime = $dueDateString === null || $acceptedDate <= $dueDateString;
            $awardedScore = $isOnTime
                ? round($weight, 2)
                : round($weight * ($latePct / 100.0), 2);

            $review->update([
                'decision' => TaskAssignmentItemReview::DECISION_APPROVED,
                'decided_by' => $reviewer->id,
                'decided_at' => $now,
                'decision_reason' => $cleanNote,
                'awarded_score' => $awardedScore,
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($item, $awardedScore, $acceptedDate, $acceptedSubmittedAt, $now, $reviewer): void {
                $item->update([
                    'status' => TaskStatus::COMPLETED,
                    'score' => $awardedScore,
                    'completed_at' => $acceptedDate,
                    'accepted_submission_at' => $acceptedSubmittedAt,
                    'approved_at' => $now,
                    'approved_by' => $reviewer->id,
                ]);
            });

            $history = is_array($meta['submission_history'] ?? null) ? $meta['submission_history'] : [];
            if ($history !== []) {
                $lastIdx = count($history) - 1;
                $history[$lastIdx]['status'] = 'approved';
                $history[$lastIdx]['decided_by_user_id'] = $reviewer->id;
                $history[$lastIdx]['decided_at'] = $now->toIso8601String();
                $history[$lastIdx]['awarded_score'] = $awardedScore;
                $history[$lastIdx]['on_time'] = $isOnTime;
            }
            $meta['submission_history'] = $history;
            $meta['approved_by_user_id'] = $reviewer->id;
            $meta['approved_at'] = $now->toIso8601String();
            $meta['applied_expiry_date'] = $newExpiryDate;
            $meta['treasury_transaction_id'] = $treasuryTransaction?->id;
            $meta['awarded_score'] = $awardedScore;
            $meta['on_time'] = $isOnTime;

            $locked->update([
                'status' => UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED,
                'resolved_at' => $now,
                'resolved_by_user_id' => $reviewer->id,
                'resolution_notes' => $cleanNote,
                'metadata' => $meta,
            ]);

            OperationsAuditService::record(
                companyId: (int) $locked->company_id,
                transactionId: null,
                actionType: 'document_renewal_approved',
                oldStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY,
                newStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED,
                payload: [
                    'renewal_request_id' => $locked->id,
                    'invoice_id' => $invoice->id,
                    'fee_mode' => $feeMode,
                    'amount' => $invoiceAmount,
                    'treasury_transaction_id' => $treasuryTransaction?->id,
                    'applied_expiry_date' => $newExpiryDate,
                    'awarded_score' => $awardedScore,
                    'on_time' => $isOnTime,
                ],
                userId: $reviewer->id,
            );

            DB::afterCommit(function () use ($locked, $awardedScore, $newExpiryDate): void {
                self::notifyAssigneeResult(
                    $locked,
                    'document_renewal_approved',
                    __('document_renewals.notifications.approved_title'),
                    __('document_renewals.notifications.approved_body', [
                        'title' => $locked->title,
                        'expiry' => $newExpiryDate,
                        'score' => number_format($awardedScore, 2),
                    ]),
                    'heroicon-o-check-circle',
                    'success',
                );
            });

            return $locked->fresh();
        }, 3);
    }

    public static function computeFingerprint(
        int $requestId,
        int $version,
        string $feeMode,
        float $amount,
        string $newExpiryDate,
        ?int $alreadyPaidTxId,
    ): string {
        return hash('sha256', implode('|', [
            $requestId,
            $version,
            $feeMode,
            number_format($amount, 2, '.', ''),
            $newExpiryDate,
            (string) ($alreadyPaidTxId ?? 'none'),
        ]));
    }

    private static function lockSourceModel(UnifiedDeficiencyTask $request): ?Model
    {
        $sourceClass = $request->taskable_type;
        $sourceId = (int) $request->taskable_id;

        if (! is_string($sourceClass) || ! class_exists($sourceClass) || $sourceId <= 0) {
            return null;
        }

        /** @var class-string<Model> $sourceClass */
        return $sourceClass::query()->lockForUpdate()->find($sourceId);
    }

    private static function applyNewExpiryToSource(string $documentType, Model $source, string $newExpiryDate): void
    {
        $def = RenewalDocumentRegistry::definition($documentType);

        if ($def['json_path'] !== null && $source instanceof Company) {
            $platform = (string) $def['platform'];
            $companyMeta = is_array($source->metadata) ? $source->metadata : [];
            $subscriptions = is_array($companyMeta['subscriptions'] ?? null) ? $companyMeta['subscriptions'] : [];
            $platformData = is_array($subscriptions[$platform] ?? null) ? $subscriptions[$platform] : [];
            $platformData['expiry_date'] = $newExpiryDate;
            $subscriptions[$platform] = $platformData;
            $companyMeta['subscriptions'] = $subscriptions;

            $source->metadata = $companyMeta;
            $source->save();

            return;
        }

        $field = $def['expiry_field'];
        $source->{$field} = $newExpiryDate;
        $source->save();
    }

    private static function notifyTreasuryReviewers(UnifiedDeficiencyTask $request, User $submitter): void
    {
        $fresh = $request->fresh();
        if (! $fresh || $fresh->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY) {
            return;
        }

        $branchId = filled($fresh->metadata['branch_id'] ?? null) ? (int) $fresh->metadata['branch_id'] : null;

        $recipients = DocumentRenewalAccessService::eligibleTreasuryReviewers(
            companyId: (int) $fresh->company_id,
            excludeUserId: (int) $submitter->id,
            branchId: $branchId,
        );

        if ($recipients->isEmpty()) {
            return;
        }

        $actionUrl = null;
        try {
            $actionUrl = DocumentRenewals::getUrl();
        } catch (\Throwable) {
            $actionUrl = null;
        }

        $notification = new BaseSystemNotification(
            eventKey: 'document_renewal_pending_treasury',
            customTitle: __('document_renewals.notifications.pending_treasury_title'),
            customMessage: __('document_renewals.notifications.pending_treasury_body', [
                'title' => $fresh->title,
                'employee' => $submitter->name,
            ]),
            icon: 'heroicon-o-banknotes',
            color: 'info',
            actionUrl: $actionUrl,
            recordType: UnifiedDeficiencyTask::class,
            recordId: $fresh->getKey(),
            context: [
                'routing_mode' => 'document_renewal_targeted',
                'renewal_event' => 'pending_treasury',
                'exclude_user_ids' => [(int) $submitter->id],
            ],
        );

        Notification::send($recipients, $notification);
    }

    private static function notifyAssigneeResult(
        UnifiedDeficiencyTask $request,
        string $eventKey,
        string $title,
        string $message,
        string $icon,
        string $color,
    ): void {
        $recipient = $request->responsibleEmployee?->user;
        if (! $recipient instanceof User || ! DocumentRenewalAccessService::isUserActive($recipient)) {
            return;
        }

        $actionUrl = null;
        try {
            $actionUrl = DocumentRenewals::getUrl();
        } catch (\Throwable) {
            $actionUrl = null;
        }

        $renewalEvent = $eventKey === 'document_renewal_returned' ? 'returned' : 'approved';

        $notification = new BaseSystemNotification(
            eventKey: $eventKey,
            customTitle: $title,
            customMessage: $message,
            icon: $icon,
            color: $color,
            actionUrl: $actionUrl,
            recordType: UnifiedDeficiencyTask::class,
            recordId: $request->getKey(),
            context: [
                'routing_mode' => 'document_renewal_targeted',
                'renewal_event' => $renewalEvent,
            ],
        );

        Notification::send(Collection::make([$recipient]), $notification);
    }
}
