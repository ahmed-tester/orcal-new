<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayrollRecord extends EditRecord
{
    protected static string $resource = PayrollRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label(__('finance.payroll_records.actions.print_record'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('payroll-records.print', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
