<?php

namespace App\Services;

use App\Enums\DepartmentScope;
use App\Models\Company;
use App\Models\Department;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

class DepartmentScopeService
{
    /**
     * Resolve the current scope for a department record.
     */
    public static function resolveScope(?Department $record): DepartmentScope
    {
        if (! $record) {
            return DepartmentScope::SINGLE;
        }

        $allCompaniesCount = Company::count();
        $attachedCount = $record->companies()->count();

        if ($record->is_global && ($attachedCount === 0 || $attachedCount >= $allCompaniesCount)) {
            return DepartmentScope::FAWAZ_GROUP;
        }

        if ($record->is_global || $attachedCount > 1) {
            return DepartmentScope::SHARED;
        }

        return DepartmentScope::SINGLE;
    }

    /**
     * Build the form schema components for department scope selection.
     */
    public static function makeFormComponents(): array
    {
        return [
            Radio::make('department_scope')
                ->label(__('organization.departments.scope.label'))
                ->options(DepartmentScope::toOptionsArray())
                ->default(DepartmentScope::SINGLE->value)
                ->inline()
                ->live()
                ->dehydrated(false)
                ->afterStateHydrated(function ($component, $record) {
                    $component->state(static::resolveScope($record)->value);
                })
                ->afterStateUpdated(function ($state, callable $set) {
                    if (DepartmentScope::isFawazGroup($state)) {
                        $set('is_global', true);
                        $set('company_id', null);
                        $set('companies', Company::pluck('id')->toArray());
                    } elseif (DepartmentScope::isShared($state)) {
                        $set('is_global', true);
                        $set('company_id', null);
                        $set('companies', []);
                    } else {
                        $set('is_global', false);
                        $set('companies', []);
                    }
                })
                ->columnSpanFull(),

            Hidden::make('is_global')
                ->default(false)
                ->dehydrated(true),

            Placeholder::make('fawaz_group_info')
                ->label(__('organization.departments.scope.fawaz_group_notice'))
                ->hiddenLabel()
                ->content(fn () => new HtmlString('
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-700 rounded-lg text-emerald-800 dark:text-emerald-200 text-sm flex items-center gap-2">
                        <span class="text-xl">🌐</span>
                        <span><strong>' . e(__('organization.departments.scope.fawaz_group_badge')) . ':</strong> ' . e(__('organization.departments.scope.fawaz_group_info')) . '</span>
                    </div>
                '))
                ->visible(fn ($get) => DepartmentScope::isFawazGroup($get('department_scope')))
                ->columnSpanFull(),

            CompanyFormService::makeCompanySelect('company_id', __('organization.departments.fields.company'))
                ->visible(fn ($get) => DepartmentScope::isSingle($get('department_scope')))
                ->required(fn ($get) => DepartmentScope::isSingle($get('department_scope'))),

            Select::make('companies')
                ->label(__('organization.departments.fields.companies'))
                ->helperText(__('organization.departments.fields.companies_helper'))
                ->relationship(
                    name: 'companies',
                    titleAttribute: 'trade_name',
                    modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && ! $user->isSuperAdmin()) {
                            $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);
                            $query->whereIn('companies.id', ! empty($authorizedIds) ? $authorizedIds : [0]);
                        }
                    }
                )
                ->multiple()
                ->searchable()
                ->preload()
                ->visible(fn ($get) => DepartmentScope::isShared($get('department_scope')))
                ->required(fn ($get) => DepartmentScope::isShared($get('department_scope')))
                ->columnSpanFull(),
        ];
    }

    /**
     * Mutate department data before creation or saving.
     */
    public static function mutateBeforeSave(array $data, mixed $scope, ?Department $record = null): array
    {
        if (DepartmentScope::isFawazGroup($scope)) {
            $data['is_global'] = true;
            $data['company_id'] = $record?->company_id ?: Company::value('id');
        } elseif (DepartmentScope::isShared($scope)) {
            $data['is_global'] = true;
        } else {
            $data['is_global'] = false;
        }

        return $data;
    }

    /**
     * Handle synchronization of companies after creation or saving.
     */
    public static function afterSave(Department $department, mixed $scope): void
    {
        if (DepartmentScope::isFawazGroup($scope)) {
            $allIds = Company::pluck('id')->toArray();
            $department->companies()->sync($allIds);
        }
    }

    /**
     * Build the table column to show the department scope/companies nicely.
     */
    public static function makeTableColumn(): TextColumn
    {
        return TextColumn::make('scope_display')
            ->label(__('organization.departments.fields.companies'))
            ->state(function (Department $record) {
                $scope = static::resolveScope($record);

                return match ($scope) {
                    DepartmentScope::FAWAZ_GROUP => $scope->getLabel(),
                    DepartmentScope::SHARED => $record->companies->isNotEmpty()
                        ? $record->companies->pluck('trade_name')->join('، ')
                        : __('organization.departments.fields.global_badge'),
                    DepartmentScope::SINGLE => $record->company?->trade_name ?? '-',
                };
            })
            ->badge(fn (Department $record) => $record->is_global)
            ->color(fn (Department $record) => static::resolveScope($record)->getColor())
            ->icon(fn (Department $record) => static::resolveScope($record)->getIcon())
            ->wrap();
    }
}
