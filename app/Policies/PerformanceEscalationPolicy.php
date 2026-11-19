<?php

namespace App\Policies;

use App\Models\PerformanceEscalation;
use App\Models\User;
use App\Services\CompanyDepartmentPerformanceService;
use App\Services\PerformanceEscalationService;

class PerformanceEscalationPolicy
{
    public function viewAny(User $user): bool
    {
        return PerformanceEscalationService::canViewEscalations($user);
    }

    public function view(User $user, PerformanceEscalation $escalation): bool
    {
        if (! $this->viewAny($user)
            || ! $this->canAccessCompany($user, (int) $escalation->company_id)) {
            return false;
        }

        // Historical cases stay visible when the source was deleted, but a
        // source that moved to another company is no longer shown here.
        if ($escalation->subject === null) {
            return true;
        }

        return PerformanceEscalationService::subjectBelongsToCompany(
            $escalation->subject_type,
            (int) $escalation->subject_id,
            (int) $escalation->company_id,
        );
    }

    public function manage(User $user, PerformanceEscalation $escalation): bool
    {
        return $escalation->isOpen()
            && $this->view($user, $escalation)
            && ($user->isSuperAdminOrOwner() || $user->can('resolve_performance_escalation'));
    }

    private function canAccessCompany(User $user, int $companyId): bool
    {
        return in_array(
            $companyId,
            CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user),
            true,
        );
    }
}
