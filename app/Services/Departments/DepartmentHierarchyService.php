<?php

namespace App\Services\Departments;

use App\Enums\DepartmentScope;
use App\Models\Department;
use App\Models\Employee;
use App\Services\CodeGeneratorService;
use App\Services\CompanyAccessScopeService;
use App\Services\CompanyFormService;
use Closure;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DepartmentHierarchyService
{
    public const PREFIX_ADMINISTRATION = 'DEP1';

    public const PREFIX_MAIN = 'DEP2';

    public const PREFIX_SUB = 'DEP3';

    public const MAX_DEPTH = 3;

    /** Creation defaults come from the authorized parent, not editable browser fields. */
    public static function creationDefaults(?Department $parent): array
    {
        $targetLevel = static::targetLevelForParent($parent?->id);
        $isGeneral = $targetLevel <= 2;

        return [
            'parent_id' => $parent?->id,
            'manager_id' => null,
            'department_scope' => $isGeneral ? DepartmentScope::FAWAZ_GROUP->value : DepartmentScope::SINGLE->value,
            'is_global' => $isGeneral,
            'company_id' => null,
            'companies' => [],
        ];
    }

    public static function createFromContext(array $data, ?int $parentId, string $errorKeyPrefix = ''): Department
    {
        return static::transaction(function () use ($data, $parentId, $errorKeyPrefix) {
            $parent = $parentId ? CompanyAccessScopeService::apply(Department::query())
                ->with('companies')->lockForUpdate()->findOrFail($parentId) : null;
            $targetLevel = static::targetLevelForParent($parentId);

            if ($targetLevel > self::MAX_DEPTH) {
                static::fail($errorKeyPrefix.'parent_id', 'max_depth_exceeded');
            }

            if ($targetLevel <= 2 && (filled($data['company_id'] ?? null) || ! empty($data['companies'] ?? []))) {
                static::fail($errorKeyPrefix.'company_id', 'general_level_forbids_companies');
            }

            $defaults = static::creationDefaults($parent);
            $data = array_replace($defaults, $data);
            $data['parent_id'] = $parentId;
            if ($targetLevel <= 2) {
                $lockedDefaults = $defaults;
                unset($lockedDefaults['manager_id']);
                $data = array_replace($data, $lockedDefaults);
            }
            $scope = DepartmentScope::tryFrom((string) $data['department_scope']);
            if (! $scope) {
                static::fail($errorKeyPrefix.'department_scope', 'scope_invalid');
            }
            if ($targetLevel === self::MAX_DEPTH && $scope === DepartmentScope::FAWAZ_GROUP) {
                static::fail($errorKeyPrefix.'company_id', 'subdepartment_requires_companies');
            }
            $companies = (array) $data['companies'];
            unset($data['companies'], $data['department_scope']);

            return static::save($data, null, $scope, $companies, $errorKeyPrefix);
        });
    }

    /** Save attributes and company assignments together, for both Filament and AI tools. */
    public static function save(array $data, ?Department $record, DepartmentScope $scope, array $companyIds = [], string $errorKeyPrefix = ''): Department
    {
        return static::transaction(function () use ($data, $record, $scope, $companyIds, $errorKeyPrefix) {
            if ($record) {
                $record = Department::query()->lockForUpdate()->findOrFail($record->getKey());
                static::authorizeUpdates(collect([$record]));
                if ($record->parent_id === null && blank($data['parent_id'] ?? null)
                    && DepartmentScopeService::resolveScope($record) === DepartmentScope::FAWAZ_GROUP) {
                    $scope = DepartmentScope::FAWAZ_GROUP;
                    $companyIds = [];
                    $data['company_id'] = null;
                }
            } else {
                Gate::authorize('create', Department::class);
            }

            if ($scope === DepartmentScope::FAWAZ_GROUP && (filled($data['company_id'] ?? null) || ! empty($companyIds))) {
                static::fail($errorKeyPrefix.'company_id', 'general_level_forbids_companies');
            }

            if ($scope === DepartmentScope::SHARED) {
                $normalized = static::normalizedIds($companyIds);
                if (count($normalized) === 1) {
                    $scope = DepartmentScope::SINGLE;
                    $data['company_id'] = $normalized[0];
                    $companyIds = $normalized;
                }
            }

            [$data, $companyIds] = static::prepareSave($data, $record, $scope, $companyIds);
            $parentId = $data['parent_id'];
            $moving = $record && $record->parent_id != $parentId;
            if ($moving && $parentId !== null) {
                $data['manager_id'] = null;
            }

            static::authorizeAssignment($parentId, $companyIds);
            static::assertStructure($data, $record, $companyIds, $errorKeyPrefix);
            static::assertValidSave($data, $record, $companyIds, $errorKeyPrefix);

            $assignmentChanged = $record && static::companyIds($record) != static::normalizedIds($companyIds);
            if ($moving || $assignmentChanged) {
                static::assertBranchAssignable($record, $errorKeyPrefix);
            }
            if ($moving) {
                static::assertBranchLayerCompatibility($record, static::targetLevelForParent($parentId), $errorKeyPrefix);
            }

            $position = isset($data['sort_order']) ? max(1, (int) $data['sort_order']) : null;
            $reordering = $record && ! $moving && $position !== null && $position !== $record->sort_order;
            $data['sort_order'] = ($moving || ! $record) ? static::nextSortOrder($parentId) : $record->sort_order;
            unset($data['department_code']);

            $record ??= new Department;
            $record->pendingCompanyIds = $companyIds;
            try {
                $record->fill($data);
                if ($moving) {
                    static::assertCodeAvailable($record, $errorKeyPrefix);
                }
                $record->save();
                $record->companies()->sync($companyIds);
                if ($reordering || ($position !== null && $position !== $record->sort_order && ! $moving)) {
                    static::placeAt($record, $position);
                }

                return $record->refresh();
            } finally {
                $record->pendingCompanyIds = null;
            }
        });
    }

    protected static function prepareSave(array $data, ?Department $record, DepartmentScope $scope, array $companyIds): array
    {
        $data = array_replace($record?->only(['name', 'description', 'parent_id', 'manager_id', 'company_id', 'status']) ?? ['status' => 'active'], $data);
        $data = DepartmentScopeService::mutateBeforeSave($data, $scope, $record);
        $companyIds = match ($scope) {
            DepartmentScope::SINGLE => array_filter([(int) ($data['company_id'] ?? 0)]),
            DepartmentScope::SHARED => static::normalizedIds($companyIds),
            DepartmentScope::FAWAZ_GROUP => [],
        };
        $data['company_id'] = $companyIds[0] ?? null;
        $data['parent_id'] = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;
        $data['manager_id'] = filled($data['manager_id'] ?? null) ? (int) $data['manager_id'] : null;

        return [$data, $companyIds];
    }

    protected static function assertBranchAssignable(Department $record, string $prefix): void
    {
        $descendants = Department::query()->whereKey(static::descendantIds((int) $record->id))->with('companies')->get();
        static::authorizeUpdates($descendants);
        foreach ($descendants as $descendant) {
            if ((int) $descendant->parent_id === (int) $record->id) {
                continue;
            }
            static::assertCompaniesWithinParent($descendant->parent_id, $descendant->is_global,
                $descendant->company_id, $descendant->companies->pluck('id')->all(), $prefix);
        }
    }

    protected static function assertBranchLayerCompatibility(Department $record, int $targetLevel, string $prefix = ''): void
    {
        $descendantIds = static::descendantIds((int) $record->id);
        if ($descendantIds === []) {
            return;
        }

        $descendants = Department::query()->whereKey($descendantIds)->with('companies')->get()->keyBy('id');
        $offsets = [(int) $record->id => 0];
        $queue = [(int) $record->id];

        while ($queue !== []) {
            $currentId = array_shift($queue);
            foreach ($descendants as $descendant) {
                if ((int) $descendant->parent_id === $currentId) {
                    $offsets[(int) $descendant->id] = $offsets[$currentId] + 1;
                    $queue[] = (int) $descendant->id;
                }
            }
        }

        foreach ($descendants as $descendant) {
            $descLevel = $targetLevel + ($offsets[(int) $descendant->id] ?? 1);
            if ($descLevel > self::MAX_DEPTH) {
                static::fail($prefix.'parent_id', 'max_depth_exceeded');
            }
            if ($descLevel <= 2 && ! static::isGroupLevel($descendant)) {
                static::fail($prefix.'parent_id', 'general_level_forbids_companies');
            }
            if ($descLevel === self::MAX_DEPTH && static::companyIds($descendant) === []) {
                static::fail($prefix.'parent_id', 'subdepartment_requires_companies');
            }
        }
    }

    /** Enforce three-layer structure: L1 & L2 are general without companies; L3 requires companies. */
    protected static function assertStructure(array $data, ?Department $record, array $companyIds, string $prefix): void
    {
        $targetLevel = static::targetLevelForParent($data['parent_id']);
        $moving = $record?->exists && (int) ($record->parent_id ?? 0) !== (int) ($data['parent_id'] ?? 0);

        if ((! $record?->exists || $moving) && $targetLevel > self::MAX_DEPTH) {
            static::fail($prefix.'parent_id', 'max_depth_exceeded');
        }

        if ($moving && ($targetLevel + static::subtreeDepth((int) $record->id) - 1) > self::MAX_DEPTH) {
            static::fail($prefix.'parent_id', 'max_depth_exceeded');
        }

        $isGroup = (bool) $data['is_global'] && $companyIds === [];
        $untouchedLegacy = $record
            && (int) ($record->parent_id ?? 0) === (int) ($data['parent_id'] ?? 0)
            && (bool) $record->is_global === (bool) $data['is_global']
            && static::companyIds($record) === static::normalizedIds($companyIds);

        if ($untouchedLegacy) {
            return;
        }

        if ($targetLevel === 1 && ! $isGroup) {
            static::fail($prefix.'parent_id', 'root_requires_group_scope');
        }

        if ($targetLevel === 2 && ! $isGroup) {
            static::fail($prefix.'company_id', 'general_level_forbids_companies');
        }

        if ($targetLevel === 3 && ($isGroup || $companyIds === [])) {
            static::fail($prefix.'company_id', 'subdepartment_requires_companies');
        }
    }

    public static function authorizeAssignment(?int $parentId, array $companyIds): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new AuthorizationException;
        }
        if ($parentId && ! CompanyAccessScopeService::apply(Department::query())->whereKey($parentId)->exists()) {
            throw new AuthorizationException;
        }
        if (static::targetLevelForParent($parentId) <= 2 && ! $user->isSuperAdmin()) {
            throw new AuthorizationException;
        }
        if (! $user->isSuperAdmin()) {
            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if ($authorizedIds === [] || array_diff($companyIds, $authorizedIds)) {
                throw new AuthorizationException;
            }
        }
        if (DB::table('companies')->whereIn('id', $companyIds)->count() !== count($companyIds)) {
            static::fail('company_id', 'company_missing');
        }
    }

    public static function authorizeUpdates(Collection $records): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new AuthorizationException;
        }
        if ($user->isSuperAdmin()) {
            return;
        }
        $visibleIds = CompanyAccessScopeService::apply(Department::query())->whereKey($records->pluck('id'))->pluck('id')->all();
        $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
        foreach ($records as $record) {
            Gate::authorize('update', $record);
            if (static::levelOf($record) <= 2) {
                throw new AuthorizationException;
            }
            if (! in_array($record->id, $visibleIds) || array_diff(static::companyIds($record), $companyIds)) {
                throw new AuthorizationException;
            }
        }
    }

    protected static function normalizedIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        return $ids;
    }

    public static function companyIds(Department $record): array
    {
        return static::normalizedIds($record->getAssignedCompanyIds());
    }

    /** Serialize department writes on MySQL, including insertion into an empty sibling group. */
    public static function transaction(Closure $callback): mixed
    {
        $connection = DB::connection();
        $lock = 'orcal_departments_'.substr(hash('sha256', $connection->getDatabaseName()), 0, 32);
        $mysql = $connection->getDriverName() === 'mysql';
        if ($mysql && (int) $connection->selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lock])->acquired !== 1) {
            static::fail('parent_id', 'busy');
        }
        try {
            return $connection->transaction($callback, 3);
        } finally {
            static::clearDisplayCache();
            request()->attributes->remove('department_orderability');
            if ($mysql) {
                $connection->selectOne('SELECT RELEASE_LOCK(?)', [$lock]);
            }
        }
    }

    /**
     * Code prefix for a new department, based on where it is created.
     * The prefix classifies the record at creation time only; it never drives level, sorting or permissions.
     */
    public static function codePrefixForParent(?int $parentId): string
    {
        if (! $parentId) {
            return self::PREFIX_ADMINISTRATION;
        }

        $parent = Department::query()->whereKey($parentId)->first(['id', 'parent_id']);

        if (! $parent) {
            return self::PREFIX_ADMINISTRATION;
        }

        return $parent->parent_id ? self::PREFIX_SUB : self::PREFIX_MAIN;
    }

    public static function generateCode(?int $parentId): string
    {
        $prefix = static::codePrefixForParent($parentId);

        return CodeGeneratorService::generate(
            modelClass: Department::class,
            column: 'department_code',
            prefix: $prefix,
            padLength: 4,
            scopeQuery: fn ($query) => $query->where('parent_id', $parentId)->where('department_code', 'like', $prefix.'-%'),
            ignoreCompany: true,
            uniqueWithinScope: true,
            sequenceFromMaximum: true,
        );
    }

    /** Keep codes stable on moves; reject a destination containing the same code. */
    public static function assertCodeAvailable(Department $department, string $errorKeyPrefix = ''): void
    {
        if (blank($department->department_code)) {
            return;
        }

        $query = Department::withoutGlobalScopes()
            ->where('parent_id', $department->parent_id)
            ->where('department_code', $department->department_code);

        if ($department->exists) {
            $query->whereKeyNot($department->getKey());
        }

        if ($query->exists()) {
            static::fail($errorKeyPrefix.'parent_id', 'code_conflict');
        }
    }

    /**
     * Ancestor ids from the direct parent up to the root. Fails on a cycle or a missing parent link.
     *
     * @return array<int, int>
     */
    public static function ancestorIds(Department $department, bool $forDisplay = false): array
    {
        $ids = [];
        $seen = [(int) $department->getKey() => true];
        $parentId = $department->parent_id;

        while ($parentId) {
            if (isset($seen[(int) $parentId])) {
                throw new DomainException('Department hierarchy contains a cycle.');
            }

            $seen[(int) $parentId] = true;
            $ids[] = (int) $parentId;
            $parent = $forDisplay
                ? static::displayAncestor((int) $parentId)
                : Department::query()->find($parentId, ['id', 'parent_id']);
            if (! $parent) {
                throw new DomainException('Department hierarchy contains a missing parent.');
            }
            $parentId = $parent->parent_id;
        }

        return $ids;
    }

    /** Reuse topology reads within this request; authorization queries remain separate. */
    protected static function displayAncestor(int $id): ?Department
    {
        $cache = request()->attributes->get('department_display_ancestors', []);
        if (! array_key_exists($id, $cache)) {
            $cache[$id] = Department::query()->find($id, ['id', 'parent_id']);
            request()->attributes->set('department_display_ancestors', $cache);
        }

        return $cache[$id];
    }

    public static function clearDisplayCache(): void
    {
        request()->attributes->remove('department_display_ancestors');
    }

    /**
     * 1 for an administration, 2 for a main department, 3 for a sub department.
     */
    public static function levelOf(Department $department): int
    {
        return count(static::ancestorIds($department, forDisplay: true)) + 1;
    }

    public static function targetLevelForParent(?int $parentId): int
    {
        if (! $parentId) {
            return 1;
        }

        $parent = static::displayAncestor($parentId)
            ?? Department::query()->find($parentId, ['id', 'parent_id']);

        if (! $parent) {
            return 1;
        }

        return static::levelOf($parent) + 1;
    }

    public static function subtreeDepth(int $departmentId): int
    {
        $depth = 0;
        $seen = [$departmentId => true];
        $frontier = [$departmentId];

        while ($frontier !== []) {
            $depth++;
            $children = Department::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $frontier = [];

            foreach ($children as $childId) {
                if (isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $frontier[] = $childId;
            }
        }

        return $depth;
    }

    public static function levelKey(Department $department): string
    {
        return match (static::levelOf($department)) {
            1 => 'administration',
            2 => 'main',
            default => 'sub',
        };

    }

    public static function levelLabel(Department $department): string
    {
        return __('organization.departments.hierarchy.levels.'.static::levelKey($department));
    }

    public static function visiblePath(Department $department): string
    {
        $ids = [...array_reverse(static::ancestorIds($department, forDisplay: true)), (int) $department->id];
        $names = CompanyAccessScopeService::apply(Department::query())->whereKey($ids)->pluck('name', 'id');

        return collect($ids)->filter(fn ($id) => $names->has($id))->map(fn ($id) => $names[$id])->implode(' / ');
    }

    /**
     * All descendant ids, fetched one tree level per query.
     *
     * @return array<int, int>
     */
    public static function descendantIds(int $departmentId): array
    {
        $all = [];
        $seen = [$departmentId => true];
        $frontier = [$departmentId];

        while ($frontier !== []) {
            $children = Department::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $frontier = [];

            foreach ($children as $childId) {
                if (isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $all[] = $childId;
                $frontier[] = $childId;
            }
        }

        return $all;
    }

    /**
     * Validate the parent choice for a new or existing department.
     */
    public static function assertParentAssignable(?Department $department, ?int $parentId, string $errorKeyPrefix = ''): void
    {
        if (! $parentId) {
            return;
        }

        if (! Department::query()->whereKey($parentId)->exists()) {
            static::fail($errorKeyPrefix.'parent_id', 'parent_missing');
        }

        if ($department?->exists) {
            $id = (int) $department->getKey();

            if ($parentId === $id || in_array($parentId, static::descendantIds($id), true)) {
                static::fail($errorKeyPrefix.'parent_id', 'cycle');
            }
        }
    }

    /**
     * A department's companies must be inside its parent's companies when the parent has a defined assignment.
     * An unrestricted child (no companies) under a restricted parent is also rejected.
     *
     * @param  array<int, int|string>  $companyIds  The final company set for shared departments.
     */
    public static function assertCompaniesWithinParent(?int $parentId, bool $isGlobal, ?int $companyId, array $companyIds, string $errorKeyPrefix = ''): void
    {
        if (! $parentId) {
            return;
        }

        $parent = Department::query()->find($parentId);

        if (! $parent) {
            return;
        }

        $parentCompanyIds = array_map('intval', $parent->getAssignedCompanyIds());

        if ($parentCompanyIds === []) {
            return;
        }

        $childCompanyIds = $isGlobal
            ? array_map('intval', $companyIds)
            : array_filter([(int) $companyId]);

        if ($childCompanyIds === [] || array_diff($childCompanyIds, $parentCompanyIds) !== []) {
            static::fail($errorKeyPrefix.'parent_id', 'companies_outside_parent');
        }
    }

    /**
     * Validate form data before saving a department (parent, cycles and company assignment).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, int|string>  $companyIds
     */
    public static function assertValidSave(array $data, ?Department $record, array $companyIds, string $errorKeyPrefix = ''): void
    {
        $parentId = ($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;

        static::assertParentAssignable($record, $parentId, $errorKeyPrefix);
        static::assertManagerValid(
            filled($data['manager_id'] ?? null) ? (int) $data['manager_id'] : null,
            $parentId,
            (bool) ($data['is_global'] ?? false),
            $companyIds,
            filled($data['company_id'] ?? null) ? (int) $data['company_id'] : null,
            $errorKeyPrefix,
        );
        static::assertCompaniesWithinParent(
            $parentId,
            (bool) ($data['is_global'] ?? false),
            isset($data['company_id']) ? (int) $data['company_id'] : null,
            $companyIds,
            $errorKeyPrefix,
        );
        if ($record?->exists) {
            $finalIds = (bool) ($data['is_global'] ?? false)
                ? $companyIds : array_filter([(int) ($data['company_id'] ?? 0)]);
            static::assertDependentsCompatible($record, $finalIds, $errorKeyPrefix);
        }
    }

    public static function assertManagerValid(
        ?int $managerId,
        ?int $parentId,
        bool $isGlobal,
        array $companyIds = [],
        ?int $companyId = null,
        string $errorKeyPrefix = '',
    ): void {
        if ($managerId === null) {
            return;
        }

        if (! auth()->user()?->isSuperAdmin()) {
            throw new AuthorizationException;
        }

        if ($parentId !== null || ! $isGlobal || $companyIds !== [] || $companyId !== null) {
            static::fail($errorKeyPrefix.'manager_id', 'manager_only_for_first_layer');
        }

        $valid = Employee::query()
            ->whereKey($managerId)
            ->where('status', 'active')
            ->saudi()
            ->exists();

        if (! $valid) {
            static::fail($errorKeyPrefix.'manager_id', 'manager_must_be_active_saudi');
        }
    }

    /** Reject narrowing assignments that would strand children, employees or job titles. */
    public static function assertDependentsCompatible(Department $record, array $companyIds, string $prefix = ''): void
    {
        $companyIds = static::normalizedIds($companyIds);
        if ($companyIds === static::companyIds($record->fresh())) {
            return;
        }

        if ($companyIds === []) {
            return;
        }

        if ($record->children()->exists()) {
            static::fail($prefix.'company_id', 'max_depth_exceeded');
        }

        foreach (['employees', 'job_titles'] as $table) {
            if (! Schema::hasColumn($table, 'company_id')) {
                continue;
            }
            $assigned = DB::table($table)
                ->where('department_id', $record->id)
                ->whereNotNull('company_id')
                ->when(Schema::hasColumn($table, 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'));
            if ($assigned->whereNotIn('company_id', $companyIds)->exists()) {
                static::fail($prefix.'company_id', 'assignments_outside_scope');
            }
        }
    }

    public static function nextSortOrder(?int $parentId): int
    {
        return ((int) Department::query()->where('parent_id', $parentId)->max('sort_order')) + 1;
    }

    /**
     * Move a department (with its whole branch) under a new parent, as the last sibling by default.
     */
    public static function move(Department $department, ?int $newParentId, ?int $position = null): Department
    {
        return static::transaction(function () use ($department, $newParentId, $position) {
            $department = Department::query()->lockForUpdate()->findOrFail($department->id);
            $descendantIds = static::descendantIds((int) $department->id);
            static::authorizeUpdates(Department::query()->whereKey([$department->id, ...$descendantIds])->get());
            static::authorizeAssignment($newParentId, static::companyIds($department));
            static::assertParentAssignable($department, $newParentId);

            $targetLevel = static::targetLevelForParent($newParentId);
            if (($targetLevel + static::subtreeDepth((int) $department->id) - 1) > self::MAX_DEPTH) {
                static::fail('parent_id', 'max_depth_exceeded');
            }

            if ($targetLevel <= 2 && ! static::isGroupLevel($department)) {
                static::fail('parent_id', $targetLevel === 1 ? 'root_requires_group_scope' : 'general_level_forbids_companies');
            }

            if ($targetLevel === self::MAX_DEPTH && static::companyIds($department) === []) {
                static::fail('parent_id', 'subdepartment_requires_companies');
            }

            static::assertBranchLayerCompatibility($department, $targetLevel);

            $oldParentId = $department->parent_id ? (int) $department->parent_id : null;

            if ($oldParentId === $newParentId) {
                return $department;
            }

            $siblings = Department::query()
                ->where('parent_id', $newParentId)
                ->lockForUpdate()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $position = $position === null ? count($siblings) : max(0, min($position, count($siblings)));
            array_splice($siblings, $position, 0, [(int) $department->getKey()]);

            $department->parent_id = $newParentId;
            $department->save();

            static::authorizeUpdates(Department::query()->whereKey($siblings)->get());
            static::writeOrder($siblings);

            return $department->refresh();
        });
    }

    /**
     * Reorder one sibling group. The ids must be exactly the current children of the parent.
     *
     * @param  array<int, int|string>  $orderedIds
     */
    public static function reorder(?int $parentId, array $orderedIds): void
    {
        static::transaction(function () use ($parentId, $orderedIds) {
            $current = Department::query()
                ->where('parent_id', $parentId)
                ->lockForUpdate()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $ordered = array_map('intval', $orderedIds);

            if (count($ordered) !== count(array_unique($ordered)) || array_diff($current, $ordered) || array_diff($ordered, $current)) {
                static::fail('sort_order', 'reorder_mismatch');
            }

            static::authorizeUpdates(Department::query()->whereKey($ordered)->get());
            static::writeOrder($ordered);
        });
    }

    /**
     * Reasons that block deleting a department, as lang keys (empty when it can be deleted).
     *
     * @return array<int, string>
     */
    public static function deletionBlockers(Department $department): array
    {
        $blockers = [];

        if ($department->children()->exists()) {
            $blockers[] = 'has_children';
        }

        if ($department->employees()->exists()) {
            $blockers[] = 'has_employees';
        }
        if (Schema::hasTable('job_titles') && DB::table('job_titles')
            ->where('department_id', $department->id)
            ->when(Schema::hasColumn('job_titles', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->exists()) {
            $blockers[] = 'has_job_titles';
        }

        return $blockers;
    }

    /**
     * Move a department one step up (-1) or down (+1) among its siblings.
     */
    public static function shift(Department $department, int $direction): void
    {
        static::transaction(function () use ($department, $direction) {
            $department = Department::query()->lockForUpdate()->findOrFail($department->id);
            $siblings = Department::query()
                ->where('parent_id', $department->parent_id)
                ->lockForUpdate()
                ->ordered()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            static::authorizeUpdates(Department::query()->whereKey($siblings)->get());

            $index = array_search((int) $department->getKey(), $siblings, true);
            $target = $index === false ? false : $index + ($direction < 0 ? -1 : 1);

            if ($target === false || ! isset($siblings[$target])) {
                return;
            }

            [$siblings[$index], $siblings[$target]] = [$siblings[$target], $siblings[$index]];

            static::writeOrder($siblings);
        });
    }

    public static function canReorder(Department $department): bool
    {
        $cache = request()->attributes->get('department_orderability', []);
        $key = (auth()->id() ?? 'guest').':'.($department->parent_id ?? 'root');
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            static::authorizeUpdates(Department::query()->where('parent_id', $department->parent_id)->with('companies')->get());
            $allowed = true;
        } catch (AuthorizationException) {
            $allowed = false;
        }
        $cache[$key] = $allowed;
        request()->attributes->set('department_orderability', $cache);

        return $allowed;
    }

    protected static function placeAt(Department $department, int $position): void
    {
        $siblings = Department::query()->where('parent_id', $department->parent_id)->ordered()->get();
        static::authorizeUpdates($siblings);
        $ids = $siblings->where('id', '!=', $department->id)->pluck('id')->all();
        array_splice($ids, min(max(0, $position - 1), count($ids)), 0, [$department->id]);
        static::writeOrder($ids);
    }

    /**
     * Parent choices as "Root / Main / Sub" paths, limited to the departments the user may see.
     * Ancestors the user cannot see are left out of the displayed path, never revealed.
     *
     * @param  Builder<Department>  $visibleQuery
     * @return array<int, string>
     */
    public static function parentOptions($visibleQuery, ?Department $record = null): array
    {
        $excluded = [];
        $maxAllowedParentLevel = self::MAX_DEPTH - 1;

        if ($record?->exists) {
            $excluded = [(int) $record->getKey(), ...static::descendantIds((int) $record->getKey())];
            $maxAllowedParentLevel = max(0, self::MAX_DEPTH - static::subtreeDepth((int) $record->getKey()));
        }

        if ($maxAllowedParentLevel <= 0) {
            return [];
        }

        $departments = $visibleQuery
            ->clone()
            ->where(fn (Builder $query) => $query->where('status', 'active')
                ->when($record?->parent_id, fn (Builder $existingParent) => $existingParent->orWhere('id', $record->parent_id)))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name'])
            ->reject(fn (Department $department) => in_array((int) $department->id, $excluded, true)
                || static::levelOf($department) > $maxAllowedParentLevel);

        return static::pathLabels($departments);
    }

    /**
     * Labels like "Root / Main / Sub" for the given departments, using only ancestors present in the collection.
     * Ancestors outside the collection (for example ones the user cannot see) are never added to a label.
     *
     * @param  Collection<int, Department>  $departments
     * @return array<int, string>
     */
    public static function pathLabels($departments): array
    {
        $byId = $departments->keyBy('id');
        $options = [];

        foreach ($departments as $department) {
            $path = [$department->name];
            $seen = [(int) $department->id => true];
            $parent = $byId->get($department->parent_id);

            while ($parent && ! isset($seen[(int) $parent->id])) {
                $seen[(int) $parent->id] = true;
                array_unshift($path, $parent->name);
                $parent = $byId->get($parent->parent_id);
            }

            $options[(int) $department->id] = implode(' / ', $path);
        }

        asort($options);

        return $options;
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    protected static function writeOrder(array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $id) {
            Department::query()->whereKey($id)->update(['sort_order' => $index + 1]);
        }
    }

    protected static function isGroupLevel(Department $department): bool
    {
        return $department->is_global && ! $department->companies()->exists();
    }

    protected static function fail(string $key, string $error): never
    {
        throw ValidationException::withMessages([
            $key => __('organization.departments.hierarchy.errors.'.$error),
        ]);
    }
}
