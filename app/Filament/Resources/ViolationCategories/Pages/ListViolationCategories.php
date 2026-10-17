<?php

namespace App\Filament\Resources\ViolationCategories\Pages;

use App\Filament\Resources\ViolationCategories\ViolationCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListViolationCategories extends ListRecords
{
    protected static string $resource = ViolationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export_pdf')
                ->label('تصدير كشف PDF / طباعة 🖨️')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(route('exports.violations', ['section' => 'categories']))
                ->openUrlInNewTab(),
        ];
    }
}
