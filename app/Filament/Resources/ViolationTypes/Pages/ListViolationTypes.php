<?php

namespace App\Filament\Resources\ViolationTypes\Pages;

use App\Filament\Resources\ViolationTypes\ViolationTypeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViolationTypes extends ListRecords
{
    protected static string $resource = ViolationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_pdf')
                ->label('تصدير كشف PDF / طباعة 🖨️')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('exports.violations', ['section' => 'types']))
                ->openUrlInNewTab(),
        ];
    }
}
