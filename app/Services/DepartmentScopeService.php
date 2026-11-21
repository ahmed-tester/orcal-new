<?php

namespace App\Services;

use App\Enums\DepartmentScope;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
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

        if ($record->is_global) {
            if ($record->relationLoaded('companies') ? $record->companies->isNotEmpty() : $record->companies()->exists()) {
                return DepartmentScope::SHARED;
            }

            return DepartmentScope::FAWAZ_GROUP;
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
                ->label(fn ($get, $livewire) => DepartmentForm::label('scope', DepartmentForm::contextParentId($get, $livewire)))
                ->options(function ($get, ?Department $record) {
                    $options = DepartmentScope::toOptionsArray();
                    if ($get('parent_id')) {
                        unset($options[DepartmentScope::FAWAZ_GROUP->value]);
                    } elseif (! $record || $record->parent_id !== null) {
                        $options = [DepartmentScope::FAWAZ_GROUP->value => $options[DepartmentScope::FAWAZ_GROUP->value]];
                    }

                    return $options;
                })
                ->default(DepartmentScope::SINGLE->value)
                ->inline()
                ->live()
                ->visible(fn ($get, $livewire, ?Department $record) => ! static::hasFixedRootScope($get, $record)
                    && (! ($livewire instanceof CreateDepartment) || ! $livewire->hasFixedAffiliation()))
                ->dehydratedWhenHidden(fn ($livewire) => ! ($livewire instanceof CreateDepartment))
                ->required()
                ->afterStateHydrated(function ($component, $record) {
                    $component->state($record
                        ? static::resolveScope($record)->value
                        : (request()->integer('parent') ? DepartmentScope::SINGLE->value : DepartmentScope::FAWAZ_GROUP->value));
                })
                ->afterStateUpdated(function ($state, callable $set) {
                    if (DepartmentScope::isFawazGroup($state)) {
                        $set('is_global', true);
                        $set('company_id', null);
                        $set('companies', []);
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
                        <span><strong>'.e(__('organization.departments.scope.fawaz_group_badge')).':</strong> '.e(__('organization.departments.scope.fawaz_group_info')).'</span>
                    </div>
                '))
                ->visible(fn ($get, ?Department $record) => static::hasFixedRootScope($get, $record)
                    || DepartmentScope::isFawazGroup($get('department_scope')))
                ->columnSpanFull(),

            CompanyFormService::makeCompanySelect('company_id', __('organization.departments.fields.company'))
                ->label(fn ($get, $livewire) => DepartmentForm::label('company', DepartmentForm::contextParentId($get, $livewire)))
                ->options(fn ($get, $livewire) => static::companyOptions($get, $livewire))
                ->visible(fn ($get, $livewire, ?Department $record) => ! static::hasFixedRootScope($get, $record)
                    && DepartmentScope::isSingle($get('department_scope'))
                    && (! ($livewire instanceof CreateDepartment) || ! $livewire->inheritsCompany()))
                ->required(fn ($get, $livewire) => DepartmentScope::isSingle($get('department_scope'))
                    && (! ($livewire instanceof CreateDepartment) || ! $livewire->inheritsCompany())),

            Select::make('companies')
                ->label(fn ($get, $livewire) => DepartmentForm::label('companies', DepartmentForm::contextParentId($get, $livewire)))
                ->helperText(fn ($livewire) => $livewire instanceof CreateDepartment && $livewire->creationParent()
                    ? __('organization.departments.hierarchy.form.parent_companies_helper')
                    : __('organization.departments.fields.companies_helper'))
                ->options(fn ($get, $livewire) => static::companyOptions($get, $livewire))
                ->afterStateHydrated(function ($component, ?Department $record) {
                    if ($record) {
                        $component->state($record->companies()->pluck('companies.id')->all());
                    }
                })
                ->multiple()
                ->searchable()
                ->preload()
                ->visible(fn ($get, $livewire, ?Department $record) => ! static::hasFixedRootScope($get, $record)
                    && DepartmentScope::isShared($get('department_scope'))
                    && (! ($livewire instanceof CreateDepartment) || ! $livewire->hasFixedAffiliation()))
                ->required(fn ($get, $livewire) => DepartmentScope::isShared($get('department_scope'))
                    && (! ($livewire instanceof CreateDepartment) || ! $livewire->hasFixedAffiliation()))
                ->columnSpanFull(),
        ];
    }

    protected static function hasFixedRootScope($get, ?Department $record): bool
    {
        return $record && $record->parent_id === null && blank($get('parent_id'))
            && static::resolveScope($record) === DepartmentScope::FAWAZ_GROUP;
    }

    protected static function companyOptions($get, $livewire): array
    {
        $query = Company::query();
        $user = auth()->user();
        if (! $user?->isSuperAdmin()) {
            $query->whereIn('id', CompanyFormService::getAuthorizedCompanyIds($user));
        }
        $parentId = $livewire instanceof CreateDepartment ? $livewire->contextParentId : $get('parent_id');
        if ($parentId) {
            $parent = CompanyAccessScopeService::apply(Department::query())->find($parentId);
            if (! $parent) {
                return [];
            }
            if ($ids = $parent->getAssignedCompanyIds()) {
                $query->whereIn('id', $ids);
            }
        }

        return $query->pluck('trade_name', 'id')->all();
    }

    /**
     * Mutate department data before creation or saving.
     */
    public static function mutateBeforeSave(array $data, mixed $scope, ?Department $record = null): array
    {
        if (DepartmentScope::isFawazGroup($scope)) {
            $data['is_global'] = true;
            $data['company_id'] = null;
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
            $department->companies()->sync([]);

            if ($department->company_id !== null) {
                $department->withoutEvents(function () use ($department) {
                    $department->updateQuietly(['company_id' => null]);
                });
            }
        } elseif (DepartmentScope::isSingle($scope)) {
            if ($department->company_id) {
                $department->companies()->sync([$department->company_id]);
            } else {
                $department->companies()->sync([]);
            }
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
                        ? $record->companies->pluck('trade_name')->all()
                        : __('organization.departments.fields.global_badge'),
                    DepartmentScope::SINGLE => $record->company?->trade_name ?? '-',
                };
            })
            ->badge(fn (Department $record) => $record->is_global)
            ->listWithLineBreaks()
            ->limitList(2)
            ->expandableLimitedList()
            ->tooltip(function (Department $record): ?string {
                if (static::resolveScope($record) !== DepartmentScope::SHARED || $record->companies->count() <= 2) {
                    return null;
                }

                return $record->companies->pluck('trade_name')->join(__('organization.departments.fields.companies_separator'));
            })
            ->color(fn (Department $record) => static::resolveScope($record)->getColor())
            ->icon(fn (Department $record) => static::resolveScope($record)->getIcon())
            ->wrap();
    }
}
