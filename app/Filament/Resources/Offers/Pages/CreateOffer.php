<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateOffer extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = OfferResource::class;
}
