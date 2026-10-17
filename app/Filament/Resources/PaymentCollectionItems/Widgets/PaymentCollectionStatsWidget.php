<?php

namespace App\Filament\Resources\PaymentCollectionItems\Widgets;

use App\Enums\PaymentItemCategory;
use App\Filament\Resources\PaymentCollectionItems\PaymentCollectionItemResource;
use App\Models\PaymentCollectionItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PaymentCollectionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $query = PaymentCollectionItemResource::getEloquentQuery();

        $totalItems = (clone $query)->count();
        $salaryItems = (clone $query)->whereIn('main_item', [
            PaymentItemCategory::SALARY_COLLECTION->value,
            PaymentItemCategory::SALARY_PAYMENT->value,
        ])->count();

        $expensesItems = (clone $query)->whereIn('main_item', [
            PaymentItemCategory::GENERAL_EXPENSES->value,
            PaymentItemCategory::VIOLATIONS->value,
            PaymentItemCategory::INVOICES->value,
        ])->count();

        $serviceItems = (clone $query)->where('main_item', PaymentItemCategory::SERVICES->value)->count();
        $otherItems = (clone $query)->whereIn('main_item', [
            PaymentItemCategory::RENT->value,
            PaymentItemCategory::MAINTENANCE->value,
            PaymentItemCategory::OTHER->value,
        ])->count();

        $unit = __('finance.payment_collection_items.stats.unit');

        return [
            Stat::make(__('finance.payment_collection_items.stats.total_items'), "{$totalItems} {$unit}")
                ->description(__('finance.payment_collection_items.stats.total_items_desc'))
                ->descriptionIcon('heroicon-m-tag')
                ->color('primary')
                ->chart([3, 5, 4, 6, 7, 8, max($totalItems, 1)]),

            Stat::make(__('finance.payment_collection_items.stats.salary_items'), "{$salaryItems} {$unit}")
                ->description(__('finance.payment_collection_items.stats.salary_items_desc'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('success')
                ->chart([1, 2, 3, 2, 4, 3, max($salaryItems, 1)]),

            Stat::make(__('finance.payment_collection_items.stats.expenses_items'), "{$expensesItems} {$unit}")
                ->description(__('finance.payment_collection_items.stats.expenses_items_desc'))
                ->descriptionIcon('heroicon-m-receipt-percent')
                ->color('danger')
                ->chart([2, 3, 1, 4, 3, 5, max($expensesItems, 1)]),

            Stat::make(__('finance.payment_collection_items.stats.services_items'), "{$serviceItems} {$unit}")
                ->description(__('finance.payment_collection_items.stats.services_items_desc'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('warning')
                ->chart([1, 1, 2, 3, 2, 4, max($serviceItems, 1)]),

            Stat::make(__('finance.payment_collection_items.stats.other_items'), "{$otherItems} {$unit}")
                ->description(__('finance.payment_collection_items.stats.other_items_desc'))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info')
                ->chart([2, 2, 3, 3, 4, 5, max($otherItems, 1)]),
        ];
    }
}
