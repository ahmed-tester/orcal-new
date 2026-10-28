<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallments extends ListRecords
{
    protected static string $resource = InstallmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('summaryView')
                ->label(__('finance.installments.summary.summary_view'))
                ->icon('heroicon-o-chart-pie')
                ->color('gray')
                ->url(fn (): string => InstallmentResource::getUrl('index')),
            CreateAction::make(),
        ];
    }
}
