<?php

namespace Database\Seeders;

use App\Enums\InstallmentCategory;
use App\Enums\InstallmentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\InstallmentSchedule;
use App\Models\InstallmentType;
use App\Models\PayrollPayment;
use App\Models\PayrollRecord;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceStageThreeSeeder extends Seeder
{
    public function run(): void
    {
        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        $employees = Employee::where('company_id', $mainCompany->id)->get();
        if ($employees->isEmpty()) {
            return;
        }

        $adminUser = User::where('user_type', \App\Enums\UserType::SUPER_ADMIN)->first() ?? User::first();
        $treasuryIncoming = TreasuryTransaction::where('transaction_type', 'incoming')->first();
        $treasuryOutgoing = TreasuryTransaction::where('transaction_type', 'outgoing')->first();

        // 1. أنواع الأقساط والسلف (PDF ص 19, 21)
        $typesData = [
            ['name' => 'قسط سيارة', 'category' => InstallmentCategory::ADVANCES],
            ['name' => 'سلفة شخصية', 'category' => InstallmentCategory::ADVANCES],
            ['name' => 'قرض شخصي', 'category' => InstallmentCategory::DEBTS],
            ['name' => 'مديونية مشتريات', 'category' => InstallmentCategory::DEBTS],
            ['name' => 'قسط جهاز لابتوب', 'category' => InstallmentCategory::OTHERS],
            ['name' => 'قسط تدريب وتأهيل', 'category' => InstallmentCategory::OTHERS],
        ];

        $installmentTypes = [];
        foreach ($typesData as $td) {
            $installmentTypes[] = InstallmentType::firstOrCreate(
                ['category' => $td['category']->value, 'name' => $td['name']],
                ['company_id' => null, 'status' => 'active']
            );
        }

        // 2. سجل الأقساط والسلف (PDF ص 19, 21, 22)
        $installmentsData = [
            [
                'installment_number' => 'INS-2026-001',
                'emp_idx'            => 0,
                'type_idx'           => 0, // قسط سيارة
                'total_amount'       => 24000.00,
                'down_payment'       => 4000.00,
                'months_count'       => 10,
                'paid_amount'        => 4000.00,
                'description'        => 'قسط سيارة هيونداي — تمويل داخلي يخصم 2,000 ر.س شهرياً',
                'status'             => InstallmentStatus::OPEN,
            ],
            [
                'installment_number' => 'INS-2026-002',
                'emp_idx'            => 1,
                'type_idx'           => 1, // سلفة شخصية
                'total_amount'       => 6000.00,
                'down_payment'       => 0.00,
                'months_count'       => 3,
                'paid_amount'        => 2000.00,
                'description'        => 'سلفة زواج طارئة تخصم على 3 دفعات متساوية',
                'status'             => InstallmentStatus::OPEN,
            ],
            [
                'installment_number' => 'INS-2026-003',
                'emp_idx'            => 2,
                'type_idx'           => 4, // قسط لابتوب
                'total_amount'       => 4500.00,
                'down_payment'       => 500.00,
                'months_count'       => 4,
                'paid_amount'        => 4000.00,
                'description'        => 'قسط جهاز ماك بوك للعمل البرمجي',
                'status'             => InstallmentStatus::COMPLETED,
            ],
        ];

        foreach ($installmentsData as $instData) {
            $emp = $employees[$instData['emp_idx']] ?? $employees->first();
            $type = $installmentTypes[$instData['type_idx']] ?? $installmentTypes[0];

            $installment = Installment::updateOrCreate(
                ['installment_number' => $instData['installment_number']],
                [
                    'installment_date'    => '2026-07-01',
                    'company_id'          => $mainCompany->id,
                    'employee_id'         => $emp->id,
                    'installment_type_id' => $type->id,
                    'total_amount'        => $instData['total_amount'],
                    'down_payment'        => $instData['down_payment'],
                    'monthly_amount'      => round(($instData['total_amount'] - $instData['down_payment']) / $instData['months_count'], 2),
                    'paid_amount'         => $instData['paid_amount'],
                    'months_count'        => $instData['months_count'],
                    'start_due_date'      => '2026-08-01',
                    'final_due_date'      => '2027-05-01',
                    'notify_employee_id'  => $employees->first()?->id,
                    'notification_notes'  => 'تنبيه استحقاق القسط الشهري للموظف',
                    'description'         => $instData['description'],
                    'executed_by'         => $employees->first()?->id,
                    'status'              => $instData['status'],
                ]
            );

            // 3. إنشاء جدول الأقساط الشهرية المجدولة
            for ($m = 1; $m <= $instData['months_count']; $m++) {
                $dueDate = now()->startOfYear()->addMonths($m + 6)->format('Y-m-d');
                $monthlyAmount = round(($instData['total_amount'] - $instData['down_payment']) / $instData['months_count'], 2);
                $isPaid = $m <= 2;

                InstallmentSchedule::updateOrCreate(
                    ['installment_id' => $installment->id, 'installment_order' => $m],
                    [
                        'company_id'       => $mainCompany->id,
                        'employee_id'      => $emp->id,
                        'due_date'         => $dueDate,
                        'amount'           => $monthlyAmount,
                        'paid_amount'      => $isPaid ? $monthlyAmount : 0,
                        'remaining_amount' => $isPaid ? 0 : $monthlyAmount,
                        'status'           => $isPaid ? 'paid' : 'pending',
                    ]
                );
            }

            // 4. سند سداد قسط (PDF ص 20)
            InstallmentPayment::updateOrCreate(
                ['voucher_number' => 'P-2026-' . str_pad($installment->id, 4, '0', STR_PAD_LEFT)],
                [
                    'company_id'             => $mainCompany->id,
                    'employee_id'            => $emp->id,
                    'installment_id'         => $installment->id,
                    'payment_date'           => '2026-09-01',
                    'amount'                 => 2000.00,
                    'payment_source'         => 'cash',
                    'treasury_transaction_id'=> $treasuryIncoming?->id,
                    'description'            => 'سداد قسط شهر سبتمبر 2026 نقداً بالخزينة',
                    'executed_by'            => $employees->first()?->id,
                ]
            );
        }

        // 5. مسيرات الرواتب (PDF ص 16, 17, 31)
        foreach ($employees->take(4) as $idx => $emp) {
            $basic = (float)$emp->basic_salary > 0 ? (float)$emp->basic_salary : 8000.00;
            $overtime = $idx === 0 ? 1500.00 : 0.00;
            $rewards = $idx === 1 ? 800.00 : 0.00;
            $advance = $idx === 0 ? 2000.00 : 0.00;
            $absence = $idx === 2 ? 300.00 : 0.00;

            $record = PayrollRecord::updateOrCreate(
                ['company_id' => $mainCompany->id, 'employee_id' => $emp->id, 'payroll_month' => '2026-09-01'],
                [
                    'record_number'     => 'P-202609-' . str_pad($emp->id, 4, '0', STR_PAD_LEFT),
                    'record_date'       => '2026-09-25',
                    'basic_salary'      => $basic,
                    'overtime_amount'   => $overtime,
                    'rewards_amount'    => $rewards,
                    'additional_amount' => $overtime + $rewards,
                    'advance_deduction' => $advance,
                    'absence_deduction' => $absence,
                    'deduction_amount'  => $advance + $absence,
                    'net_amount'        => $basic + $overtime + $rewards - ($advance + $absence),
                    'paid_amount'       => $idx === 3 ? 0.00 : $basic + $overtime + $rewards - ($advance + $absence),
                    'remaining_amount'  => $idx === 3 ? $basic + $overtime + $rewards - ($advance + $absence) : 0.00,
                    'payment_status'    => $idx === 3 ? \App\Enums\PaymentStatus::UNPAID : \App\Enums\PaymentStatus::PAID,
                    'created_by'        => $adminUser?->id,
                    'notes'             => 'مسير رواتب شهر سبتمبر 2026 معتمد',
                ]
            );

            // 6. دفعة صرف الراتب (ص 16)
            if ($record->paid_amount > 0) {
                PayrollPayment::updateOrCreate(
                    ['payment_number' => 'PAY-202609-' . str_pad($record->id, 4, '0', STR_PAD_LEFT)],
                    [
                        'payroll_record_id'      => $record->id,
                        'company_id'             => $mainCompany->id,
                        'employee_id'            => $emp->id,
                        'payment_date'           => '2026-09-28',
                        'amount'                 => $record->paid_amount,
                        'payment_method'         => 'payroll',
                        'treasury_transaction_id'=> $treasuryOutgoing?->id,
                        'executed_by'            => $employees->first()?->id,
                        'notes'                  => 'إيداع بنكي لحساب الموظف المعتمد',
                    ]
                );
            }
        }
    }
}
