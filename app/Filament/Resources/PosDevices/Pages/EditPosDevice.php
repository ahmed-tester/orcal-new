<?php

namespace App\Filament\Resources\PosDevices\Pages;

use App\Filament\Resources\PosDevices\PosDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPosDevice extends EditRecord
{
    protected static string $resource = PosDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('finance.pos_devices.modals.delete_heading'))
                ->modalDescription(__('finance.pos_devices.modals.delete_desc')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
