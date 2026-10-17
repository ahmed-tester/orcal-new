<?php

namespace App\Filament\Resources\TaskAssignments;

use App\Filament\Resources\TaskAssignments\Pages\CreateTaskAssignment;
use App\Filament\Resources\TaskAssignments\Pages\EditTaskAssignment;
use App\Filament\Resources\TaskAssignments\Pages\ListTaskAssignments;
use App\Filament\Resources\TaskAssignments\Schemas\TaskAssignmentForm;
use App\Filament\Resources\TaskAssignments\Tables\TaskAssignmentsTable;
use App\Filament\Traits\HasCompanyScope;
use App\Models\TaskAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TaskAssignmentResource extends Resource
{
    use HasCompanyScope;

    protected static ?string $model = TaskAssignment::class;

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    public static function getModelLabel(): string
    {
        return __('tasks.assignment_single');
    }

    public static function getPluralModelLabel(): string
    {
        return __('tasks.assignment_plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.task_assignments');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.tasks_group');
    }

    public static function form(Schema $schema): Schema
    {
        return TaskAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaskAssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaskAssignments::route('/'),
            'create' => CreateTaskAssignment::route('/create'),
            'edit' => EditTaskAssignment::route('/{record}/edit'),
        ];
    }
}
