<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\Pages\Concerns\HasServiceTransactionActions;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Services\OperationsReconciliationService;
use Filament\Resources\Pages\EditRecord;

class EditServiceTransaction extends EditRecord
{
    use HasServiceTransactionActions;

    protected static string $resource = ServiceTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $recordModel = $this->getRecord();
        OperationsReconciliationService::reconcile($recordModel);
        $this->refreshFormData(['workflow_status', 'metadata', 'service_status']);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['total_amount']);

        return $data;
    }

    protected function afterSave(): void
    {
        OperationsReconciliationService::reconcile($this->getRecord());
    }
}
