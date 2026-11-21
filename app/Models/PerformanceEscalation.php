<?php

namespace App\Models;

use App\Enums\PerformanceEscalationClosureReason;
use App\Enums\PerformanceEscalationStatus;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\EmployeePerformanceAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PerformanceEscalation extends Model
{
    public const SUBJECT_DEPARTMENT = 'department';

    public const SUBJECT_EMPLOYEE = 'employee';

    public const SUBJECT_BRANCH = 'branch';

    public const SUBJECT_SUPERVISOR = 'supervisor';

    /**
     * Explicit morph binding for the escalation subject. Only the department
     * rule is active now; the other keys reserve the same table structure.
     *
     * @var array<string, class-string<Model>>
     */
    public const SUBJECT_TYPES = [
        self::SUBJECT_DEPARTMENT => Department::class,
        self::SUBJECT_EMPLOYEE => Employee::class,
        self::SUBJECT_BRANCH => Branch::class,
        self::SUBJECT_SUPERVISOR => User::class,
    ];

    protected $fillable = [
        'company_id',
        'subject_type',
        'subject_id',
        'rule_key',
        'status',
        'score_at_open',
        'threshold',
        'rules_version',
        'opened_at',
        'open_slot',
        'management_action',
        'management_action_by_user_id',
        'management_action_at',
        'closure_reason',
        'close_reason',
        'close_score',
        'closed_at',
        'closed_by_user_id',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PerformanceEscalationStatus::class,
            'closure_reason' => PerformanceEscalationClosureReason::class,
            'score_at_open' => 'decimal:2',
            'close_score' => 'decimal:2',
            'threshold' => 'integer',
            'open_slot' => 'integer',
            'opened_at' => 'datetime',
            'management_action_at' => 'datetime',
            'closed_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subject(): MorphTo
    {
        $class = $this->getSubjectModelAttribute();

        return $class === null
            ? $this->morphEagerTo('subject', 'subject_model', 'subject_id', null)
            : $this->morphInstanceTo($class, 'subject', 'subject_model', 'subject_id', null);
    }

    public function getSubjectModelAttribute(): ?string
    {
        return self::SUBJECT_TYPES[$this->subject_type] ?? null;
    }

    public function managementActionUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'management_action_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->status === PerformanceEscalationStatus::OPEN;
    }

    public function subjectName(): string
    {
        $subject = $this->subject;

        return match (true) {
            $subject instanceof Department => $subject->parent
                ? "{$subject->name} ({$subject->parent->name})"
                : $subject->name,
            $subject instanceof Employee => $subject->user?->name ?? (string) $subject->employee_number,
            $subject instanceof Branch => $subject->name,
            $subject instanceof User => $subject->name,
            default => '',
        };
    }

    public function subjectLabel(): string
    {
        $type = __('performance_escalations.subject_types.'.$this->subject_type);
        $name = $this->subjectName();

        return $name !== '' ? "{$type}: {$name}" : $type;
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', PerformanceEscalationStatus::OPEN->value);
    }

    /**
     * Rows a viewer may see. Department-wide scores are only shown to users
     * who can see the whole department (no branch/team restriction).
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (! EmployeePerformanceAccessService::isSupervisor($user)
            && ! ($user->branch_id && ! $user->canAccessAllBranches())
            && ($user->isSuperAdminOrOwner() || $user->can('view_any_performance_escalation'))) {
            return $query->whereIn('company_id', CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user));
        }

        return $query->whereRaw('1 = 0');
    }
}
