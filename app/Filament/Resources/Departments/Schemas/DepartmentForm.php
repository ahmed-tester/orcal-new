<?php

namespace App\Filament\Resources\Departments\Schemas;

use App\Enums\DepartmentScope;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Models\Department;
use App\Models\Employee;
use App\Services\CompanyFormService;
use App\Services\Departments\DepartmentHierarchyService;
use App\Services\Departments\DepartmentScopeService;
use App\Services\Tasks\Renewals\DocumentRenewalAccessService;
use App\Services\Tasks\Renewals\RenewalDocumentRegistry;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class DepartmentForm
{
    public static function label(string $key, ?int $parentId): string
    {
        $levelKey = DepartmentHierarchyService::levelKey(new Department(['parent_id' => $parentId]));

        return __('organization.departments.hierarchy.form.'.$key, [
            'type' => __('organization.departments.hierarchy.levels.'.$levelKey),
            'type_name' => __('organization.departments.hierarchy.form.type_names.'.$levelKey),
        ]);
    }

    public static function contextParentId($get, $livewire): ?int
    {
        $id = $livewire instanceof CreateDepartment ? $livewire->contextParentId : $get('parent_id');

        return filled($id) ? (int) $id : null;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(fn ($get, $livewire) => static::label('section', static::contextParentId($get, $livewire)))
                    ->columns(3)
                    ->schema([
                        Section::make()
                            ->compact()
                            ->secondary()
                            ->columnSpanFull()
                            ->columns(['default' => 1, 'sm' => 3])
                            ->schema([
                                TextEntry::make('context_parent')
                                    ->label(__('organization.departments.hierarchy.form.parent_context'))
                                    ->icon('heroicon-o-folder-open')
                                    ->weight(FontWeight::SemiBold)
                                    ->visible(fn ($livewire) => $livewire instanceof CreateDepartment && $livewire->creationParent() !== null)
                                    ->state(fn ($livewire) => $livewire instanceof CreateDepartment ? $livewire->creationParent()?->name : null),

                                TextEntry::make('inherited_company')
                                    ->label(__('organization.departments.hierarchy.form.inherited_company'))
                                    ->icon('heroicon-o-building-office-2')
                                    ->weight(FontWeight::SemiBold)
                                    ->visible(fn ($livewire) => $livewire instanceof CreateDepartment && $livewire->inheritsCompany())
                                    ->state(fn ($livewire) => $livewire instanceof CreateDepartment ? $livewire->creationParent()?->company?->trade_name : null),

                                TextEntry::make('current_level')
                                    ->label(__('organization.departments.hierarchy.columns.level'))
                                    ->badge()
                                    ->icon('heroicon-o-squares-2x2')
                                    ->color(fn ($get, $livewire) => match (DepartmentHierarchyService::levelKey(new Department([
                                        'parent_id' => static::contextParentId($get, $livewire),
                                    ]))) {
                                        'administration' => 'primary',
                                        'main' => 'info',
                                        default => 'success',
                                    })
                                    ->state(fn ($get, $livewire) => DepartmentHierarchyService::levelLabel(new Department([
                                        'parent_id' => static::contextParentId($get, $livewire),
                                    ]))),
                            ]),

                        ...DepartmentScopeService::makeFormComponents(),

                        TextInput::make('name')
                            ->label(fn ($get, $livewire) => static::label('name', static::contextParentId($get, $livewire)))
                            ->required(),

                        CompanyFormService::makeEmployeeSelect(
                            name: 'manager_id',
                            label: __('organization.departments.fields.manager'),
                            required: false,
                            relationshipName: 'manager',
                            saudiOnly: true,
                            ignoreCompanyScope: true,
                        )
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set, ?Department $record): void {
                                if (! filled($state)) {
                                    $set('renewal_permissions', []);

                                    return;
                                }

                                $newUser = Employee::with('user')->find((int) $state)?->user;
                                $newPerms = $newUser ? DocumentRenewalAccessService::directRenewalPermissions($newUser) : [];

                                if ($record && $record->manager_id !== null && (int) $state !== (int) $record->manager_id) {
                                    $oldUser = $record->manager?->user;
                                    $oldPerms = $oldUser ? DocumentRenewalAccessService::directRenewalPermissions($oldUser) : [];
                                    $newPerms = array_values(array_unique(array_merge($newPerms, $oldPerms)));
                                }

                                $set('renewal_permissions', $newPerms);
                            })
                            ->helperText(__('organization.departments.hierarchy.helpers.manager'))
                            ->visible(fn ($get, $livewire, ?Department $record): bool => (auth()->user()?->isSuperAdmin() ?? false)
                                && static::contextParentId($get, $livewire) === null
                                && ($record === null || DepartmentScopeService::resolveScope($record) === DepartmentScope::FAWAZ_GROUP)),

                        CheckboxList::make('renewal_permissions')
                            ->label(__('document_renewals.layer_one.permissions_label'))
                            ->helperText(__('document_renewals.layer_one.permissions_helper'))
                            ->options(fn (): array => RenewalDocumentRegistry::permissionOptions())
                            ->columns(3)
                            ->columnSpanFull()
                            ->visible(fn ($get, $livewire, ?Department $record): bool => (auth()->user()?->isSuperAdmin() ?? false)
                                && static::contextParentId($get, $livewire) === null
                                && filled($get('manager_id'))
                                && ($record === null || DepartmentScopeService::resolveScope($record) === DepartmentScope::FAWAZ_GROUP)),

                        Select::make('parent_id')
                            ->label(__('organization.departments.hierarchy.form.parent_context'))
                            ->visible(fn (string $operation) => $operation !== 'create')
                            ->helperText(__('organization.departments.hierarchy.helpers.parent'))
                            ->options(fn (?Department $record) => DepartmentHierarchyService::parentOptions(
                                DepartmentResource::getEloquentQuery(),
                                $record,
                            ))
                            ->default(fn () => request()->integer('parent') ?: null)
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $targetLevel = DepartmentHierarchyService::targetLevelForParent(filled($state) ? (int) $state : null);
                                if ($targetLevel > 1) {
                                    $set('manager_id', null);
                                }
                                if ($targetLevel <= 2) {
                                    $set('department_scope', DepartmentScope::FAWAZ_GROUP->value);
                                    $set('is_global', true);
                                    $set('company_id', null);
                                    $set('companies', []);
                                } elseif ($get('department_scope') === DepartmentScope::FAWAZ_GROUP->value) {
                                    $set('department_scope', DepartmentScope::SINGLE->value);
                                    $set('is_global', false);
                                    $set('company_id', null);
                                    $set('companies', []);
                                }
                            })
                            ->searchable()
                            ->preload(),

                        TextInput::make('sort_order')
                            ->label(fn ($get, $livewire) => static::label('order', static::contextParentId($get, $livewire)))
                            ->helperText(__('organization.departments.hierarchy.helpers.sort_order'))
                            ->numeric()
                            ->integer()
                            ->minValue(0),

                        Select::make('status')
                            ->label(fn ($get, $livewire) => static::label('status', static::contextParentId($get, $livewire)))
                            ->options(__('organization.statuses'))
                            ->default('active')
                            ->required(),

                        Textarea::make('description')
                            ->label(fn ($get, $livewire) => static::label('description', static::contextParentId($get, $livewire)))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
