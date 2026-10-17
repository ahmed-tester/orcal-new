<?php

namespace App\Filament\Resources\PaymentCollectionItems\Pages;

use App\Filament\Resources\PaymentCollectionItems\PaymentCollectionItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentCollectionItem extends CreateRecord
{
    protected static string $resource = PaymentCollectionItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
