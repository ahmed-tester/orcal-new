<?php

namespace App\Filament\Resources\InvoiceTypes\Pages;

use App\Filament\Resources\InvoiceTypes\InvoiceTypeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateInvoiceType extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = InvoiceTypeResource::class;
}
