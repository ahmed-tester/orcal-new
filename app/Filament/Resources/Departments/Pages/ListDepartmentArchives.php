<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use App\Models\DepartmentArchiveBatch;
use App\Services\DepartmentDeletionService;
use App\Services\Departments\DepartmentHierarchyService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class ListDepartmentArchives extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = DepartmentResource::class;

    protected string $view = 'filament.resources.departments.pages.list-department-archives';

    public static function canAccess(array $parameters = []): bool
    {
        return ! app()->isProduction() && (auth()->user()?->isSuperAdmin() ?? false);
    }

    public function getTitle(): string|Htmlable
    {
        return __('organization.departments.deletion.archive.title');
    }

    public function getBreadcrumbs(): array
    {
        return [
            DepartmentResource::getUrl('index') => __('organization.departments.plural'),
            '#' => __('organization.departments.deletion.archive.title'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('organization.departments.hierarchy.actions.back'))
                ->icon('heroicon-m-arrow-uturn-right')
                ->color('gray')
                ->url(DepartmentResource::getUrl('index')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(DepartmentArchiveBatch::query()->with(['actor', 'restoredBy'])->latest('id'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('organization.departments.deletion.archive.columns.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('root_department_name')
                    ->label(__('organization.departments.deletion.archive.columns.root'))
                    ->description(fn (DepartmentArchiveBatch $record): ?string => $record->root_department_code)
                    ->searchable(),

                TextColumn::make('action_type')
                    ->label(__('organization.departments.deletion.archive.columns.action_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('organization.departments.deletion.archive.action_types.'.$state))
                    ->color(fn (string $state): string => $state === 'transfer_and_archive' ? 'info' : 'warning'),

                TextColumn::make('target_department_name')
                    ->label(__('organization.departments.deletion.archive.columns.target'))
                    ->placeholder('-'),

                TextColumn::make('actor.name')
                    ->label(__('organization.departments.deletion.archive.columns.actor'))
                    ->placeholder('-'),

                TextColumn::make('restored_at')
                    ->label(__('organization.departments.deletion.archive.columns.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state
                        ? __('organization.departments.deletion.archive.statuses.restored')
                        : __('organization.departments.deletion.archive.statuses.archived'))
                    ->color(fn ($state): string => $state ? 'success' : 'danger'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('organization.departments.deletion.archive.actions.preview'))
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->modalHeading(__('organization.departments.deletion.archive.actions.preview'))
                    ->modalWidth('xl')
                    ->modalSubmitAction(false)
                    ->modalContent(fn (DepartmentArchiveBatch $record): HtmlString => new HtmlString(
                        view('filament.departments.archive-batch-preview', [
                            'batch' => $record->loadMissing(['actor', 'items']),
                            'groupedItems' => $record->items->groupBy('item_type'),
                        ])->render()
                    )),

                Action::make('restore')
                    ->label(__('organization.departments.deletion.archive.actions.restore'))
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DepartmentArchiveBatch $record): bool => $record->restored_at === null)
                    ->form(function (DepartmentArchiveBatch $record): array {
                        $needsParentOverride = static::batchNeedsParentOverride($record);
                        if (! $needsParentOverride) {
                            return [];
                        }

                        return [
                            Select::make('override_parent_id')
                                ->label(__('organization.departments.deletion.archive.fields.override_parent'))
                                ->options(DepartmentHierarchyService::parentOptions(Department::query()))
                                ->searchable()
                                ->required()
                                ->helperText(__('organization.departments.deletion.errors.parent_archived')),
                        ];
                    })
                    ->action(function (array $data, DepartmentArchiveBatch $record): void {
                        try {
                            DepartmentDeletionService::restoreBatch(
                                $record,
                                isset($data['override_parent_id']) ? (int) $data['override_parent_id'] : null
                            );

                            Notification::make()
                                ->success()
                                ->title(__('organization.departments.deletion.notifications.restored_success'))
                                ->send();
                        } catch (ValidationException $e) {
                            Notification::make()
                                ->danger()
                                ->title(collect($e->errors())->flatten()->first() ?? __('organization.departments.deletion.errors.restore_failed'))
                                ->send();
                        }
                    }),
            ]);
    }

    protected static function batchNeedsParentOverride(DepartmentArchiveBatch $batch): bool
    {
        $deptItems = $batch->items->where('item_type', 'department');
        $batchDeptIds = $deptItems->pluck('item_id')->map(fn ($id) => (int) $id)->all();

        foreach ($deptItems as $item) {
            $parentId = $item->parent_department_id ? (int) $item->parent_department_id : null;
            if ($parentId !== null && ! in_array($parentId, $batchDeptIds, true)) {
                if (! Department::query()->whereKey($parentId)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
