<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentHierarchyService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    #[Url(as: 'parent')]
    public ?int $parent = null;

    protected ?Department $resolvedParent = null;

    public function mount(): void
    {
        parent::mount();

        $this->resolveCurrentParent();
    }

    public function table(Table $table): Table
    {
        return parent::table($table)->modifyQueryUsing(function (Builder $query) {
            if ($this->isFlatMode()) {
                return $query;
            }

            $currentParent = $this->resolveCurrentParent();

            return $currentParent
                ? $query->where('parent_id', $currentParent->getKey())
                : $query->whereNull('parent_id')->where('is_global', true)->whereDoesntHave('companies');
        });
    }

    public function getTitle(): string|Htmlable
    {
        return $this->resolveCurrentParent()?->name ?? parent::getTitle();
    }

    public function getBreadcrumbs(): array
    {
        $currentParent = $this->resolveCurrentParent();

        if (! $currentParent) {
            return [];
        }

        $ancestorIds = DepartmentHierarchyService::ancestorIds($currentParent, forDisplay: true);
        $visibleAncestors = DepartmentResource::getEloquentQuery()
            ->whereIn('id', $ancestorIds)
            ->pluck('name', 'id');

        $breadcrumbs = [DepartmentResource::getUrl('index') => __('organization.departments.hierarchy.root_breadcrumb')];

        foreach (array_reverse($ancestorIds) as $ancestorId) {
            if ($visibleAncestors->has($ancestorId)) {
                $breadcrumbs[DepartmentResource::getUrl('index', ['parent' => $ancestorId])] = $visibleAncestors[$ancestorId];
            }
        }

        $breadcrumbs[DepartmentResource::getUrl('index', ['parent' => $currentParent->getKey()])] = $currentParent->name;

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('organization.departments.hierarchy.actions.back'))
                ->icon('heroicon-m-arrow-uturn-right')
                ->color('gray')
                ->visible(fn () => $this->resolveCurrentParent() !== null)
                ->url(fn () => DepartmentResource::getUrl('index', ['parent' => $this->visibleParentId()])),

            CreateAction::make()
                ->label(fn () => $this->createLabel())
                ->visible(fn () => $this->canCreateAtCurrentLevel())
                ->url(fn () => DepartmentResource::getUrl('create', array_filter(['parent' => $this->resolveCurrentParent()?->getKey()]))),
        ];
    }

    protected function canCreateAtCurrentLevel(): bool
    {
        if (! DepartmentResource::canCreate()) {
            return false;
        }

        $currentParent = $this->resolveCurrentParent();

        if (! $currentParent || DepartmentHierarchyService::levelOf($currentParent) === 1) {
            return auth()->user()?->isSuperAdmin() ?? false;
        }

        return DepartmentHierarchyService::levelOf($currentParent) < DepartmentHierarchyService::MAX_DEPTH;
    }

    public function shouldShowChildrenCountColumn(): bool
    {
        if ($this->isFlatMode()) {
            return true;
        }

        $currentParent = $this->resolveCurrentParent();

        return ! $currentParent || DepartmentHierarchyService::levelOf($currentParent) < DepartmentHierarchyService::MAX_DEPTH - 1;
    }

    protected function isFlatMode(): bool
    {
        return (bool) data_get($this->tableFilters, 'all_levels.isActive', false)
            || (bool) data_get($this->tableFilters, 'unclassified.isActive', false);
    }

    /**
     * The opened department; a parent id the user cannot see is treated as not found.
     */
    protected function resolveCurrentParent(): ?Department
    {
        if (! $this->parent) {
            return null;
        }

        if ($this->resolvedParent?->getKey() !== $this->parent) {
            $parent = DepartmentResource::getEloquentQuery()->whereKey($this->parent)->first() ?? abort(404);
            if (DepartmentHierarchyService::levelOf($parent) >= DepartmentHierarchyService::MAX_DEPTH) {
                abort(404);
            }
            $this->resolvedParent = $parent;
        }

        return $this->resolvedParent;
    }

    protected function visibleParentId(): ?int
    {
        $parentId = $this->resolveCurrentParent()?->parent_id;

        if (! $parentId) {
            return null;
        }

        return DepartmentResource::getEloquentQuery()->whereKey($parentId)->exists() ? (int) $parentId : null;
    }

    protected function createLabel(): string
    {
        $currentParent = $this->resolveCurrentParent();

        if (! $currentParent) {
            return __('organization.departments.hierarchy.actions.add_administration');
        }

        return DepartmentHierarchyService::levelOf($currentParent) === 1
            ? __('organization.departments.hierarchy.actions.add_main')
            : __('organization.departments.hierarchy.actions.add_sub');
    }
}
