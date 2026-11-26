<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Traits\HandlesDepartmentScope;
use App\Models\Department;
use App\Services\Departments\DepartmentHierarchyService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class CreateDepartment extends CreateRecord
{
    use HandlesDepartmentScope;

    protected static string $resource = DepartmentResource::class;

    #[Locked]
    public ?int $contextParentId = null;

    protected ?Department $resolvedParent = null;

    public function mount(): void
    {
        $this->contextParentId = request()->integer('parent') ?: null;
        $parent = $this->creationParent();

        if ($parent && DepartmentHierarchyService::levelOf($parent) >= DepartmentHierarchyService::MAX_DEPTH) {
            abort(404);
        }

        if ((! $parent || DepartmentHierarchyService::levelOf($parent) === 1) && ! auth()->user()?->isSuperAdmin()) {
            abort(403);
        }

        parent::mount();
    }

    public function creationParent(): ?Department
    {
        if (! $this->contextParentId) {
            return null;
        }

        return $this->resolvedParent ??= DepartmentResource::getEloquentQuery()
            ->with('companies')->findOrFail($this->contextParentId);
    }

    public function hasFixedAffiliation(): bool
    {
        return ! $this->creationParent() || DepartmentHierarchyService::levelOf($this->creationParent()) === 1;
    }

    public function inheritsCompany(): bool
    {
        return false;
    }

    protected function afterFill(): void
    {
        $this->data = array_replace($this->data ?? [], DepartmentHierarchyService::creationDefaults($this->creationParent()));
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DepartmentHierarchyService::createFromContext($data, $this->contextParentId, 'data.');
    }

    public function getTitle(): string|Htmlable
    {
        return DepartmentForm::label('create', $this->contextParentId);
    }

    public function getBreadcrumb(): string
    {
        return $this->getTitle();
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label($this->getTitle());
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label(DepartmentForm::label('create_another', $this->contextParentId));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return DepartmentForm::label('created', $this->contextParentId);
    }

    protected function getRedirectUrl(): string
    {
        return DepartmentResource::getUrl('index', array_filter(['parent' => $this->record->parent_id]));
    }
}
