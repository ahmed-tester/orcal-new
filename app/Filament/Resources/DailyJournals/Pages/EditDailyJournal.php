<?php

namespace App\Filament\Resources\DailyJournals\Pages;

use App\Filament\Resources\DailyJournals\DailyJournalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyJournal extends EditRecord
{
    protected static string $resource = DailyJournalResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $purchases = (float)($data['purchases'] ?? 0);
        $sales = (float)($data['sales'] ?? 0);
        $data['net_amount'] = round($sales - $purchases, 2);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
