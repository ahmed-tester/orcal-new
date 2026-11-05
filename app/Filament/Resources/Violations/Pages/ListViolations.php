<?php

namespace App\Filament\Resources\Violations\Pages;

use App\Filament\Resources\Violations\ViolationResource;
use App\Filament\Traits\HasFilterBarState;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\FilterBarService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListViolations extends ListRecords
{
    use HasFilterBarState;

    protected static string $resource = ViolationResource::class;

    public mixed $categoryId = null;

    public mixed $typeId = null;

    public mixed $paymentStatus = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_pdf')
                ->label(__('violations.violations.actions.export_pdf'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('exports.violations', ['section' => 'violations']))
                ->openUrlInNewTab(),
        ];
    }

    protected function filterBarExtraProperties(): array
    {
        return ['categoryId', 'typeId', 'paymentStatus'];
    }

    protected function getTableQuery(): Builder
    {
        return FilterBarService::applyFilters(parent::getTableQuery(), $this->filterBarState(), [
            'company' => true,
            'date_column' => 'violation_date',
            'search_columns' => [
                'violation_number',
                'external_violation_number',
                'issuing_authority',
                'description',
                'company.trade_name',
            ],
            'where_in' => [
                'violation_category_id' => $this->categoryId,
                'violation_type_id' => $this->typeId,
                'payment_status' => $this->paymentStatus,
            ],
        ]);
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
}
