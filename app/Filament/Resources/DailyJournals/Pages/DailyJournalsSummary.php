<?php

namespace App\Filament\Resources\DailyJournals\Pages;

use App\Filament\Resources\DailyJournals\DailyJournalResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\DailyJournal;
use App\Services\DailyJournalSummaryService;
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

class DailyJournalsSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = DailyJournalResource::class;

    #[Url]
    public mixed $isDetailed = null;

    #[Locked]
    public ?int $detailsId = null;

    public function getView(): string
    {
        return 'filament.resources.daily-journals.pages.daily-journals-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Gate::allows('viewAny', DailyJournal::class);
    }

    public function getTitle(): string|Htmlable
    {
        return __('finance.daily_journals.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('finance.daily_journals.summary.create_entry'))
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn () => DailyJournalResource::getUrl('create'))
                ->visible(fn () => DailyJournalResource::canCreate()),
            Action::make('detailed')
                ->label(__('finance.daily_journals.summary.detailed'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => DailyJournalResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['isDetailed'];
    }

    public function handleFilterBarUpdate(): void
    {
        $this->resetPage();
        $this->detailsId = null;
        $this->dispatch('close-modal', id: 'daily-journal-details');
    }

    public function filters(): array
    {
        return $this->filterBarState(['is_detailed' => $this->isDetailed]);
    }

    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar() || ! empty($this->isDetailed);
    }

    public function resetFilters(): void
    {
        $this->resetFilterBar();
        $this->isDetailed = is_array($this->isDetailed) ? [] : null;
        $this->handleFilterBarUpdate();
    }

    #[Computed]
    public function summary(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', DailyJournal::class);

        return DailyJournalSummaryService::summaryQuery($this->filters())->paginate(15);
    }

    #[Computed]
    public function stats(): object
    {
        Gate::authorize('viewAny', DailyJournal::class);

        return DailyJournalSummaryService::stats($this->filters());
    }

    #[Computed]
    public function details(): ?array
    {
        Gate::authorize('viewAny', DailyJournal::class);

        return $this->detailsId === null ? null : DailyJournalSummaryService::details($this->detailsId, $this->filters());
    }

    public function openDetails(int $id): void
    {
        Gate::authorize('viewAny', DailyJournal::class);
        $this->detailsId = $id;
        $this->dispatch('open-modal', id: 'daily-journal-details');
    }

    public function closeDetails(): void
    {
        $this->detailsId = null;
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('viewAny', DailyJournal::class);
        $query = DailyJournalSummaryService::summaryQuery($this->filters());

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $columns = ['branch', 'company', 'entries_count', ...DailyJournalSummaryService::TOTALS];
            fputcsv($output, array_map(fn ($key) => __('finance.daily_journals.summary.'.$key), $columns), escape: '');

            foreach ($query->lazy(250) as $row) {
                fputcsv($output, [
                    self::csvText($row->branch?->name ?? __('finance.daily_journals.summary.no_branch')),
                    self::csvText($row->company?->trade_name ?? __('finance.daily_journals.summary.no_company')),
                    $row->entries_count,
                    ...array_map(fn ($key) => number_format((float) $row->{$key}, 2, '.', ''), DailyJournalSummaryService::TOTALS),
                ], escape: '');
            }

            fclose($output);
        }, 'daily-journals-summary-'.today()->toDateString().'.csv');
    }

    private static function csvText(string $value): string
    {
        return preg_match('/^\s*[=+\-@]|^[\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
