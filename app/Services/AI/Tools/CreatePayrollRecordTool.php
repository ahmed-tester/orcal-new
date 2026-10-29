<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;
use Carbon\Carbon;

class CreatePayrollRecordTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_payroll_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إعداد أو تعديل وتحديث مسير راتب شهري لموظف مع حساب البدلات والخصومات وصافي الراتب تلقائياً. إذا كان المسير موجوداً يتم تعديله وتحديث بياناته، وإذا لم يكن موجوداً يتم إنشاؤه.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية أو الرقم الوظيفي',
                    ],
                    'payroll_month' => [
                        'type' => 'STRING',
                        'description' => 'شهر المسير بصيغة YYYY-MM أو YYYY-MM-01 (مثال: 2026-10)',
                    ],
                    'basic_salary' => [
                        'type' => 'NUMBER',
                        'description' => 'الراتب الأساسي بالريال السعودي',
                    ],
                    'overtime_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'بدل العمل الإضافي إن وجد',
                    ],
                    'rewards_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المكافآت الإضافية إن وجدت',
                    ],
                    'advance_deduction' => [
                        'type' => 'NUMBER',
                        'description' => 'خصم السلف والأقساط إن وجد',
                    ],
                    'absence_deduction' => [
                        'type' => 'NUMBER',
                        'description' => 'خصم الغياب والتأخير إن وجد',
                    ],
                    'paid_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد أو المصروف فعلياً (الافتراضي 0)',
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات المسير',
                    ],
                ],
                'required' => ['employee_identifier', 'payroll_month', 'basic_salary'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $monthStr = trim($arguments['payroll_month'] ?? date('Y-m'));
            $basic = (float) ($arguments['basic_salary'] ?? 0);
            $overtime = (float) ($arguments['overtime_amount'] ?? 0);
            $rewards = (float) ($arguments['rewards_amount'] ?? 0);
            $advance = (float) ($arguments['advance_deduction'] ?? 0);
            $absence = (float) ($arguments['absence_deduction'] ?? 0);
            $paid = (float) ($arguments['paid_amount'] ?? 0);
            $notes = trim($arguments['notes'] ?? '');

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب لمسير الراتب.'];
            }

            if ($basic <= 0) {
                return ['success' => false, 'message' => 'الراتب الأساسي يجب أن يكون أكبر من الصفر.'];
            }

            $monthDate = Carbon::parse($monthStr)->startOfMonth()->toDateString();
            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $employee = $this->resolveEmployee($identifier, $companyId);
            if (! $employee) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على الموظف ({$identifier}) في النظام لإدراج المسير.",
                ];
            }

            // فحص وجود مسير سابق لنفس الموظف ونفس الشهر لتعديله أو إنشائه
            $existing = PayrollRecord::where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->whereDate('payroll_month', $monthDate)
                ->first();

            if ($existing) {
                $existing->basic_salary = $basic;
                $existing->overtime_amount = $overtime;
                $existing->rewards_amount = $rewards;
                $existing->advance_deduction = $advance;
                $existing->absence_deduction = $absence;
                if ($paid > 0) {
                    $existing->paid_amount = $paid;
                }
                if (! empty($notes)) {
                    $existing->notes = $notes;
                }
                $existing->save();

                return [
                    'success' => true,
                    'message' => "تم تعديل وتحديث مسير شهر ({$monthStr}) للموظف ({$employee->user?->name}) بصافي جديد ".number_format($existing->net_amount, 2).' ر.س',
                    'record_name' => $employee->user?->name ?? "مسير #{$existing->id}",
                    'details' => [
                        'العملية' => 'تعديل مسير قائم',
                        'basic_salary' => number_format($basic, 2).' ر.س',
                        'net_amount' => number_format($existing->net_amount, 2).' ر.س',
                        'payroll_month' => $monthStr,
                    ],
                ];
            }

            $record = PayrollRecord::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'payroll_month' => $monthDate,
                'record_date' => date('Y-m-d'),
                'basic_salary' => $basic,
                'overtime_amount' => $overtime,
                'rewards_amount' => $rewards,
                'advance_deduction' => $advance,
                'absence_deduction' => $absence,
                'paid_amount' => $paid,
                'notes' => $notes ?: 'استيراد ذكي — مسير رواتب',
            ]);

            return [
                'success' => true,
                'message' => "تم إنشاء مسير راتب جديد لشهر ({$monthStr}) للموظف ({$employee->user?->name}) بصافي ".number_format($record->net_amount, 2).' ر.س',
                'record_name' => $employee->user?->name ?? "مسير #{$record->id}",
                'details' => [
                    'العملية' => 'إنشاء مسير جديد',
                    'basic_salary' => number_format($basic, 2).' ر.س',
                    'net_amount' => number_format($record->net_amount, 2).' ر.س',
                    'payroll_month' => $monthStr,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ مسير الراتب: '.$e->getMessage(),
            ];
        }
    }

    protected function resolveEmployee(string $identifier, ?int $companyId = null): ?Employee
    {
        return EmployeeNameMatcher::resolveEmployee($identifier, $companyId);
    }
}
