<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Enums\UserType;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Company;
use App\Models\Employee;
use App\Services\CompanyFormService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
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
                ->label(__('employees.actions.create_saudi'))
                ->icon('heroicon-o-user-plus')
                ->color('success'),

            Action::make('create_foreign')
                ->label(__('employees.actions.create_foreign'))
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->url(ForeignEmployeeResource::getUrl('create')),

            Action::make('print_statement')
                ->label(__('employees.statement.action_label'))
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->modalHeading(__('employees.statement.modal_heading'))
                ->modalDescription(__('employees.statement.modal_description'))
                ->modalSubmitActionLabel(__('employees.statement.modal_submit'))
                ->form([
                    Select::make('statement_type')
                        ->label(__('employees.statement.statement_type'))
                        ->options(__('employees.statement.types'))
                        ->default('all')
                        ->live()
                        ->required(),

                    Select::make('nationality')
                        ->label(__('employees.statement.nationality'))
                        ->options(__('employees.statement.nationalities'))
                        ->default('saudi')
                        ->visible(fn ($get) => in_array($get('statement_type'), ['all', 'employees_only', 'dual_only']))
                        ->required(),

                    Select::make('company_id')
                        ->label(__('employees.statement.company'))
                        ->options(function () {
                            $user = auth()->user();
                            $query = Company::query();
                            if ($user && ! $user->isSuperAdmin()) {
                                $ids = CompanyFormService::getAuthorizedCompanyIds($user);
                                if (! empty($ids)) {
                                    $query->whereIn('id', $ids);
                                }
                            }

                            return ['all' => __('employees.statement.all_authorized_companies')] + $query->orderBy('trade_name')->pluck('trade_name', 'id')->toArray();
                        })
                        ->default('all')
                        ->searchable()
                        ->required(),

                    Select::make('status')
                        ->label(__('employees.statement.status'))
                        ->options(__('employees.statement.statuses'))
                        ->default('all')
                        ->required(),

                    Select::make('export_mode')
                        ->label(__('employees.statement.export_mode'))
                        ->options(__('employees.statement.modes'))
                        ->default('print')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                    $url = route('exports.employees-statement', $params);
                    $this->js("window.open('{$url}', '_blank')");
                }),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = Employee::query();
        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin() && ! $user->hasRole('Super Admin')) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if (! empty($companyIds)) {
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
