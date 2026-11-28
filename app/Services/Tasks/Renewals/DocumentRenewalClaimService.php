<?php

namespace App\Services\Tasks\Renewals;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Services\EmployeePerformanceAccessService;
use App\Services\OperationsAuditService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentRenewalClaimService
{
    /**
     * Atomically claim an available document renewal request and create
     * one dedicated TaskAssignment with one TaskAssignmentItem.
     *
     * @return array{request: UnifiedDeficiencyTask, item: TaskAssignmentItem, already_claimed_by_user: bool}
     */
    public static function claim(User $user, UnifiedDeficiencyTask $request): array
    {
        return DB::transaction(function () use ($user, $request): array {
            $locked = UnifiedDeficiencyTask::query()
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if (! $locked->isDocumentRenewal()) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.invalid_request_type'),
                ]);
            }

            $employee = $user->employee;

            // Idempotent re-claim by the same assignee
            if (
                $employee instanceof Employee
                && (int) $locked->responsible_employee_id === (int) $employee->id
                && in_array($locked->status, [
                    UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED,
                    UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED,
                    UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY,
                ], true)
            ) {
                $existingItem = TaskAssignmentItem::query()
                    ->where('renewal_request_id', $locked->id)
                    ->firstOrFail();

                return [
                    'request' => $locked,
                    'item' => $existingItem,
                    'already_claimed_by_user' => true,
                ];
            }

            if ($locked->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE || $locked->responsible_employee_id !== null) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.already_claimed_or_closed'),
                ]);
            }

            if (! DocumentRenewalAccessService::canClaim($user, $locked)) {
                throw new AuthorizationException(__('document_renewals.errors.not_eligible_to_claim'));
            }

            $documentType = (string) ($locked->metadata['document_type'] ?? '');
            $expectedExpiry = (string) ($locked->metadata['cycle_expiry_date'] ?? '');

            // Re-verify source consistency before claiming
            $source = $locked->taskable;
            if (! $source) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.source_missing'),
                ]);
            }

            $currentExpiry = RenewalDocumentRegistry::extractExpiryDate($documentType, $source);
            if ($currentExpiry !== $expectedExpiry) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.source_expiry_changed'),
                ]);
            }

            $systemTask = RenewalDocumentRegistry::ensureSystemTask($documentType);

            $snapshot = is_array($locked->metadata['settings_snapshot'] ?? null)
                ? $locked->metadata['settings_snapshot']
                : RenewalDocumentRegistry::settingsSnapshot();

            $deadlineDays = max(1, (int) ($snapshot['deadline_days'] ?? RenewalDocumentRegistry::deadlineDays()));
            $weight = max(0.01, round((float) ($snapshot['task_weight'] ?? RenewalDocumentRegistry::taskWeight()), 2));

            $claimedAt = Carbon::now(config('app.timezone'));
            $dueDate = $claimedAt->copy()->addDays($deadlineDays)->toDateString();

            $assignment = TaskAssignment::query()->create([
                'assignment_date' => $claimedAt->toDateString(),
                'employee_id' => $employee->id,
                'from_date' => $claimedAt->toDateString(),
                'to_date' => $dueDate,
                'created_by' => $user->id,
                'notes' => $locked->title,
                'metadata' => [
                    'source' => 'document_renewal',
                    'renewal_request_id' => $locked->id,
                    'company_id' => $locked->company_id,
                ],
            ]);

            $item = TaskAssignmentItem::withoutReviewGuard(fn (): TaskAssignmentItem => TaskAssignmentItem::query()->create([
                'task_assignment_id' => $assignment->id,
                'task_id' => $systemTask->id,
                'renewal_request_id' => $locked->id,
                'company_id' => $locked->company_id,
                'status' => TaskStatus::IN_PROGRESS,
                'requires_review' => true,
                'weight' => $weight,
                'score' => 0.00,
                'due_date' => $dueDate,
                'original_due_date' => $dueDate,
                'notes' => $locked->description,
                'metadata' => [
                    'document_type' => $documentType,
                    'cycle_expiry_date' => $expectedExpiry,
                    'source_label' => $locked->metadata['source_label'] ?? null,
                    'settings_snapshot' => $snapshot,
                ],
            ]));

            $meta = is_array($locked->metadata) ? $locked->metadata : [];
            $meta['claimed_at'] = $claimedAt->toIso8601String();
            $meta['claimed_by_user_id'] = $user->id;
            $meta['task_assignment_id'] = $assignment->id;
            $meta['task_assignment_item_id'] = $item->id;
            $meta['unassignable'] = false;

            $locked->update([
                'responsible_employee_id' => $employee->id,
                'assigned_by_user_id' => $user->id,
                'status' => UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED,
                'due_date' => $dueDate,
                'metadata' => $meta,
            ]);

            OperationsAuditService::record(
                companyId: (int) $locked->company_id,
                transactionId: null,
                actionType: 'document_renewal_claimed',
                oldStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE,
                newStatus: UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED,
                payload: [
                    'renewal_request_id' => $locked->id,
                    'task_assignment_id' => $assignment->id,
                    'task_assignment_item_id' => $item->id,
                    'employee_id' => $employee->id,
                    'due_date' => $dueDate,
                ],
                userId: $user->id,
            );

            return [
                'request' => $locked->fresh(),
                'item' => $item->fresh(),
                'already_claimed_by_user' => false,
            ];
        }, 3);
    }

    /**
     * Dedicated renewal reassignment that preserves the single TaskAssignmentItem,
     * UnifiedDeficiencyTask, Invoice, due_date, and settings snapshot.
     */
    public static function reassign(
        User $actor,
        UnifiedDeficiencyTask $request,
        Employee $targetEmployee,
        string $reason,
    ): TaskAssignmentItem {
        $cleanReason = trim($reason);
        if ($cleanReason === '') {
            throw ValidationException::withMessages([
                'reason' => __('document_renewals.errors.reassignment_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($actor, $request, $targetEmployee, $cleanReason): TaskAssignmentItem {
            $locked = UnifiedDeficiencyTask::query()
                ->lockForUpdate()
                ->findOrFail($request->getKey());

            if (! DocumentRenewalAccessService::canReassign($actor, $locked)) {
                throw new AuthorizationException(__('document_renewals.errors.cannot_reassign'));
            }

            $item = TaskAssignmentItem::query()
                ->where('renewal_request_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw ValidationException::withMessages([
                    'request' => __('document_renewals.errors.not_claimed_yet'),
                ]);
            }

            if ((int) $locked->responsible_employee_id === (int) $targetEmployee->id) {
                throw ValidationException::withMessages([
                    'target_employee_id' => __('document_renewals.errors.reassign_same_employee'),
                ]);
            }

            if (! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($targetEmployee)) {
                throw ValidationException::withMessages([
                    'target_employee_id' => __('tasks.validation.saudi_only'),
                ]);
            }

            $documentType = (string) ($locked->metadata['document_type'] ?? '');
            $targetUser = $targetEmployee->user;
            if (
                ! $targetUser instanceof User
                || ! DocumentRenewalAccessService::userHasExplicitRenewalPermission($targetUser, RenewalDocumentRegistry::permissionFor($documentType))
                || ! in_array((int) $locked->company_id, DocumentRenewalAccessService::coveredCompanyIdsForManager($targetEmployee), true)
            ) {
                throw ValidationException::withMessages([
                    'target_employee_id' => __('document_renewals.errors.target_missing_renewal_permission'),
                ]);
            }

            $oldAssignment = $item->taskAssignment;
            $oldEmployeeId = $locked->responsible_employee_id;

            $newAssignment = TaskAssignment::query()->create([
                'assignment_date' => Carbon::now(config('app.timezone'))->toDateString(),
                'employee_id' => $targetEmployee->id,
                'from_date' => $oldAssignment?->from_date,
                'to_date' => $oldAssignment?->to_date,
                'created_by' => $actor->id,
                'notes' => $locked->title,
                'metadata' => [
                    'source' => 'document_renewal',
                    'renewal_request_id' => $locked->id,
                    'company_id' => $locked->company_id,
                    'reassigned_from_assignment_id' => $oldAssignment?->id,
                ],
            ]);

            TaskAssignmentItem::withoutReviewGuard(function () use ($item, $newAssignment): void {
                $item->update([
                    'task_assignment_id' => $newAssignment->id,
                ]);
            });

            $meta = is_array($locked->metadata) ? $locked->metadata : [];
            $history = is_array($meta['reassignment_history'] ?? null) ? $meta['reassignment_history'] : [];
            $history[] = [
                'from_employee_id' => $oldEmployeeId,
                'to_employee_id' => $targetEmployee->id,
                'reason' => $cleanReason,
                'by_user_id' => $actor->id,
                'at' => now()->toIso8601String(),
            ];
            $meta['reassignment_history'] = $history;
            $meta['task_assignment_id'] = $newAssignment->id;

            $locked->update([
                'responsible_employee_id' => $targetEmployee->id,
                'metadata' => $meta,
            ]);

            $oldAssignment?->fresh()?->recalculateTotals();
            $newAssignment->fresh()?->recalculateTotals();

            OperationsAuditService::record(
                companyId: (int) $locked->company_id,
                transactionId: null,
                actionType: 'document_renewal_reassigned',
                oldStatus: "employee:{$oldEmployeeId}",
                newStatus: "employee:{$targetEmployee->id}",
                payload: [
                    'renewal_request_id' => $locked->id,
                    'task_assignment_item_id' => $item->id,
                    'old_assignment_id' => $oldAssignment?->id,
                    'new_assignment_id' => $newAssignment->id,
                    'reason' => $cleanReason,
                ],
                userId: $actor->id,
            );

            return $item->fresh();
        }, 3);
    }
}
