<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\TaskAssignment;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\Auth;

class TaskAssignmentObserver
{
    /**
     * Handle the TaskAssignment "creating" event.
     */
    public function creating(TaskAssignment $assignment): void
    {
        if (empty($assignment->transaction_number)) {
            $assignment->transaction_number = $this->generateTransactionNumber();
        }

        if (empty($assignment->created_by)) {
            $assignment->created_by = Auth::id();
        }
    }

    /**
     * Handle the TaskAssignment "created" event.
     */
    public function created(TaskAssignment $assignment): void
    {
        $this->notifyAssignedEmployee($assignment);
    }

    /**
     * Handle the TaskAssignment "updated" event.
     */
    public function updated(TaskAssignment $assignment): void
    {
        if ($assignment->wasChanged('employee_id')) {
            $this->notifyAssignedEmployee($assignment);
        }
    }

    /**
     * Send in-app notification to the assigned employee's user account.
     */
    protected function notifyAssignedEmployee(TaskAssignment $assignment): void
    {
        NotificationDispatcher::translated(
            record: $assignment,
            eventKey: 'TaskAssigned',
            titleKey: 'tasks.notifications.new_assignment_title',
            bodyKey: 'tasks.notifications.new_assignment_body',
            parameters: [
                'number' => $assignment->transaction_number,
                'creator' => $assignment->creator?->name ?? Auth::user()?->name ?? __('tasks.creator_default'),
            ],
            icon: 'heroicon-o-clipboard-document-check',
            color: 'success',
        );
    }

    /**
     * Generate unique transaction number based on company number and date.
     */
    public function generateTransactionNumber(?User $user = null): string
    {
        $user = $user ?? Auth::user();
        $company = null;

        if ($user) {
            $company = $user->employee?->company;
            if (! $company && method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
                $company = Company::first();
            }
        }

        if (! $company) {
            $company = Company::first();
        }

        $companyPrefix = $company?->company_number ?: ($company ? 'COM-'.$company->id : 'ORG');
        $nextNumber = (TaskAssignment::max('id') ?? 0) + 1;

        return $companyPrefix.'-ASN-'.date('Ymd').'-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
