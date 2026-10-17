<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTreasuryTransaction extends EditRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_voucher')
                ->label(__('finance.treasury_transactions.actions.print_voucher'))
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->url(fn ($record) => route('treasury.voucher.print', $record))
                ->openUrlInNewTab(),
            // DeleteAction::make(), // تم تعطيل حذف حركات الخزينة
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge(
            $data,
            \App\Services\TreasuryBeneficiaryService::detectBeneficiary($data['recipient_or_payer'] ?? null)
        );
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['employee_id']) && !empty($data['beneficiary_type'])) {
            if ($name = \App\Services\TreasuryBeneficiaryService::resolveEmployeeName($data['beneficiary_type'], $data['employee_id'])) {
                $data['recipient_or_payer'] = $name;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
