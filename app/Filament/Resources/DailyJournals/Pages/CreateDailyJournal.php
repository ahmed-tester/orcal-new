<?php

namespace App\Filament\Resources\DailyJournals\Pages;

use App\Filament\Resources\DailyJournals\DailyJournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyJournal extends CreateRecord
{
    protected static string $resource = DailyJournalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        if (empty($data['recorded_by'])) {
            $data['recorded_by'] = auth()->user()?->employee?->id;
        }

        $purchases = (float)($data['purchases'] ?? 0);
        $sales = (float)($data['sales'] ?? 0);
        $data['net_amount'] = round($sales - $purchases, 2);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
