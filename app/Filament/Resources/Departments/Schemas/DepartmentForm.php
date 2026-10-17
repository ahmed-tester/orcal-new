<?php

namespace App\Filament\Resources\Departments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('organization.departments.sections.info'))
                    ->columns(3)
                    ->schema([
                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('organization.departments.fields.company')),

                        TextInput::make('name')
                            ->label(__('organization.departments.fields.name'))
                            ->required(),

                        TextInput::make('department_code')
                            ->label(__('organization.departments.fields.department_code')),

                        Select::make('parent_id')
                            ->label(__('organization.departments.fields.parent'))
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label(__('organization.departments.fields.status'))
                            ->options(__('organization.statuses'))
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }
}
