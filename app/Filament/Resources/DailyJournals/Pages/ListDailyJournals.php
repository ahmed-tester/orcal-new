<?php

namespace App\Filament\Resources\DailyJournals\Pages;

use App\Filament\Resources\DailyJournals\DailyJournalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyJournals extends ListRecords
{
    protected static string $resource = DailyJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
