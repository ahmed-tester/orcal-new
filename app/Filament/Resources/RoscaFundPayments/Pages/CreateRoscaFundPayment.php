<?php

namespace App\Filament\Resources\RoscaFundPayments\Pages;

use App\Filament\Resources\RoscaFundPayments\RoscaFundPaymentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateRoscaFundPayment extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoscaFundPaymentResource::class;
}
