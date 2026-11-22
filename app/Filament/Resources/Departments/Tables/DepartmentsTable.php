<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Enums\GeneralStatus;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use App\Services\CompanyAccessScopeService;
use App\Services\DepartmentHierarchyService;
use App\Services\DepartmentScopeService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['parent' => fn ($parent) => CompanyAccessScopeService::apply($parent->getQuery()), 'companies', 'company'])
                ->withCount(['children as visible_children_count' => fn (Builder $children) => CompanyAccessScopeService::apply($children)])
                ->ordered())
            ->columns([
                TextColumn::make('department_code')
                    ->label(__('organization.departments.fields.department_code'))
                    ->tooltip(__('organization.departments.hierarchy.helpers.code'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label(__('organization.departments.fields.name'))
                    ->description(fn (Department $record) => $record->description)
                    ->url(fn (Department $record) => DepartmentHierarchyService::levelOf($record) < DepartmentHierarchyService::MAX_DEPTH
                        ? DepartmentResource::getUrl('index', ['parent' => $record->getKey()])
                        : null)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hierarchy_path')
                    ->label(__('organization.departments.hierarchy.columns.path'))
                    ->state(fn (Department $record) => DepartmentHierarchyService::visiblePath($record))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('visible_children_count')
                    ->label(__('organization.departments.hierarchy.columns.children_count'))
                    ->numeric()
                    ->badge()
                    ->color('info'),

                TextColumn::make('hierarchy_level')
                    ->label(__('organization.departments.hierarchy.columns.level'))
                    ->state(fn (Department $record) => DepartmentHierarchyService::levelLabel($record)),

                TextColumn::make('parent.name')
                    ->label(__('organization.departments.hierarchy.columns.parent'))
                    ->visible(fn ($livewire): bool => ! data_get($livewire->tableFilters, 'unclassified.isActive', false)
                        && ($livewire->parent !== null || data_get($livewire->tableFilters, 'all_levels.isActive', false)))
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                DepartmentScopeService::makeTableColumn(),

                TextColumn::make('sort_order')
                    ->label(__('organization.departments.fields.sort_order'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('organization.departments.fields.status'))
                    ->badge(),
            ])
            ->filters([
                Filter::make('all_levels')
                    ->label(__('organization.departments.hierarchy.filters.all_levels'))
                    ->toggle()
                    ->query(fn (Builder $query) => $query),

                Filter::make('unclassified')
                    ->label(__('organization.departments.hierarchy.filters.unclassified'))
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereNull('parent_id')->where(fn (Builder $roots) => $roots
                        ->where('is_global', false)->orWhereHas('companies'))),

                SelectFilter::make('company_id')
                    ->label(__('organization.departments.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('organization.departments.fields.status'))
                    ->options(GeneralStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorizeIndividualRecords('delete')
                        ->visible(fn () => auth()->user()?->can('delete_department') ?? false)
                        ->action(function (Collection $records): void {
                            $blocked = 0;

                            foreach ($records as $record) {
                                if (DepartmentHierarchyService::deletionBlockers($record) !== []) {
                                    $blocked++;

                                    continue;
                                }

                                $record->delete();
                            }

                            if ($blocked > 0) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('organization.departments.hierarchy.notifications.bulk_delete_blocked', ['count' => $blocked]))
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
