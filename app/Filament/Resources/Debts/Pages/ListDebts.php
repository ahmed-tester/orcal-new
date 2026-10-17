<?php

namespace App\Filament\Resources\Debts\Pages;

use App\Filament\Resources\Debts\DebtResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDebts extends ListRecords
{
    protected static string $resource = DebtResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Filament\Actions\Action::make('print_all')
                ->label('طباعة وحفظ الكشف 🖨️')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(route('exports.debts-statement'))
                ->openUrlInNewTab(),
        ];
    }
}
