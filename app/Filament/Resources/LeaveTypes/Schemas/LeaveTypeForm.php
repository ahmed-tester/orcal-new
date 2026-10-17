<?php

namespace App\Filament\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('organization.leave_types.sections.info'))
                    ->columns(2)
                    ->schema([
                        // \App\Services\CompanyFormService::makeCompanySelect('company_id', __('organization.leave_types.fields.company')),

                        TextInput::make('name')
                            ->label(__('organization.leave_types.fields.name'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('annual_balance')
                            ->label(__('organization.leave_types.fields.annual_balance'))
                            ->numeric()
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0))
                            ->required(),

                        Select::make('status')
                            ->label(__('organization.leave_types.fields.status'))
                            ->options(__('organization.statuses'))
                            ->default('active')
                            ->required(),

                        Toggle::make('is_paid')
                            ->label(__('organization.leave_types.fields.is_paid'))
                            ->default(true),

                        Toggle::make('requires_approval')
                            ->label(__('organization.leave_types.fields.requires_approval'))
                            ->default(true),
                    ]),
            ]);
    }
}
