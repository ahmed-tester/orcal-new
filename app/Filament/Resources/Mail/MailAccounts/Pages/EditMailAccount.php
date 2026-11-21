<?php

namespace App\Filament\Resources\Mail\MailAccounts\Pages;

use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Services\Mail\MailAccountService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMailAccount extends EditRecord
{
    protected static string $resource = MailAccountResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $employeeIds = (array) ($this->data['employees'] ?? []);

        return app(MailAccountService::class)->saveAccount(
            $record,
            $data,
            $employeeIds,
            auth()->user()
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
