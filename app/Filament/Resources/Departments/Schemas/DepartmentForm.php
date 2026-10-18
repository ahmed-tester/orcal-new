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
                        \Filament\Forms\Components\Toggle::make('is_global')
                            ->label(__('organization.departments.fields.is_global'))
                            ->helperText(__('organization.departments.fields.is_global_helper'))
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('organization.departments.fields.company'))
                            ->visible(fn ($get) => ! $get('is_global'))
                            ->required(fn ($get) => ! $get('is_global')),

                        \Filament\Forms\Components\Select::make('companies')
                            ->label(__('organization.departments.fields.companies'))
                            ->helperText(__('organization.departments.fields.companies_helper'))
                            ->relationship(
                                name: 'companies',
                                titleAttribute: 'trade_name',
                                modifyQueryUsing: function ($query) {
                                    $user = auth()->user();
                                    if ($user && ! $user->isSuperAdmin()) {
                                        $authorizedIds = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
                                        $query->whereIn('id', !empty($authorizedIds) ? $authorizedIds : [0]);
                                    }
                                }
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => (bool) $get('is_global'))
                            ->required(fn ($get) => (bool) $get('is_global'))
                            ->columnSpanFull(),

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
