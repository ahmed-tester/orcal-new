<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ForeignEmployee;
use App\Models\Owner;
use App\Models\SaudiEmployee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalCompanies = Company::count();
        $totalBranches = Branch::count();
        $totalSaudi = SaudiEmployee::count();
        $totalForeign = ForeignEmployee::count();
        $totalOwners = Owner::count();
        $totalWorkforce = $totalSaudi + $totalForeign;

        $saudizationRate = $totalWorkforce > 0
            ? round(($totalSaudi / $totalWorkforce) * 100, 1)
            : 0;

        return [
            Stat::make(__('dashboard.stats.total_companies'), $totalCompanies)
                ->description(__('dashboard.stats.companies_desc'))
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('primary')
                ->chart([1, 2, 1, 2, 3, 2, 3, max($totalCompanies, 3)]),

            Stat::make(__('dashboard.stats.saudi_employees'), $totalSaudi)
                ->description(__('dashboard.stats.saudization_rate', ['rate' => $saudizationRate]))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([2, 3, 4, 3, 5, 4, 6, max($totalSaudi, 6)]),

            Stat::make(__('dashboard.stats.foreign_employees'), $totalForeign)
                ->description(__('dashboard.stats.foreign_employees_desc'))
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning')
                ->chart([3, 4, 3, 5, 4, 4, 5, max($totalForeign, 5)]),

            Stat::make(__('dashboard.stats.total_workforce'), $totalWorkforce)
                ->description(__('dashboard.stats.workforce_breakdown', [
                    'saudi' => $totalSaudi,
                    'foreign' => $totalForeign,
                ]))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info')
                ->chart([4, 6, 7, 6, 8, 9, 10, max($totalWorkforce, 11)]),

            Stat::make(__('dashboard.stats.branches'), $totalBranches)
                ->description(__('dashboard.stats.branches_desc'))
                ->descriptionIcon('heroicon-m-map-pin')
                ->color('info')
                ->chart([1, 2, 2, 3, 2, 3, 4, max($totalBranches, 4)]),

            Stat::make(__('dashboard.stats.owners'), $totalOwners)
                ->description(__('dashboard.stats.owners_desc'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart([1, 1, 2, 2, 3, 2, 3, max($totalOwners, 3)]),
        ];
    }
}
