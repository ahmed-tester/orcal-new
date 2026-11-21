<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\Service;
use App\Services\FilterBarService;
use App\Services\ServiceFinancialIndicatorsService;
use App\Services\ServiceFinancialWorkflowService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;

class FinancialIndicators extends Page
{
    use HasFilterBarState;

    protected static string $resource = ServiceTransactionResource::class;

    protected string $view = 'filament.resources.service-transactions.pages.financial-indicators';

    public ?string $serviceFilter = null;

    /**
     * @return array<string>
     */
    protected function filterBarExtraProperties(): array
    {
        return ['serviceFilter'];
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

    public function getServicesProperty()
    {
        $scoped = ServiceTransactionResource::getEloquentQuery()
            ->where('metadata->'.ServiceFinancialWorkflowService::FLOW_KEY, ServiceFinancialWorkflowService::WORKFLOW_VERSION)
            ->reorder();

        $services = Service::query()
            ->whereIn('id', (clone $scoped)->select('service_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => ['id:'.$id => $name]);

        $externalServices = (clone $scoped)->whereNull('service_id')->whereNotNull('service_name')
            ->where('service_name', '<>', '')->distinct()->orderBy('service_name')->pluck('service_name')
            ->mapWithKeys(fn ($name) => ['name:'.$name => $name]);

        return $services->union($externalServices);
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $scoped = ServiceTransactionResource::getEloquentQuery()
            ->where('metadata->'.ServiceFinancialWorkflowService::FLOW_KEY, ServiceFinancialWorkflowService::WORKFLOW_VERSION)
            ->reorder();

        $filters = $this->filterBarState();

        $query = FilterBarService::applyFilters(clone $scoped, $filters, [
            'company' => true,
            'date_column' => 'transaction_date',
        ]);

        $service = (string) ($this->serviceFilter ?? '');
        if (str_starts_with($service, 'id:')) {
            $query->where('service_id', substr($service, 3));
        } elseif (str_starts_with($service, 'name:')) {
            $query->whereNull('service_id')->where('service_name', substr($service, 5));
        }

        return [
            'groups' => ServiceFinancialIndicatorsService::for($query),
        ];
    }
}
