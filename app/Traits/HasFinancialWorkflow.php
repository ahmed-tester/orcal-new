<?php

namespace App\Traits;

use App\Models\UnifiedAuditLog;
use App\Models\UnifiedDeficiencyTask;
use App\Services\OperationsAuditService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFinancialWorkflow
{
    /**
     * Get all unified audit logs for this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(UnifiedAuditLog::class, 'auditable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get all deficiency tasks linked to this model.
     */
    public function deficiencyTasks(): MorphMany
    {
        return $this->morphMany(UnifiedDeficiencyTask::class, 'taskable')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Record an audit log entry for this model.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordAudit(
        string $actionType,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        array $payload = [],
        ?int $userId = null,
    ): UnifiedAuditLog {
        return OperationsAuditService::recordFor(
            model: $this,
            actionType: $actionType,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            payload: $payload,
            userId: $userId,
            companyId: $this->company_id ?? 1,
        );
    }

    /**
     * Create a deficiency task associated with this model.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function createDeficiencyTask(
        string $deficiencyType,
        string $title,
        ?string $description = null,
        ?int $responsibleEmployeeId = null,
        ?string $dueDate = null,
        array $metadata = [],
    ): UnifiedDeficiencyTask {
        return $this->deficiencyTasks()->create([
            'company_id' => $this->company_id ?? 1,
            'deficiency_type' => $deficiencyType,
            'title' => $title,
            'description' => $description,
            'responsible_employee_id' => $responsibleEmployeeId,
            'assigned_by_user_id' => auth()->id(),
            'status' => 'open',
            'due_date' => $dueDate ?? now()->addDays(2)->toDateString(),
            'metadata' => $metadata,
        ]);
    }
}

