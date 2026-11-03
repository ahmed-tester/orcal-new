<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\Company;
use App\Models\PaymentCollectionItem;
use App\Models\TreasuryTransaction;
use App\Services\CompanyFormService;
use App\Services\TreasurySummaryService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\WithPagination;

class TreasurySummary extends Page
{
    use WithPagination;

    protected static string $resource = TreasuryTransactionResource::class;

    public string $search = '';

    public ?int $companyId = null;

    public ?string $transactionType = null;

    public ?int $paymentCollectionItemId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public int $perPage = 15;

    public string $sortColumn = 'total_outgoing';

    public string $sortDirection = 'desc';

    public ?string $selectedBeneficiary = null;

    public ?int $selectedCompanyId = null;

    public ?array $beneficiaryDetails = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'companyId' => ['except' => null],
        'transactionType' => ['except' => null],
        'paymentCollectionItemId' => ['except' => null],
        'dateFrom' => ['except' => null],
        'dateTo' => ['except' => null],
        'perPage' => ['except' => 15],
        'sortColumn' => ['except' => 'total_outgoing'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function getView(): string
    {
        return 'filament.resources.treasury-transactions.pages.treasury-summary';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->can('viewAny', TreasuryTransaction::class) ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('finance.treasury_transactions.summary.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label(__('finance.treasury_transactions.summary.create_transaction'))
                ->icon('heroicon-o-plus-circle')
                ->url(fn (): string => TreasuryTransactionResource::getUrl('create'))
                ->visible(fn (): bool => auth()->user()?->can('create', TreasuryTransaction::class) ?? false),

            Action::make('export_statement')
                ->label(__('finance.treasury_transactions.summary.filters.export_statement_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->modalHeading('تصدير كشف حركة الخزينة والمحاسبة')
                ->modalDescription('حدد المؤسسة ونوع الكشف والفترة الزمنية لتصدير وطباعة كشف الحساب والعمليات بصيغة PDF.')
                ->modalSubmitActionLabel('توليد وعرض الكشف')
                ->form([
                    Select::make('company_id')
                        ->label('المؤسسة / الشركة')
                        ->options(['all' => 'كافة المؤسسات والشركات (الكل)'] + Company::orderBy('trade_name')->pluck('trade_name', 'id')->toArray())
                        ->default('all')
                        ->searchable()
                        ->required(),
                    Select::make('transaction_type')
                        ->label('نوع الكشف')
                        ->options([
                            'all' => 'الكل (قبض وصرف)',
                            'incoming' => 'مقبوضات الخزينة (سندات قبض فقط)',
                            'outgoing' => 'مدفوعات ومنصرفات الخزينة (سندات صرف فقط)',
                        ])
                        ->default('all')
                        ->required(),
                    DatePicker::make('from_date')
                        ->label('من تاريخ'),
                    DatePicker::make('to_date')
                        ->label('إلى تاريخ'),
                ])
                ->action(function (array $data) {
                    $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                    $url = route('exports.treasury-statement', $params);
                    $this->js("window.open('{$url}', '_blank')");
                }),

            Action::make('detailedView')
                ->label(__('finance.treasury_transactions.summary.detailed_view'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => TreasuryTransactionResource::getUrl('detailed')),
        ];
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'companyId', 'transactionType', 'paymentCollectionItemId', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if ($this->sortColumn === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortColumn = $column;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'companyId', 'transactionType', 'paymentCollectionItemId', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function hasActiveFilters(): bool
    {
        return trim((string) $this->search) !== ''
            || $this->companyId !== null
            || $this->transactionType !== null
            || $this->paymentCollectionItemId !== null
            || $this->dateFrom !== null
            || $this->dateTo !== null;
    }

    public function openDetails(int $rowId): void
    {
        $record = TreasuryTransaction::find($rowId);
        if (! $record) {
            return;
        }

        $beneficiaryName = $record->recipient_or_payer ?: 'طرف غير محدد';
        $this->selectedBeneficiary = $beneficiaryName;
        $this->selectedCompanyId = $record->company_id;

        $this->beneficiaryDetails = TreasurySummaryService::getBeneficiaryDetails(
            $beneficiaryName,
            $record->company_id,
            $this->filterState()
        );
    }

    public function closeDetails(): void
    {
        $this->selectedBeneficiary = null;
        $this->selectedCompanyId = null;
        $this->beneficiaryDetails = null;
    }

    public function exportCsv()
    {
        $rows = TreasurySummaryService::exportRows($this->filterState());

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            // BOM for UTF-8 Excel support
            fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                '#',
                __('finance.treasury_transactions.summary.columns.beneficiary'),
                __('finance.treasury_transactions.summary.columns.company'),
                __('finance.treasury_transactions.summary.columns.vouchers_count'),
                __('finance.treasury_transactions.summary.columns.total_outgoing'),
                __('finance.treasury_transactions.summary.columns.total_incoming'),
                __('finance.treasury_transactions.summary.columns.net_amount'),
                __('finance.treasury_transactions.summary.columns.last_date'),
            ]);

            foreach ($rows as $index => $row) {
                fputcsv($handle, [
                    $index + 1,
                    $row->beneficiary_name ?? '—',
                    $row->company?->trade_name ?? '—',
                    (int) $row->transactions_count,
                    number_format((float) $row->total_outgoing, 2, '.', ''),
                    number_format((float) $row->total_incoming, 2, '.', ''),
                    number_format((float) $row->net_amount, 2, '.', ''),
                    $row->last_transaction_date ? Carbon::parse($row->last_transaction_date)->format('Y-m-d') : '—',
                ]);
            }

            fclose($handle);
        }, 'treasury_beneficiaries_summary_'.date('Y_m_d').'.csv');
    }

    protected function filterState(): array
    {
        return [
            'search' => $this->search,
            'company_id' => $this->companyId,
            'transaction_type' => $this->transactionType,
            'payment_collection_item_id' => $this->paymentCollectionItemId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    public function getSummaryProperty()
    {
        $allowed = ['total_outgoing', 'total_incoming', 'net_amount', 'transactions_count', 'last_transaction_date', 'row_id'];
        $column = in_array($this->sortColumn, $allowed, true) ? $this->sortColumn : 'total_outgoing';
        $direction = strtolower($this->sortDirection) === 'asc' ? 'asc' : 'desc';

        return TreasurySummaryService::summaryQuery($this->filterState())
            ->orderBy($column, $direction)
            ->paginate(max(5, (int) $this->perPage));
    }

    public function getStatsProperty(): array
    {
        return TreasurySummaryService::getStats($this->filterState());
    }

    public function getCompaniesProperty()
    {
        $query = Company::query();
        $companyIds = CompanyFormService::getAuthorizedCompanyIds();

        if (! empty($companyIds)) {
            $query->whereIn('id', $companyIds);
        }

        return $query->orderBy('trade_name')->get();
    }

    public function getPaymentItemsProperty()
    {
        $query = PaymentCollectionItem::query();
        $companyIds = CompanyFormService::getAuthorizedCompanyIds();

        if (! empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }

        return $query->orderBy('sub_item')->get();
    }
}
