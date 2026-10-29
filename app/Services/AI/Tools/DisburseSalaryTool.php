<?php

namespace App\Services\AI\Tools;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;

class DisburseSalaryTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'disburse_payroll_salary';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'صرف وتسليم راتب لموظف (سند صرف وخزينة) مع تحديث حالة مسير الراتب إلى مسدد أو مسدد جزئياً.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية الوطنية أو رقم الإقامة أو الرقم الوظيفي أو الجوال',
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المصروف فعلياً بالريال السعودي',
                    ],
                    'payroll_month' => [
                        'type' => 'STRING',
                        'description' => 'شهر المسير المراد صرفه بصيغة YYYY-MM (مثال: 2026-10). إذا لم يحدد يُصرف لأقرب مسير مفتوح به رصيد مستحق.',
                    ],
                    'payment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ الصرف الفعلي بصيغة YYYY-MM-DD (الافتراضي تاريخ اليوم)',
                    ],
                    'payment_method' => [
                        'type' => 'STRING',
                        'description' => 'طريقة الصرف: bank_transfer (تحويل بنكي) أو cash (نقداً) أو cheque (شيك)',
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'رقم الحوالة البنكية أو ملاحظات الصرف',
                    ],
                ],
                'required' => ['employee_identifier', 'amount'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $amount = (float) ($arguments['amount'] ?? 0);
            $monthStr = ! empty($arguments['payroll_month']) ? trim($arguments['payroll_month']) : null;
            $paymentDate = ! empty($arguments['payment_date']) ? Carbon::parse($arguments['payment_date'])->toDateString() : date('Y-m-d');
            $paymentMethod = ! empty($arguments['payment_method']) ? trim($arguments['payment_method']) : 'bank_transfer';
            $notes = trim($arguments['notes'] ?? '');

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب لصرف الراتب.'];
            }

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'مبلغ الصرف يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $employee = $this->resolveEmployee($identifier, $companyId);
            if (! $employee) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على الموظف ({$identifier}) في النظام لإتمام عملية الصرف.",
                ];
            }

            // تحديد مسير الراتب المستهدف
            $record = null;
            if ($monthStr) {
                $monthDate = Carbon::parse($monthStr)->startOfMonth()->toDateString();
                $record = PayrollRecord::where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->whereDate('payroll_month', $monthDate)
                    ->first();
            } else {
                // البحث عن أحدث مسير به رصيد متبقي لم يُصرف بالكامل
                $record = PayrollRecord::where('company_id', $companyId)
                    ->where('employee_id', $employee->id)
                    ->where('payment_status', '!=', PaymentStatus::PAID)
                    ->orderByDesc('payroll_month')
                    ->first();
            }

            // إذا لم يتم العثور على مسير، يتم إنشاء مسير للشهر تلقائياً لحفظ الصرف داخله
            if (! $record) {
                $targetMonth = $monthStr ? Carbon::parse($monthStr)->startOfMonth()->toDateString() : Carbon::parse($paymentDate)->startOfMonth()->toDateString();
                $basicSalary = (float) $employee->basic_salary > 0 ? (float) $employee->basic_salary : $amount;

                $record = PayrollRecord::create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'payroll_month' => $targetMonth,
                    'record_date' => $paymentDate,
                    'basic_salary' => $basicSalary,
                    'operation_type' => 'payroll',
                    'notes' => 'تم إنشاؤه تلقائياً أثناء استيراد صرف الراتب',
                ]);
            }

            // حماية من التكرار المزدوج: فحص هل تم تسجيل نفس عملية الصرف بنفس المبلغ والتاريخ لنفس المسير
            $duplicatePayment = PayrollPayment::where('payroll_record_id', $record->id)
                ->where('amount', $amount)
                ->whereDate('payment_date', $paymentDate)
                ->first();

            if ($duplicatePayment) {
                return [
                    'success' => false,
                    'message' => 'تنبيه (مكرر): تم صرف مبلغ ('.number_format($amount, 2)." ر.س) للموظف ({$employee->user?->name}) بتاريخ ({$paymentDate}) مسبقاً بسند رقم #{$duplicatePayment->payment_number}، تم تخطيه لمنع ازدواجية الصرف.",
                ];
            }

            // تنفيذ الصرف الفعلي وإصدار حركة الخزينة وتحديث المسير
            $payment = PayrollCalculationService::disburseSalary(
                record: $record,
                amount: $amount,
                paymentDate: $paymentDate,
                paymentMethod: $paymentMethod,
                notes: $notes ?: 'استيراد ذكي — صرف رواتب'
            );

            $freshRecord = $record->fresh();
            $employeeName = $employee->user?->name ?? "موظف #{$employee->id}";
            $monthDisplay = Carbon::parse($freshRecord->payroll_month)->format('Y-m');

            $methodName = match ($paymentMethod) {
                'bank_transfer' => 'تحويل بنكي',
                'cash' => 'نقداً',
                'cheque' => 'شيك',
                default => $paymentMethod
            };

            return [
                'success' => true,
                'message' => 'تم صرف مبلغ ('.number_format($amount, 2)." ر.س) للموظف ({$employeeName}) عن شهر ({$monthDisplay}) بنجاح بسند رقم #{$payment->payment_number} عبر ({$methodName}). المتبقي: ".number_format($freshRecord->remaining_amount, 2).' ر.س',
                'record_name' => "صرف راتب: {$employeeName}",
                'details' => [
                    'المبلغ المصروف' => number_format($amount, 2).' ر.س',
                    'سند الصرف' => $payment->payment_number,
                    'تاريخ الصرف' => $paymentDate,
                    'طريقة الصرف' => $methodName,
                    'شهر الاستحقاق' => $monthDisplay,
                    'المتبقي للصرف' => number_format($freshRecord->remaining_amount, 2).' ر.س',
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء صرف الراتب: '.$e->getMessage(),
            ];
        }
    }

    protected function resolveEmployee(string $identifier, ?int $companyId = null): ?Employee
    {
        return EmployeeNameMatcher::resolveEmployee($identifier, $companyId);
    }
}
