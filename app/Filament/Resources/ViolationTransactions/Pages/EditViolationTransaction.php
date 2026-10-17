<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use Filament\Resources\Pages\EditRecord;

class EditViolationTransaction extends EditRecord
{
    protected static string $resource = ViolationTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action omitted to protect transaction audit trail
        ];
    }
}
