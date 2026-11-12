<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Models\Service;
use App\Services\ServiceReceiptCreationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateServiceTransaction extends CreateRecord
{
    protected static string $resource = ServiceTransactionResource::class;

    /**
     * Collection inputs carried out of the form state by
     * ServiceReceiptCreationService::extract(), kept separate from the
     * transaction model data.
     */
    protected ?array $receiptPayload = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['scope_type'] ?? 'internal') === 'internal') {
            $price = Service::query()->whereKey($data['service_id'] ?? null)->value('price');

            if ($price === null) {
                throw ValidationException::withMessages([
                    'data.service_id' => __('validation.exists', ['attribute' => __('services_tracking.fields.service')]),
                ]);
            }

            $data['total_amount'] = $price;
        }

        $payload = ServiceReceiptCreationService::extract($data);
        $this->receiptPayload = $payload['receipt'];

        return $payload['transaction'];
    }

    protected function handleRecordCreation(array $data): Model
    {
        [$record, $outcome] = DB::transaction(function () use ($data) {
            ServiceReceiptCreationService::assertNewAttempt(
                $this->receiptPayload,
                (string) ($data['metadata']['submission_key'] ?? ''),
            );

            $record = parent::handleRecordCreation($data);

            return [$record, ServiceReceiptCreationService::process($record, $this->receiptPayload)];
        });

        ServiceReceiptCreationService::notify($outcome);

        return $record;
    }
}
