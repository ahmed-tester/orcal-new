<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\Pages\Concerns\HasServiceTransactionActions;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewServiceTransaction extends ViewRecord
{
    use HasServiceTransactionActions;

    protected static string $resource = ServiceTransactionResource::class;
}
