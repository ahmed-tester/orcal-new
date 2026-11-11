<?php

namespace App\Filament\Resources\Violations\Pages;

use App\Filament\Resources\Violations\ViolationResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\ViolationSummaryService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViolationsSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = ViolationResource::class;

    #[Url]
    public mixed $categoryId = null;

    #[Url]
    public mixed $typeId = null;

    #[Url]
    public mixed $stage = null;

    #[Locked]
    public ?int $detailsId = null;

    public function getView(): string
    {
        return 'filament.resources.violations.pages.violations-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Gate::allows('viewAny', Violation::class);
    }

    public function getTitle(): string|Htmlable
    {
        return __('violations.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('violations.summary.create_violation'))
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn () => ViolationResource::getUrl('create'))
                ->visible(fn () => ViolationResource::canCreate()),
            Action::make('detailed')
                ->label(__('violations.summary.detailed'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => ViolationResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['categoryId', 'typeId', 'stage'];
    }

    public function handleFilterBarUpdate(): void
    {
        $this->resetPage();
        $this->detailsId = null;
        $this->dispatch('close-modal', id: 'violation-details');
    }

    public function filters(): array
    {
        return $this->filterBarState([
            'category_id' => $this->categoryId,
            'type_id' => $this->typeId,
            'stage' => $this->stage,
        ]);
    }

    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar()
            || ! empty($this->categoryId)
            || ! empty($this->typeId)
            || ! empty($this->stage);
    }

    public function resetFilters(): void
    {
        $this->resetFilterBar();
        $this->categoryId = is_array($this->categoryId) ? [] : null;
        $this->typeId = is_array($this->typeId) ? [] : null;
        $this->stage = is_array($this->stage) ? [] : null;
        $this->handleFilterBarUpdate();
    }

    public function getCategoriesProperty(): array
    {
        return ViolationCategory::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->toArray();
    }

    public function getViolationTypesProperty(): array
    {
        return ViolationType::where('is_active', true)
            ->when(! empty($this->categoryId), fn ($q) => $q->whereIn('violation_category_id', (array) $this->categoryId))
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    #[Computed]
    public function summary(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Violation::class);

        return ViolationSummaryService::summaryQuery($this->filters())->paginate(15);
    }

    #[Computed]
    public function stats(): object
    {
        Gate::authorize('viewAny', Violation::class);

        return ViolationSummaryService::stats($this->filters());
    }

    #[Computed]
    public function details(): ?array
    {
        Gate::authorize('viewAny', Violation::class);

        return $this->detailsId === null ? null : ViolationSummaryService::details($this->detailsId, $this->filters());
    }

    public function openDetails(int $id): void
    {
        Gate::authorize('viewAny', Violation::class);
        $this->detailsId = $id;
        $this->dispatch('open-modal', id: 'violation-details');
    }

    public function closeDetails(): void
    {
        $this->detailsId = null;
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('viewAny', Violation::class);
        $query = ViolationSummaryService::summaryQuery($this->filters());

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $columns = ['company', 'commercial_registration', 'violation_count', ...ViolationSummaryService::TOTALS];
            fputcsv($output, array_map(fn ($key) => __('violations.summary.'.$key), $columns), escape: '');

            foreach ($query->lazy(250) as $row) {
                fputcsv($output, [
                    self::csvText($row->company?->trade_name ?? __('violations.summary.no_company')),
                    self::csvText($row->company?->commercial_registration_number ?? '—'),
                    $row->violation_count,
                    ...array_map(fn ($key) => number_format((float) $row->{$key}, 2, '.', ''), ViolationSummaryService::TOTALS),
                ], escape: '');
            }

            fclose($output);
        }, 'violations-summary-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function csvText(?string $value): string
    {
        $text = trim((string) $value);
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@'], true)) {
            return "'".$text;
        }

        return $text;
    }
}

