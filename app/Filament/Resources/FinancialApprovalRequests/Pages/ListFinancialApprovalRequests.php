<?php

namespace App\Filament\Resources\FinancialApprovalRequests\Pages;

use App\Enums\Payroll\FinancialApprovalStatus;
use App\Filament\Resources\FinancialApprovalRequests\FinancialApprovalRequestResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFinancialApprovalRequests extends ListRecords
{
    protected static string $resource = FinancialApprovalRequestResource::class;

    public function getDefaultActiveTab(): string|int|null
    {
        $user = auth()->user();
        if (! $user || $user->isSuperAdminOrOwner()) {
            return 'all';
        }

        if ($user->hasPermissionTo('update_treasury_transaction')) {
            return 'pending_treasury';
        }

        if ($user->hasPermissionTo('approve_financial_request')) {
            return 'pending_manager';
        }

        return 'all';
    }


    public function getTabs(): array
    {
        return [
            'pending_treasury' => Tab::make(__('financial.approval.tabs.pending_treasury'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FinancialApprovalStatus::PENDING_TREASURY->value)),

            'pending_manager' => Tab::make(__('financial.approval.tabs.pending_manager'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FinancialApprovalStatus::PENDING_MANAGER->value)),

            'executed' => Tab::make(__('financial.approval.tabs.executed'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', FinancialApprovalStatus::EXECUTED->value)),

            'all' => Tab::make(__('financial.approval.tabs.all')),
        ];
    }
}
