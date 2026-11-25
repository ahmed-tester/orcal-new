<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Filament\Components\PayrollFinancialActions;
use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use App\Models\PayrollRecord;
use App\Services\Payroll\PayrollEntitlementService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditPayrollRecord extends EditRecord
{
    protected static string $resource = PayrollRecordResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record instanceof PayrollRecord) {
            if (array_key_exists('employee_id', $data) && (int) $data['employee_id'] !== (int) $record->employee_id) {
                throw ValidationException::withMessages([
                    'employee_id' => __('finance.payroll_records.fields.employee'),
                ]);
            }

            if (array_key_exists('operation_type', $data) && (string) $data['operation_type'] !== (string) $record->operation_type) {
                throw ValidationException::withMessages([
                    'operation_type' => __('finance.payroll_records.fields.operation_type'),
                ]);
            }

            if (array_key_exists('payroll_month', $data) && ! empty($data['payroll_month'])) {
                $submittedYm = Carbon::parse($data['payroll_month'])->format('Y-m');
                $originalYm = $record->payroll_month?->format('Y-m');
                if ($originalYm !== null && $submittedYm !== $originalYm) {
                    throw ValidationException::withMessages([
                        'payroll_month' => __('finance.payroll_records.fields.payroll_month'),
                    ]);
                }
            }

            if ($record->isPayroll() && ! $record->hasFinancialImpact()) {
                $month = $record->payroll_month?->format('Y-m') ?? now()->format('Y-m');
                $updated = PayrollEntitlementService::upsertMonthlyPayroll((int) $record->employee_id, $month, [
                    'skip_if_financially_impacted' => false,
                    'record_date' => $data['record_date'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'overrides' => array_filter([
                        'basic_salary' => $data['basic_salary'] ?? null,
                    ], fn ($v) => $v !== null),
                ]);

                if ($updated) {
                    return $updated;
                }
            }
        }

        $record->update($data);

        return $record;
    }

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
            PayrollFinancialActions::recalculateSaudiPolicy(),
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
