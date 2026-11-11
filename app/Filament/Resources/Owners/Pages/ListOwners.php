<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Filament\Resources\Owners\OwnerResource;
use App\Models\Company;
use App\Services\CompanyFormService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListOwners extends ListRecords
{
    protected static string $resource = OwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

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
                        ->default('owners_only')
                        ->live()
                        ->required(),

                    Select::make('nationality')
                        ->label(__('employees.statement.nationality'))
                        ->options(__('employees.statement.nationalities'))
                        ->default('all')
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
}
