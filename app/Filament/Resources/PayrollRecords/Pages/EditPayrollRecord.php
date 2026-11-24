<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Filament\Components\PayrollFinancialActions;
use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditPayrollRecord extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = PayrollRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PayrollFinancialActions::confirmCollectionReceipt(),
            PayrollFinancialActions::requestCollectionHandover(),
            PayrollFinancialActions::handoverQr(),
            PayrollFinancialActions::reverseCollectionReceipt(),
            PayrollFinancialActions::deductCollectionFromSalary(),
            PayrollFinancialActions::confirmPendingPayrollTransfer(),
            PayrollFinancialActions::reversePayrollPayment(),
            PayrollFinancialActions::financialSummary(),
            Action::make('print')
                ->label(__('finance.payroll_records.actions.print_record'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('payroll-records.print', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
