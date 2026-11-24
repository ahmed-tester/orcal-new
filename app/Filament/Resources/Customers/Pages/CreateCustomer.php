<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateCustomer extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = CustomerResource::class;
}
