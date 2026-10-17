<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViolationTransaction extends CreateRecord
{
    protected static string $resource = ViolationTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['executed_by'] = auth()->id();

        return $data;
    }
}
