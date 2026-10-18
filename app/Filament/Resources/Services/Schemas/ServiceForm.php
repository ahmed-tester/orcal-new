<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('services.sections.info'))
                    ->description(__('services.sections.info_desc'))
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        \Filament\Forms\Components\ToggleButtons::make('scope_type')
                            ->label(__('services.scope_type'))
                            ->options([
                                'general' => __('services.scope_general'),
                                'company' => __('services.scope_company'),
                            ])
                            ->icons([
                                'general' => 'heroicon-m-globe-alt',
                                'company' => 'heroicon-m-building-office-2',
                            ])
                            ->colors([
                                'general' => 'success',
                                'company' => 'primary',
                            ])
                            ->default('general')
                            ->inline()
                            ->grouped()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (callable $set, $record) {
                                if ($record) {
                                    $set('scope_type', $record->company_id ? 'company' : 'general');
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'general') {
                                    $set('company_id', null);
                                }
                            })
                            ->columnSpanFull(),

                        \App\Services\CompanyFormService::makeCompanySelect('company_id', __('services.fields.company'), required: false)
                            ->default(null)
                            ->visible(fn (callable $get) => ($get('scope_type') ?? 'general') === 'company')
                            ->required(fn (callable $get) => ($get('scope_type') ?? 'general') === 'company')
                            ->dehydrateStateUsing(fn ($state, callable $get) => ($get('scope_type') ?? 'general') === 'general' ? null : $state),

                        TextInput::make('beneficiary_name')
                            ->label(__('services.beneficiary_name'))
                            ->placeholder(__('services.beneficiary_placeholder'))
                            ->maxLength(255)
                            ->visible(fn (callable $get) => ($get('scope_type') ?? 'general') === 'general'),

                        TextInput::make('name')
                            ->label(__('services.fields.name'))
                            ->placeholder(__('services.name_placeholder'))
                            ->required()
                            ->unique(\App\Models\Service::class, 'name', ignoreRecord: true, modifyRuleUsing: function ($rule, callable $get) {
                                $cid = $get('company_id');
                                return $cid ? $rule->where('company_id', $cid) : $rule;
                            })
                            ->maxLength(255),

                        Select::make('target_type')
                            ->label(__('services_tracking.quick_add.target_type'))
                            ->options([
                                'company'  => __('services_tracking.toggle_options.target_company'),
                                'employee' => __('services_tracking.toggle_options.target_employee'),
                                'general'  => __('services.general'),
                            ])
                            ->default('general')
                            ->required(),

                        Select::make('category')
                            ->label(__('services_tracking.quick_add.category'))
                            ->options([
                                'cr_licenses'  => __('services_tracking.categories.cr_licenses'),
                                'municipal'    => __('services_tracking.categories.municipal'),
                                'work_permits' => __('services_tracking.categories.work_permits'),
                                'passports'    => __('services_tracking.categories.passports'),
                                'visas'        => __('services_tracking.categories.visas'),
                                'insurance'    => __('services_tracking.categories.insurance'),
                                'other'        => __('services_tracking.categories.other'),
                            ])
                            ->default('other')
                            ->required(),

                        TextInput::make('price')
                            ->label(__('services.fields.price'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->minValue(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->required(),

                        Select::make('status')
                            ->label(__('services.fields.status'))
                            ->options(__('services.statuses'))
                            ->default('active')
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('services.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
