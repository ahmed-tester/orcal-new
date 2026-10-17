<?php

namespace App\Filament\Resources\InstallmentTypes\Schemas;

use App\Enums\InstallmentCategory;
use App\Filament\Components\OrgSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstallmentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('finance.installment_types.sections.type_info'))
                    ->description(__('finance.installment_types.sections.type_info_desc'))
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        OrgSelect::company('company_id', __('finance.installment_types.fields.company'))
                            ->nullable(),

                        Select::make('category')
                            ->label(__('finance.installment_types.fields.category'))
                            ->options(InstallmentCategory::class)
                            ->default(InstallmentCategory::ADVANCES->value)
                            ->required(),

                        TextInput::make('name')
                            ->label(__('finance.installment_types.fields.name'))
                            ->placeholder(__('finance.installment_types.placeholders.name'))
                            ->required(),

                        Select::make('status')
                            ->label(__('finance.installment_types.fields.status'))
                            ->options(\App\Enums\GeneralStatus::class)
                            ->default(\App\Enums\GeneralStatus::ACTIVE->value)
                            ->required(),
                    ]),
            ]);
    }
}
