<?php

namespace App\Filament\Resources\JobTitles\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JobTitleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('organization.job_titles.sections.info'))
                    ->columns(3)
                    ->schema([
                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('organization.job_titles.fields.company')),
                        \App\Services\CompanyFormService::makeDepartmentSelect('department_id', __('organization.job_titles.fields.department')),

                        TextInput::make('name')
                            ->label(__('organization.job_titles.fields.name'))
                            ->required(),

                        TextInput::make('job_code')
                            ->label(__('organization.job_titles.fields.job_code'))
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? trim($state) : null)
                            ->unique(\App\Models\JobTitle::class, 'job_code', ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                                return $rule->where('company_id', $get('company_id'));
                            }),

                        Select::make('parent_id')
                            ->label(__('organization.job_titles.fields.parent'))
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('sort_order')
                            ->label(__('organization.job_titles.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0)),

                        Select::make('status')
                            ->label(__('organization.job_titles.fields.status'))
                            ->options(__('organization.statuses'))
                            ->default('active')
                            ->required(),

                        Textarea::make('job_description')
                            ->label(__('organization.job_titles.fields.job_description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
