<?php

namespace App\Observers;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use App\Services\Tasks\Renewals\RenewalDocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TaskObserver
{
    /**
     * Handle the Task "creating" event.
     */
    public function creating(Task $task): void
    {
        if (empty($task->task_code)) {
            $task->task_code = $this->generateTaskCode();
        }
    }

    /**
     * Protect reserved system document renewal tasks from code changes or deactivation.
     */
    public function updating(Task $task): void
    {
        $originalCode = (string) ($task->getOriginal('task_code') ?? '');

        if (! RenewalDocumentRegistry::isReservedTaskCode($originalCode)) {
            return;
        }

        if ($task->isDirty('task_code') && (string) $task->task_code !== $originalCode) {
            throw ValidationException::withMessages([
                'task_code' => __('document_renewals.catalog.errors.cannot_modify_system_task'),
            ]);
        }

        $statusValue = $task->status instanceof GeneralStatus ? $task->status->value : (string) $task->status;
        if ($statusValue !== GeneralStatus::ACTIVE->value) {
            throw ValidationException::withMessages([
                'status' => __('document_renewals.catalog.errors.cannot_deactivate_system_task'),
            ]);
        }
    }

    /**
     * Protect reserved system document renewal tasks from deletion (cascadeOnDelete safety).
     */
    public function deleting(Task $task): void
    {
        $code = (string) ($task->task_code ?? $task->getOriginal('task_code') ?? '');

        if (RenewalDocumentRegistry::isReservedTaskCode($code)) {
            throw ValidationException::withMessages([
                'task_code' => __('document_renewals.catalog.errors.cannot_delete_system_task'),
            ]);
        }
    }

    /**
     * Generate unique task code based on company number.
     */
    public function generateTaskCode(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        $company = null;

        if ($user) {
            $company = $user->employee?->company;
            if (!$company && method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
                $company = Company::first();
            }
        }

        if (!$company) {
            $company = Company::first();
        }

        $companyPrefix = $company?->company_number ?: ($company ? 'COM-' . $company->id : 'ORG');
        $nextNumber = (Task::max('id') ?? 0) + 1;

        return $companyPrefix . '-TSK-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
