<?php

namespace App\Filament\Resources\Violations\Pages;

use App\Filament\Resources\Violations\ViolationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViolations extends ListRecords
{
    protected static string $resource = ViolationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_pdf')
                ->label('تصدير كشف المخالفات PDF 🖨️')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('exports.violations', ['section' => 'violations']))
                ->openUrlInNewTab(),
        ];
    }
}
