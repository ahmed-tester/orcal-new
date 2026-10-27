<?php

namespace App\Observers;

use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use App\Models\Company;
use App\Models\TaskAssignment;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $assignment->loadMissing(['employee.user', 'creator']);

        $user = $assignment->employee?->user;

        if (! $user) {
            return;
        }

        $creatorName = $assignment->creator?->name ?? Auth::user()?->name ?? __('tasks.creator_default');

        try {
            $viewUrl = TaskAssignmentResource::getUrl('edit', ['record' => $assignment]);
        } catch (\Throwable) {
            $viewUrl = url("/admin/task-assignments/{$assignment->id}/edit");
        }

        try {
            $notification = FilamentNotification::make()
                ->title(__('tasks.notifications.new_assignment_title'))
                ->body(__('tasks.notifications.new_assignment_body', [
                    'number' => $assignment->transaction_number,
                    'creator' => $creatorName,
                ]))
                ->icon('heroicon-o-clipboard-document-check')
                ->iconColor('success')
                ->actions([
                    Action::make('view')
                        ->label(__('tasks.notifications.view_assignment'))
                        ->url($viewUrl)
                        ->button(),
                ]);

            // إيداع فوري في قاعدة البيانات عبر notifyNow لتجاوز طابور الـ Queue
            $user->notifyNow($notification->toDatabase());
        } catch (\Throwable $e) {
            Log::error('[TaskAssignmentNotification] Failed to send notification', [
                'error' => $e->getMessage(),
                'assignment_id' => $assignment->id,
                'target_user_id' => $user->id,
            ]);
        }
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
