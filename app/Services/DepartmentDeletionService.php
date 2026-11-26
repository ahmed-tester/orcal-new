<?php

namespace App\Services;

use App\Models\Department;
use App\Models\DepartmentArchiveBatch;
use App\Services\Departments\DepartmentArchiveExecutionService;
use App\Services\Departments\DepartmentImpactService;
use App\Services\Departments\DepartmentRestoreService;
use Illuminate\Support\Collection;

class DepartmentDeletionService
{
    /**
     * @return array<string, mixed>
     */
    public static function analyzeImpact(Department $department): array
    {
        return DepartmentImpactService::analyzeImpact($department);
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return array<string, mixed>
     */
    public static function analyzeBulkImpact(Collection $departments): array
    {
        return DepartmentImpactService::analyzeBulkImpact($departments);
    }

    /**
     * @param  Department|Collection<int, Department>  $sources
     * @return array<int, string>
     */
    public static function compatibleTransferTargets(Department|Collection $sources): array
    {
        return DepartmentImpactService::compatibleTransferTargets($sources);
    }

    /**
     * @param  Collection<int, Department>  $sources
     */
    public static function canTransferAllToTarget(Collection $sources, Department $target): bool
    {
        return DepartmentImpactService::canTransferAllToTarget($sources, $target);
    }

    /**
     * @param  Department|Collection<int, Department>  $sources
     */
    public static function transferAndArchive(Department|Collection $sources, int $targetDepartmentId): DepartmentArchiveBatch
    {
        return DepartmentArchiveExecutionService::transferAndArchive($sources, $targetDepartmentId);
    }

    /**
     * @param  Department|Collection<int, Department>  $sources
     */
    public static function archiveWithDependents(
        Department|Collection $sources,
        string $confirmationText = '',
        bool $requireConfirmation = true
    ): DepartmentArchiveBatch {
        return DepartmentArchiveExecutionService::archiveWithDependents($sources, $confirmationText, $requireConfirmation);
    }

    public static function restoreBatch(DepartmentArchiveBatch $batch, ?int $overrideParentId = null): void
    {
        DepartmentRestoreService::restoreBatch($batch, $overrideParentId);
    }
}
