<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Enums\UserType;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSaudiEmployees extends ListRecords
{
    protected static string $resource = SaudiEmployeeResource::class;

    public function getTitle(): string
    {
        return __('navigation.employees');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('create_saudi')
                ->label('إضافة موظف سعودي')
                ->icon('heroicon-o-user-plus')
                ->color('success'),

            Action::make('create_foreign')
                ->label('إضافة موظف غير سعودي')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->url(ForeignEmployeeResource::getUrl('create')),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = Employee::query();
        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin() && ! $user->hasRole('Super Admin')) {
            $companyIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
            if (!empty($companyIds)) {
                $baseQuery->whereIn('company_id', $companyIds);
            }
        }

        $allCount = (clone $baseQuery)->count();
        $saudiCount = (clone $baseQuery)->whereHas('user', fn ($q) => $q->where('user_type', UserType::SAUDI_EMPLOYEE))->count();
        $foreignCount = (clone $baseQuery)->whereHas('user', fn ($q) => $q->where('user_type', UserType::FOREIGN_EMPLOYEE))->count();

        return [
            'all' => Tab::make(__('employees.tabs.all'))
                ->badge($allCount)
                ->badgeColor('gray'),

            'saudi' => Tab::make(__('employees.tabs.saudi'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('user_type', UserType::SAUDI_EMPLOYEE)))
                ->badge($saudiCount)
                ->badgeColor('success'),

            'foreign' => Tab::make(__('employees.tabs.foreign'))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('user_type', UserType::FOREIGN_EMPLOYEE)))
                ->badge($foreignCount)
                ->badgeColor('info'),
        ];
    }
}
