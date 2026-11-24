<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Traits\HandlesDepartmentScope;
use App\Models\Department;
use App\Services\DepartmentHierarchyService;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Concerns\RedirectsToIndex;

class EditDepartment extends EditRecord
{
    use RedirectsToIndex;

    use HandlesDepartmentScope;

    protected static string $resource = DepartmentResource::class;

    public function getTitle(): string|Htmlable
    {
        $parentId = data_get($this->data, 'parent_id', $this->getRecord()->parent_id);

        return DepartmentForm::label('edit', filled($parentId) ? (int) $parentId : null);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return DepartmentForm::label('saved', $this->getRecord()->parent_id);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Department $record): void {
                    $blockers = DepartmentHierarchyService::deletionBlockers($record);

                    if ($blockers === []) {
                        return;
                    }

                    Notification::make()
                        ->danger()
                        ->title(__('organization.departments.hierarchy.notifications.delete_blocked', [
                            'reasons' => collect($blockers)
                                ->map(fn (string $blocker) => __('organization.departments.hierarchy.delete_blockers.'.$blocker))
                                ->implode(' '),
                        ]))
                        ->send();

                    $action->halt();
                }),
        ];
    }
}
