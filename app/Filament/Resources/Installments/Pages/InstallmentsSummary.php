<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Enums\InstallmentStatus;
use App\Filament\Resources\Installments\InstallmentResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Installment;
use App\Services\InstallmentSummaryService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithPagination;

class InstallmentsSummary extends Page
{
    use HasFilterBarState;
    use WithPagination;

    protected static string $resource = InstallmentResource::class;

    public mixed $transactionType = null;

    public mixed $beneficiaryType = null;

    public int $perPage = 10;

    public string $sortColumn = 'remaining_sum';

    public string $sortDirection = 'desc';

    public ?array $details = null;

    public string $detailsTab = 'all';

    protected $queryString = [
        'transactionType' => ['except' => null],
        'beneficiaryType' => ['except' => null],
        'perPage' => ['except' => 10],
        'sortColumn' => ['except' => 'remaining_sum'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function getView(): string
    {
        return 'filament.resources.installments.pages.installments-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', Installment::class) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('finance.installments.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('finance.installments.summary.create_installment'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => InstallmentResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', Installment::class) ?? false),

            Action::make('detailedView')
                ->label(__('finance.installments.summary.detailed_view'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => InstallmentResource::getUrl('detailed')),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['transactionType', 'beneficiaryType'];
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function openDetails(int $rowId): void
    {
        $installment = Installment::find($rowId);

        if (! $installment) {
            return;
        }

        $this->detailsTab = 'all';
        $this->details = InstallmentSummaryService::getBeneficiaryDetails(
            InstallmentSummaryService::beneficiaryKeyForInstallment($installment),
            $this->filterState()
        );
    }

    public function setDetailsTab(string $tab): void
    {
        $this->detailsTab = in_array($tab, ['all', 'installments', 'payments'], true) ? $tab : 'all';
    }

    public function closeDetails(): void
    {
        $this->details = null;
        $this->detailsTab = 'all';
    }

    public function exportCsv()
    {
        $rows = InstallmentSummaryService::exportRows($this->filterState());

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel support
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                __('finance.installments.summary.columns.name'),
                __('finance.installments.summary.columns.national_id'),
                __('finance.installments.summary.columns.company'),
                __('finance.installments.summary.beneficiary_types.internal'),
                __('finance.installments.summary.columns.count'),
                __('finance.installments.summary.columns.total'),
                __('finance.installments.summary.columns.paid'),
                __('finance.installments.summary.columns.remaining'),
            ]);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    InstallmentSummaryService::beneficiaryName($row) ?? '',
                    InstallmentSummaryService::beneficiaryNationalId($row) ?? '',
                    $row->isExternal()
                        ? __('finance.installments.summary.beneficiary_types.external')
                        : __('finance.installments.summary.beneficiary_types.internal'),
                    (int) $row->installments_count,
                    number_format((float) $row->total_sum, 2, '.', ''),
                    number_format((float) $row->paid_sum, 2, '.', ''),
                    number_format((float) $row->remaining_sum, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 'installments_summary_'.date('Y_m_d').'.csv');
    }

    // --- Display helpers used by the Blade view ---

    public function nameOf(Installment $row): string
    {
        return InstallmentSummaryService::beneficiaryName($row) ?? '—';
    }

    public function nationalIdOf(Installment $row): ?string
    {
        return InstallmentSummaryService::beneficiaryNationalId($row);
    }

    public function companyOf(Installment $row): ?string
    {
        return InstallmentSummaryService::beneficiaryCompany($row);
    }

    // --- Computed properties for the view ---

    protected function filterState(): array
    {
        return $this->filterBarState([
            'transaction_type' => $this->transactionType,
            'beneficiary_type' => $this->beneficiaryType,
        ]);
    }

    public function getSummaryProperty()
    {
        $allowed = ['installments_count', 'total_sum', 'paid_sum', 'remaining_sum'];
        $column = in_array($this->sortColumn, $allowed, true) ? $this->sortColumn : 'remaining_sum';
        $direction = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';

        return InstallmentSummaryService::summaryQuery($this->filterState())
            ->orderBy($column, $direction)
            ->orderBy('row_id')
            ->paginate(max(5, (int) $this->perPage));
    }

    public function getStatsProperty(): array
    {
        return InstallmentSummaryService::getStats($this->filterState());
    }

    public function getStatusOptionsProperty(): array
    {
        return collect(InstallmentStatus::cases())
            ->mapWithKeys(fn (InstallmentStatus $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
