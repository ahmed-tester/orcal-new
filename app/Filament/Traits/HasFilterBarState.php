<?php

namespace App\Filament\Traits;

use App\Models\Branch;
use App\Models\Company;
use App\Services\CompanyFormService;
use App\Services\FilterBarService;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

trait HasFilterBarState
{
    public ?string $search = '';

    public mixed $companyId = null;

    public mixed $branchId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public mixed $status = null;

    /**
     * Livewire merges this trait query string into the page's own one automatically.
     */
    protected function queryStringHasFilterBarState(): array
    {
        return [
            'search' => ['except' => ''],
            'companyId' => ['except' => null],
            'branchId' => ['except' => null],
            'dateFrom' => ['except' => null],
            'dateTo' => ['except' => null],
            'status' => ['except' => null],
        ];
    }

    /**
     * Standard filter state plus any page-specific filters.
     */
    public function filterBarState(array $additional = []): array
    {
        return array_merge([
            'search' => $this->search,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->status,
        ], $additional);
    }

    /**
     * Check if any standard or page-specific filter is active.
     */
    public function hasActiveFilterBar(): bool
    {
        if (filled($this->search)) {
            return true;
        }

        if (! empty($this->companyId)) {
            return true;
        }

        if (! empty($this->branchId)) {
            return true;
        }

        if (! empty($this->dateFrom) || ! empty($this->dateTo)) {
            return true;
        }

        if (FilterBarService::values($this->status, ['all']) !== []) {
            return true;
        }

        foreach ($this->filterBarExtraProperties() as $property) {
            if (property_exists($this, $property) && ! empty($this->{$property})) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reset standard and page-specific filters to their defaults.
     */
    public function resetFilterBar(): void
    {
        $this->search = '';
        $this->companyId = is_array($this->companyId) ? [] : null;
        $this->branchId = is_array($this->branchId) ? [] : null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->status = is_array($this->status) ? [] : null;

        foreach ($this->filterBarExtraProperties() as $property) {
            if (property_exists($this, $property)) {
                $this->{$property} = is_array($this->{$property}) ? [] : null;
            }
        }

        $this->handleFilterBarUpdate();
    }

    /**
     * Page-specific filter properties (e.g. categoryId, transactionType).
     *
     * @return array<string>
     */
    protected function filterBarExtraProperties(): array
    {
        return [];
    }

    /**
     * Reset pagination after a filter change.
     */
    public function handleFilterBarUpdate(): void
    {
        if (method_exists($this, 'resetTable')) {
            $this->resetTable();

            return;
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Only the filter bar properties trigger a table/page reset.
     */
    public function updated($property): void
    {
        $properties = array_merge([
            'search',
            'companyId',
            'branchId',
            'dateFrom',
            'dateTo',
            'status',
            'perPage',
        ], $this->filterBarExtraProperties());

        if (in_array($property, $properties, true)) {
            $this->handleFilterBarUpdate();
        }
    }

    /**
     * Alias used by the shared filter bar view.
     */
    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar();
    }

    /**
     * Alias used by the shared filter bar view.
     */
    public function resetFilters(): void
    {
        $this->resetFilterBar();
    }

    /**
     * Authorized companies for the filter dropdown.
     */
    public function getCompaniesProperty()
    {
        $query = Company::query();
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin()) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            $query->whereIn('id', ! empty($companyIds) ? $companyIds : [0]);
        }

        return $query->orderBy('trade_name')->get();
    }

    /**
     * Authorized branches for the filter dropdown.
     */
    public function getBranchesProperty()
    {
        $query = Branch::query();

        if (! empty($this->companyId)) {
            $compIds = is_array($this->companyId) ? $this->companyId : [$this->companyId];
            $query->whereIn('company_id', array_filter($compIds));
        } else {
            $user = auth()->user();
            if ($user && ! $user->isSuperAdmin()) {
                $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
                $query->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0]);
            }
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Locate the filter bar view for the current ListRecords page.
     * Checks explicitly configured $filterBarView property first,
     * then discovers conventional path: filament.resources.<slug>.pages.partials.filter-bar
     */
    protected function getFilterBarView(): ?string
    {
        if (property_exists($this, 'filterBarView') && ! empty($this->filterBarView)) {
            return $this->filterBarView;
        }

        if (method_exists($this, 'getResource')) {
            $resource = $this->getResource();
            $baseName = class_basename($resource);
            $baseSlug = str($baseName)->beforeLast('Resource')->kebab();

            $candidates = [
                "filament.resources.{$baseSlug->plural()}.pages.partials.filter-bar",
                "filament.resources.{$baseSlug}.pages.partials.filter-bar",
            ];

            foreach ($candidates as $view) {
                if (view()->exists($view)) {
                    return $view;
                }
            }
        }

        return null;
    }

    /**
     * Automatically inject the Filter Bar above the table on ListRecords pages.
     */
    public function content(Schema $schema): Schema
    {
        $components = [];

        if (method_exists($this, 'getTabsContentComponent')) {
            $components[] = $this->getTabsContentComponent();
        }

        $components[] = RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE);

        if ($view = $this->getFilterBarView()) {
            $components[] = View::make($view);
        }

        $components[] = EmbeddedTable::make();
        $components[] = RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER);

        return $schema->components($components);
    }
}
