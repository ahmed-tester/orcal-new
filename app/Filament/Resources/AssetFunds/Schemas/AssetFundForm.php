<?php

namespace App\Filament\Resources\AssetFunds\Schemas;

use App\Enums\AssetFundStatus;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetFundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('asset_funds.asset_funds.sections.basic_info'))
                    ->description(__('asset_funds.asset_funds.sections.basic_info_desc'))
                    ->icon('heroicon-o-archive-box')
                    ->columns(3)
                    ->schema([
                        TextInput::make('fund_number')
                            ->label(__('asset_funds.asset_funds.fields.fund_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        TextInput::make('fund_name')
                            ->label(__('asset_funds.asset_funds.fields.fund_name'))
                            ->required()
                            ->columnSpan(fn ($record) => $record !== null ? 1 : 2),

                        DatePicker::make('established_date')
                            ->label(__('asset_funds.asset_funds.fields.established_date'))
                            ->default(now())
                            ->required(),

                        Select::make('status')
                            ->label(__('asset_funds.asset_funds.fields.status'))
                            ->options(AssetFundStatus::class)
                            ->default(AssetFundStatus::ACTIVE->value)
                            ->required(),

                        Textarea::make('description')
                            ->label(__('asset_funds.asset_funds.fields.description'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('asset_funds.asset_funds.sections.management'))
                    ->description(__('asset_funds.asset_funds.sections.management_desc'))
                    ->icon('heroicon-o-user-circle')
                    ->columns(2)
                    ->schema([
                        CompanyFormService::makeEmployeeSelect(
                            name: 'manager_employee_id',
                            label: __('asset_funds.asset_funds.fields.manager_employee'),
                            required: false,
                            relationshipName: 'managerEmployee',
                            saudiOnly: true,
                            ignoreCompanyScope: true,
                        ),

                        CompanyFormService::makeEmployeeSelect(
                            name: 'accountant_employee_id',
                            label: __('asset_funds.asset_funds.fields.accountant_employee'),
                            required: false,
                            relationshipName: 'accountantEmployee',
                            saudiOnly: true,
                            ignoreCompanyScope: true,
                        ),

                        Textarea::make('rules_and_conditions')
                            ->label(__('asset_funds.asset_funds.fields.rules_and_conditions'))
                            ->rows(4)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label(__('asset_funds.asset_funds.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

