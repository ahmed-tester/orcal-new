<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCompanies extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('print_all')
                ->label(__('organization.companies.actions.print_all'))
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(route('exports.companies-statement'))
                ->openUrlInNewTab(),
        ];
    }
}
