<?php

namespace App\Filament\Resources\ViolationTransactions\Pages;

use App\Enums\ViolationTransactionType;
use App\Filament\Resources\ViolationTransactions\ViolationTransactionResource;
use App\Services\ViolationSummaryService;
use Filament\Resources\Pages\CreateRecord;

class CreateViolationTransaction extends CreateRecord
{
    protected static string $resource = ViolationTransactionResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('violation_id')) {
            $id = filter_var(request()->query('violation_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id !== false) {
                $violation = ViolationSummaryService::violations()->where('remaining_amount', '>', 0)->find($id);
                if ($violation) {
                    $this->form->fill(array_merge($this->data ?? [], [
                        'violation_id' => $violation->id,
                        'company_id' => $violation->company_id,
                        'transaction_type' => ViolationTransactionType::OUTGOING->value,
                        'amount' => $violation->remaining_amount,
                    ]));
                }
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['executed_by'] = auth()->id();

        return $data;
    }
}
