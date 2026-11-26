<?php

namespace App\Filament\Resources\FinancialApprovalRequests;

use App\Filament\Resources\FinancialApprovalRequests\Pages\ListFinancialApprovalRequests;
use App\Filament\Resources\FinancialApprovalRequests\Pages\ViewFinancialApprovalRequest;
use App\Filament\Resources\FinancialApprovalRequests\Tables\FinancialApprovalRequestsTable;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Services\Departments\DepartmentManagerResolverService;
use App\Services\Payroll\PayrollApprovalScopeService;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinancialApprovalRequestResource extends Resource
{
    protected static ?string $model = FinancialApprovalRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.finance_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('financial.approval.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('financial.approval.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('financial.approval.plural');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user || ! DepartmentManagerResolverService::isActiveStatus($user->status)) {
            return false;
        }

        return $user->hasAnyPermission([
            'view_financial_request',
            'update_treasury_transaction',
            'approve_financial_request',
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['companies:id,trade_name', 'submitter:id,name', 'treasuryReviewer:id,name', 'managerUser:id,name', 'managerDepartment:id,name']);

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return PayrollApprovalScopeService::scopeVisibleRequests($query, $user);
    }

    public static function table(Table $table): Table
    {
        return FinancialApprovalRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialApprovalRequests::route('/'),
            'view' => ViewFinancialApprovalRequest::route('/{record}'),
        ];
    }
}
