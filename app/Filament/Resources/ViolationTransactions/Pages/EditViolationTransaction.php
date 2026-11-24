<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use App\Services\ViolationTransactionFormService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Filament\Concerns\RedirectsToIndex;

class EditViolationTransaction extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ViolationTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete action omitted to protect transaction audit trail
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['link_type'] = $this->record->link_type;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ViolationTransactionFormService::normalizeLinkData($data);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(fn () => parent::handleRecordUpdate($record, $data));
    }
}
