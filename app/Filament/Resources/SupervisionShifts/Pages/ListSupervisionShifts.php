<?php

namespace App\Filament\Resources\SupervisionShifts\Pages;

use App\Filament\Resources\SupervisionShifts\SupervisionShiftResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupervisionShifts extends ListRecords
{
    protected static string $resource = SupervisionShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Filament\Actions\Action::make('print_all')
                ->label(__('tasks.supervision_shifts.actions.print_all'))
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(route('exports.supervision-shifts-statement'))
                ->openUrlInNewTab(),
        ];
    }
}
