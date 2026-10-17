<?php

namespace App\Filament\Resources\ViolationRegulations\Pages;

use App\Filament\Resources\ViolationRegulations\ViolationRegulationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViolationRegulations extends ListRecords
{
    protected static string $resource = ViolationRegulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_pdf')
                ->label('تصدير كشف PDF / طباعة 🖨️')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('exports.violations', ['section' => 'regulations']))
                ->openUrlInNewTab(),
        ];
    }
}
