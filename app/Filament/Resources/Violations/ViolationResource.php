<?php

namespace App\Filament\Resources\Violations;

use App\Filament\Resources\Violations\Pages\CreateViolation;
use App\Filament\Resources\Violations\Pages\EditViolation;
use App\Filament\Resources\Violations\Pages\ListViolations;
use App\Filament\Resources\Violations\Schemas\ViolationForm;
use App\Filament\Resources\Violations\Tables\ViolationsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\Violation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ViolationResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = Violation::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getModelLabel(): string
    {
        return __('violations.violations.single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('violations.violations.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.violations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.violations_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ViolationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViolationsTable::configure($table);
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
            'index' => ListViolations::route('/'),
            'create' => CreateViolation::route('/create'),
            'edit' => EditViolation::route('/{record}/edit'),
        ];
    }
}
