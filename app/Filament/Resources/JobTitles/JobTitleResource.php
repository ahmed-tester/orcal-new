<?php

namespace App\Filament\Resources\JobTitles;

use App\Filament\Resources\JobTitles\Pages\CreateJobTitle;
use App\Filament\Resources\JobTitles\Pages\EditJobTitle;
use App\Filament\Resources\JobTitles\Pages\ListJobTitles;
use App\Filament\Resources\JobTitles\Schemas\JobTitleForm;
use App\Filament\Resources\JobTitles\Tables\JobTitlesTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\JobTitle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class JobTitleResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = JobTitle::class;

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    public static function getModelLabel(): string
    {
        return __('organization.job_titles.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('organization.job_titles.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.job_titles');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.hr_group');
    }

    public static function form(Schema $schema): Schema
    {
        return JobTitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JobTitlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobTitles::route('/'),
            'create' => CreateJobTitle::route('/create'),
            'edit' => EditJobTitle::route('/{record}/edit'),
        ];
    }
}
