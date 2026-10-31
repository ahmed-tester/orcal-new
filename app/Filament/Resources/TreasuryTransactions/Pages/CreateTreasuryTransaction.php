<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Services\TreasuryBeneficiaryService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateTreasuryTransaction extends CreateRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    public bool $shouldPrintAfterCreate = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
                ->label(__('finance.treasury_transactions.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $this->shouldPrintAfterCreate = true;
                    $this->create();
                }),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        if (! empty($data['employee_id']) && ! empty($data['beneficiary_type'])) {
            if ($name = TreasuryBeneficiaryService::resolveEmployeeName($data['beneficiary_type'], $data['employee_id'])) {
                $data['recipient_or_payer'] = $name;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        if ($this->shouldPrintAfterCreate && $this->record) {
            return route('treasury.voucher.print', $this->record);
        }

        return $this->getResource()::getUrl('index');
    }
}
