<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use Filament\Forms\Components\Select;

class TaskAssignmentStatusResolver
{
    public function __construct(
        protected ?User $user = null,
        protected ?TaskAssignmentItem $item = null,
        protected ?TaskAssignment $assignment = null,
    ) {
        $this->user ??= auth()->user();
    }

    /**
     * Factory method to build a resolver from a form item and component.
     */
    public static function forItem(?TaskAssignmentItem $item = null, ?Select $component = null): static
    {
        $assignment = $item?->taskAssignment;

        if (! $assignment && $component) {
            try {
                $livewireRecord = $component->getLivewire()?->record ?? null;
                if ($livewireRecord instanceof TaskAssignment) {
                    $assignment = $livewireRecord;
                }
            } catch (\Throwable) {
                $assignment = null;
            }
        }

        return new static(
            user: auth()->user(),
            item: $item,
            assignment: $assignment,
        );
    }

    /**
     * Static shortcut to get status options for form select component.
     *
     * @return array<string, string>
     */
    public static function getOptions(?TaskAssignmentItem $item = null, ?Select $component = null): array
    {
        return static::forItem($item, $component)->getAvailableOptions();
    }

    /**
     * Resolve all allowed status options mapped as [value => label].
     *
     * @return array<string, string>
     */
    public function getAvailableOptions(): array
    {
        $options = [];

        foreach (TaskStatus::cases() as $status) {
            if ($this->isStatusAllowed($status)) {
                $options[$status->value] = $status->getLabel();
            }
        }

        return $options;
    }

    /**
     * Determine whether the given status is allowed in current context.
     */
    public function isStatusAllowed(TaskStatus $status): bool
    {
        if ($this->item !== null && $this->item->status === TaskStatus::TRANSFERRED) {
            return $status === TaskStatus::TRANSFERRED;
        }

        if (
            $this->item !== null
            && $this->item->requires_review
            && in_array($this->item->status, [TaskStatus::COMPLETED, TaskStatus::PENDING_REVIEW], true)
        ) {
            return $this->item->status === $status;
        }

        // Preserve currently assigned status on existing records
        if ($this->item !== null && $this->item->status === $status) {
            return true;
        }

        return match ($status) {
            TaskStatus::PENDING,
            TaskStatus::IN_PROGRESS => true,

            TaskStatus::COMPLETED => $this->item !== null && ! $this->item->requires_review,

            TaskStatus::TRANSFERRED => $this->canTransfer(),
            TaskStatus::ESCALATED => $this->canEscalate(),
            TaskStatus::POSTPONED => $this->canPostpone(),

            TaskStatus::PENDING_REVIEW,
            TaskStatus::RETURNED_FOR_REVISION => false,
        };
    }

    /**
     * Check if user can select transferred status (assignment creator or super admin).
     */
    public function canTransfer(): bool
    {
        if (! $this->user) {
            return false;
        }

        return $this->isSuperAdmin()
            || $this->assignment === null
            || ((int) $this->assignment->created_by === (int) $this->user->id);
    }

    /**
     * Check if user can select escalated status (assigned employee only).
     */
    public function canEscalate(): bool
    {
        if (! $this->user) {
            return false;
        }

        $userEmployeeId = $this->user->employee?->id;
        $assignedEmployeeId = $this->assignment?->employee_id;

        return $userEmployeeId !== null
            && $assignedEmployeeId !== null
            && ((int) $userEmployeeId === (int) $assignedEmployeeId);
    }

    /**
     * Check if user can postpone the task (super admin or company manager).
     */
    public function canPostpone(): bool
    {
        if (! $this->user) {
            return false;
        }

        return $this->isSuperAdmin() || $this->isCompanyManager();
    }

    /**
     * Check whether current user is Super Admin.
     */
    public function isSuperAdmin(): bool
    {
        if (! $this->user) {
            return false;
        }

        return (method_exists($this->user, 'isSuperAdmin') && $this->user->isSuperAdmin())
            || $this->user->hasRole('Super Admin')
            || $this->user->user_type === UserType::SUPER_ADMIN;
    }

    /**
     * Check whether current user is a Company Manager.
     */
    public function isCompanyManager(): bool
    {
        if (! $this->user) {
            return false;
        }

        return (method_exists($this->user, 'isOwner') && $this->user->isOwner())
            || $this->user->hasAnyRole(['مدير عام', 'مدير فرع', 'مدير عمليات', 'مدير المنشأة']);
    }
}
