<?php

namespace App\Filament\Resources\Debts\Pages;

use App\Filament\Resources\Debts\DebtResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Debt;
use App\Services\DebtSummaryService;
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

class DebtsSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = DebtResource::class;

    #[Url]
    public mixed $debtorType = null;

    #[Locked]
    public ?int $detailsId = null;

    public function getView(): string
    {
        return 'filament.resources.debts.pages.debts-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return Gate::allows('viewAny', Debt::class);
    }

    public function getTitle(): string|Htmlable
    {
        return __('debts.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('debts.summary.create_debt'))
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn () => DebtResource::getUrl('create'))
                ->visible(fn () => DebtResource::canCreate()),
            Action::make('detailed')
                ->label(__('debts.summary.detailed'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn () => DebtResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['debtorType'];
    }

    public function handleFilterBarUpdate(): void
    {
        $this->resetPage();
        $this->detailsId = null;
        $this->dispatch('close-modal', id: 'debt-details');
    }

    public function filters(): array
    {
        return $this->filterBarState(['debtor_type' => $this->debtorType]);
    }

    public function hasActiveFilters(): bool
    {
        return $this->hasActiveFilterBar() || ! empty($this->debtorType);
    }

    public function resetFilters(): void
    {
        $this->resetFilterBar();
        $this->debtorType = is_array($this->debtorType) ? [] : null;
        $this->handleFilterBarUpdate();
    }

    #[Computed]
    public function summary(): LengthAwarePaginator
    {
        Gate::authorize('viewAny', Debt::class);

        return DebtSummaryService::summaryQuery($this->filters())->paginate(15);
    }

    #[Computed]
    public function stats(): object
    {
        Gate::authorize('viewAny', Debt::class);

        return DebtSummaryService::stats($this->filters());
    }

    #[Computed]
    public function details(): ?array
    {
        Gate::authorize('viewAny', Debt::class);

        return $this->detailsId === null ? null : DebtSummaryService::details($this->detailsId, $this->filters());
    }

    public function openDetails(int $id): void
    {
        Gate::authorize('viewAny', Debt::class);
        $this->detailsId = $id;
        $this->dispatch('open-modal', id: 'debt-details');
    }

    public function closeDetails(): void
    {
        $this->detailsId = null;
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('viewAny', Debt::class);
        $query = DebtSummaryService::summaryQuery($this->filters());

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $columns = ['debtor', 'debtor_type', 'company', 'debt_count', ...DebtSummaryService::TOTALS];
            fputcsv($output, array_map(fn ($key) => __('debts.summary.'.$key), $columns), escape: '');

            foreach ($query->lazy(250) as $row) {
                fputcsv($output, [
                    self::csvText($row->beneficiary_name),
                    __('debts.badges.'.$row->debtor_type),
                    self::csvText($row->company?->trade_name ?? __('debts.summary.no_company')),
                    $row->debt_count,
                    ...array_map(fn ($key) => number_format((float) $row->{$key}, 2, '.', ''), DebtSummaryService::TOTALS),
                ], escape: '');
            }

            fclose($output);
        }, 'debts-summary-'.today()->toDateString().'.csv');
    }

    private static function csvText(string $value): string
    {
        return preg_match('/^\s*[=+\-@]|^[\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
