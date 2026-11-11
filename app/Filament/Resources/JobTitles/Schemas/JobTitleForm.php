<?php

namespace App\Filament\Resources\JobTitles\Schemas;

use App\Enums\JobTitleScope;
use App\Models\JobTitle;
use App\Services\CompanyFormService;
use App\Services\JobTitleService;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
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
                        Radio::make('title_scope')
                            ->label('نطاق المسمى الوظيفي')
                            ->options(JobTitleScope::class)
                            ->default(JobTitleScope::COMPANY->value)
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                $component->state($record?->isGlobal() ? JobTitleScope::GLOBAL->value : JobTitleScope::COMPANY->value);
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                $isGlobal = ($state === JobTitleScope::GLOBAL->value);
                                $set('is_global', $isGlobal);
                                if ($isGlobal) {
                                    $set('company_id', null);
                                    $set('department_id', null);
                                }
                            })
                            ->columnSpanFull(),

                        Hidden::make('is_global')
                            ->default(false)
                            ->dehydrated(true),

                        CompanyFormService::makeCompanySelect('company_id', __('organization.job_titles.fields.company'))
                            ->visible(fn (callable $get) => $get('title_scope') !== JobTitleScope::GLOBAL->value)
                            ->required(fn (callable $get) => $get('title_scope') !== JobTitleScope::GLOBAL->value),

                        CompanyFormService::makeDepartmentSelect('department_id', __('organization.job_titles.fields.department'))
                            ->visible(fn (callable $get) => $get('title_scope') !== JobTitleScope::GLOBAL->value)
                            ->nullable(),

                        TextInput::make('name')
                            ->label(__('organization.job_titles.fields.name'))
                            ->placeholder('مثال: مدير عام، محاسب، موظف استقبال...')
                            ->required(),

                        TextInput::make('job_code')
                            ->label(__('organization.job_titles.fields.job_code'))
                            ->placeholder('مثال: MGR-01')
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? trim($state) : null)
                            ->unique(JobTitle::class, 'job_code', ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                                $companyId = $get('company_id');

                                return $companyId ? $rule->where('company_id', $companyId) : $rule->whereNull('company_id');
                            }),

                        Select::make('parent_id')
                            ->label(__('organization.job_titles.fields.parent'))
                            ->options(fn (callable $get, $record) => app(JobTitleService::class)->getParentTitleOptions(
                                $get('company_id'),
                                $record?->id
                            ))
                            ->searchable()
                            ->preload()
                            ->nullable(),

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
