<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
