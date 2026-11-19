<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaskAssignments extends ListRecords
{
    protected static string $resource = TaskAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryView')
                ->label(__('tasks.summary.title'))
                ->icon('heroicon-o-chart-bar')
                ->color('gray')
                ->url(fn (): string => TaskAssignmentResource::getUrl('index')),

            CreateAction::make(),
        ];
    }
}
