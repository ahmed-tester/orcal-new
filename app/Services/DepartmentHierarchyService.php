<?php

namespace App\Services;

use App\Enums\DepartmentScope;
use App\Models\Department;
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
    public const PREFIX_ADMINISTRATION = 'ADM';

    public const PREFIX_MAIN = 'SEC';

    public const PREFIX_SUB = 'SUB';

    /** Creation defaults come from the authorized parent, not editable browser fields. */
    public static function creationDefaults(?Department $parent): array
    {
        $scope = $parent ? DepartmentScopeService::resolveScope($parent) : DepartmentScope::FAWAZ_GROUP;
        $companies = $parent?->getAssignedCompanyIds() ?? [];
        if ($parent && ! auth()->user()?->isSuperAdmin()) {
            $companies = array_values(array_intersect($companies, CompanyFormService::getAuthorizedCompanyIds()));
        }
        $inheritedSingle = $parent && $scope === DepartmentScope::SINGLE;
        $scope = ! $parent ? DepartmentScope::FAWAZ_GROUP
            : ($scope === DepartmentScope::SHARED ? DepartmentScope::SHARED : DepartmentScope::SINGLE);

        return [
            'parent_id' => $parent?->id,
            'department_scope' => $scope->value,
            'is_global' => $scope !== DepartmentScope::SINGLE,
            'company_id' => $inheritedSingle ? $parent->company_id : null,
            'companies' => $scope === DepartmentScope::SHARED ? $companies : [],
        ];
    }

    public static function createFromContext(array $data, ?int $parentId, string $errorKeyPrefix = ''): Department
    {
        return static::transaction(function () use ($data, $parentId, $errorKeyPrefix) {
            $parent = $parentId ? CompanyAccessScopeService::apply(Department::query())
                ->with('companies')->lockForUpdate()->findOrFail($parentId) : null;
            $defaults = static::creationDefaults($parent);
            $data = array_replace($defaults, $data);
            $data['parent_id'] = $parentId;
            if (! $parent || DepartmentScopeService::resolveScope($parent) === DepartmentScope::SINGLE) {
                $data = array_replace($data, $defaults);
            }
            $scope = DepartmentScope::tryFrom((string) $data['department_scope']);
            if (! $scope) {
                static::fail($errorKeyPrefix.'department_scope', 'scope_invalid');
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

            [$data, $companyIds] = static::prepareSave($data, $record, $scope, $companyIds);
            $parentId = $data['parent_id'];

            static::authorizeAssignment($parentId, $companyIds);
            static::assertStructure($data, $record, $companyIds, $errorKeyPrefix);
            static::assertValidSave($data, $record, $companyIds, $errorKeyPrefix);

            $moving = $record && $record->parent_id != $parentId;
            $assignmentChanged = $record && static::companyIds($record) != static::normalizedIds($companyIds);
            if ($moving || $assignmentChanged) {
                static::assertBranchAssignable($record, $errorKeyPrefix);
            }

            $position = isset($data['sort_order']) ? max(1, (int) $data['sort_order']) : null;
            $reordering = $record && ! $moving && $position !== null && $position !== $record->sort_order;
            $data['sort_order'] = ($moving || ! $record) ? static::nextSortOrder($parentId) : $record->sort_order;
            unset($data['department_code']);

            $record ??= new Department;
            $record->pendingCompanyIds = $companyIds;
            try {
                $record->fill($data)->save();
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
        $data = array_replace($record?->only(['name', 'description', 'parent_id', 'company_id', 'status']) ?? ['status' => 'active'], $data);
        $data = DepartmentScopeService::mutateBeforeSave($data, $scope, $record);
        $companyIds = match ($scope) {
            DepartmentScope::SINGLE => array_filter([(int) ($data['company_id'] ?? 0)]),
            DepartmentScope::SHARED => static::normalizedIds($companyIds),
            DepartmentScope::FAWAZ_GROUP => [],
        };
        $data['company_id'] = $companyIds[0] ?? null;
        $data['parent_id'] = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;

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

    /** Existing unclassified roots may be edited, but new roots must be group administrations. */
    protected static function assertStructure(array $data, ?Department $record, array $companyIds, string $prefix): void
    {
        $isGroup = (bool) $data['is_global'] && $companyIds === [];
        if ($data['parent_id'] === null) {
            $legacyRoot = $record && $record->parent_id === null
                && (bool) $record->is_global === (bool) $data['is_global']
                && static::companyIds($record) === static::normalizedIds($companyIds);
            if (! $isGroup && ! $legacyRoot) {
                static::fail($prefix.'parent_id', 'root_requires_group_scope');
            }
        } elseif ($isGroup || $companyIds === []) {
            static::fail($prefix.'company_id', 'child_requires_company_scope');
        }
    }

    protected static function authorizeAssignment(?int $parentId, array $companyIds): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new AuthorizationException;
        }
        if ($parentId && ! CompanyAccessScopeService::apply(Department::query())->whereKey($parentId)->exists()) {
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

    protected static function authorizeUpdates(Collection $records): void
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

    protected static function companyIds(Department $record): array
    {
        return static::normalizedIds($record->getAssignedCompanyIds());
    }

    /** Serialize department writes on MySQL, including insertion into an empty sibling group. */
    protected static function transaction(Closure $callback): mixed
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
            scopeQuery: fn ($query) => $query->where('department_code', 'like', $prefix.'-%'),
            ignoreCompany: true,
        );
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
     * 1 for an administration, 2 for a main department, 3 and deeper for sub departments.
     */
    public static function levelOf(Department $department): int
    {
        return count(static::ancestorIds($department, forDisplay: true)) + 1;
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

    /** Reject narrowing assignments that would strand children, employees, users or job titles. */
    public static function assertDependentsCompatible(Department $record, array $companyIds, string $prefix = ''): void
    {
        $companyIds = static::normalizedIds($companyIds);
        if ($companyIds === static::companyIds($record->fresh())) {
            return;
        }
        if ($companyIds !== []) {
            foreach ($record->children()->with('companies')->get() as $child) {
                $childIds = static::companyIds($child);
                if ($childIds === [] || array_diff($childIds, $companyIds)) {
                    static::fail($prefix.'company_id', 'children_outside_scope');
                }
            }
        }
        foreach (['employees', 'users', 'job_titles'] as $table) {
            if (! Schema::hasColumn($table, 'company_id')) {
                continue;
            }
            $assigned = DB::table($table)->where('department_id', $record->id)->whereNotNull('company_id');
            $conflict = $companyIds === [] ? $assigned->exists() : $assigned->whereNotIn('company_id', $companyIds)->exists();
            if ($conflict) {
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
            static::authorizeUpdates(Department::query()->whereKey([$department->id, ...static::descendantIds((int) $department->id)])->get());
            static::authorizeAssignment($newParentId, static::companyIds($department));
            static::assertParentAssignable($department, $newParentId);

            if ($newParentId === null && ! static::isGroupLevel($department)) {
                static::fail('parent_id', 'root_requires_group_scope');
            }

            if ($newParentId !== null) {
                foreach ([$department->getKey(), ...static::descendantIds((int) $department->getKey())] as $id) {
                    $branchNode = Department::query()->findOrFail($id);
                    $nodeParentId = (int) $id === (int) $department->getKey() ? $newParentId : $branchNode->parent_id;
                    static::assertCompaniesWithinParent(
                        $nodeParentId,
                        (bool) $branchNode->is_global,
                        $branchNode->company_id,
                        $branchNode->companies()->pluck('companies.id')->all(),
                    );
                }
            }

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

        if ($department->employees()->exists() || DB::table('users')->where('department_id', $department->getKey())->exists()) {
            $blockers[] = 'has_employees';
        }
        if (Schema::hasTable('job_titles') && DB::table('job_titles')->where('department_id', $department->id)->exists()) {
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

        if ($record?->exists) {
            $excluded = [(int) $record->getKey(), ...static::descendantIds((int) $record->getKey())];
        }

        $departments = $visibleQuery
            ->clone()
            ->where(fn (Builder $query) => $query->where('status', 'active')
                ->when($record?->parent_id, fn (Builder $existingParent) => $existingParent->orWhere('id', $record->parent_id)))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'parent_id', 'name'])
            ->reject(fn (Department $department) => in_array((int) $department->id, $excluded, true));

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
