<?php

namespace App\Filament\Resources\InvoiceTypes\Pages;

use App\Filament\Resources\InvoiceTypes\InvoiceTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditInvoiceType extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = InvoiceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
