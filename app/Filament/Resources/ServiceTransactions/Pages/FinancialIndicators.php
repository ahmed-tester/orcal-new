<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Models\Company;
use App\Models\Service;
use App\Services\FilterBarService;
use App\Services\ServiceFinancialIndicatorsService;
use App\Services\ServiceFinancialWorkflowService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\Locked;

class FinancialIndicators extends Page
{
    protected static string $resource = ServiceTransactionResource::class;

    protected string $view = 'filament.resources.service-transactions.pages.financial-indicators';

    public string $companyId = '';

    public string $serviceFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    #[Locked]
    public ?array $appliedFilters = null;

    public function applyFilters(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->appliedFilters = [
            'company_id' => $this->companyId,
            'service' => $this->serviceFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return ServiceTransactionResource::canViewAny();
    }

    public function getTitle(): string
    {
        return __('financial.indicators.title');
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('services')
                ->label(ServiceTransactionResource::getNavigationLabel())
                ->url(fn (): string => ServiceTransactionResource::getUrl()),
        ];
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        if ($this->appliedFilters === null) {
            return ['companies' => [], 'services' => [], 'groups' => null];
        }

        $scoped = ServiceTransactionResource::getEloquentQuery()
            ->where('metadata->'.ServiceFinancialWorkflowService::FLOW_KEY, ServiceFinancialWorkflowService::WORKFLOW_VERSION)
            ->reorder();

        $companies = Company::query()
            ->whereIn('id', (clone $scoped)->select('company_id'))
            ->orderBy('trade_name')
            ->pluck('trade_name', 'id');

        $services = Service::query()
            ->whereIn('id', (clone $scoped)->select('service_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => ['id:'.$id => $name]);

        $externalServices = (clone $scoped)->whereNull('service_id')->whereNotNull('service_name')
            ->where('service_name', '<>', '')->distinct()->orderBy('service_name')->pluck('service_name')
            ->mapWithKeys(fn ($name) => ['name:'.$name => $name]);

        $query = FilterBarService::applyFilters($scoped, $this->appliedFilters, [
            'company' => true,
            'date_column' => 'transaction_date',
        ]);

        $service = $this->appliedFilters['service'];
        if (str_starts_with($service, 'id:')) {
            $query->where('service_id', substr($service, 3));
        } elseif (str_starts_with($service, 'name:')) {
            $query->whereNull('service_id')->where('service_name', substr($service, 5));
        }

        return [
            'companies' => $companies,
            'services' => $services->union($externalServices),
            'groups' => ServiceFinancialIndicatorsService::for($query),
        ];
    }
}
