<?php

namespace Database\Seeders;

use App\Enums\RewardStatus;
use App\Models\Company;
use App\Models\DailyJournal;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceStageTwoSeeder extends Seeder
{
    public function run(): void
    {
        $hvacCompany = Company::where('trade_name', 'LIKE', '%تكييف%')->first() ?? Company::first();
        $realEstateCompany = Company::where('trade_name', 'LIKE', '%عقارية%')->first() ?? Company::first();
        $contractingCompany = Company::where('trade_name', 'LIKE', '%مقاولات%')->first() ?? Company::first();
        $logisticsCompany = Company::where('trade_name', 'LIKE', '%لوجستية%')->first() ?? Company::first();

        $accountant = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%سالم%'))->first() ?? Employee::first();
        $noura = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%نورة%'))->first() ?? Employee::latest()->first();
        $foreignEmp = Employee::whereHas('detail', fn ($q) => $q->whereNotNull('iqama_number')->orWhere('national_id', 'LIKE', '2%'))->first() ?? Employee::latest()->first();
        $adminUser = User::first();

        // 1. قيود اليومية (PDF ص 11)
        $journals = [
            [
                'entry_number' => 'D-1101',
                'entry_date' => '2026-09-10',
                'company_id' => $contractingCompany?->id ?? 1,
                'purchases' => 14500.00,
                'sales' => 38000.00,
                'recorded_by' => $accountant?->id,
                'notes' => 'مبيعات ومشتريات تشطيبات مشروع فيلا الياسمين',
            ],
            [
                'entry_number' => 'D-1102',
                'entry_date' => '2026-09-11',
                'company_id' => $realEstateCompany?->id ?? 1,
                'purchases' => 3200.00,
                'sales' => 52000.00,
                'recorded_by' => $noura?->id,
                'notes' => 'تحصيلات عقود إيجارات ومصروفات صيانة دورية',
            ],
            [
                'entry_number' => 'D-1103',
                'entry_date' => '2026-09-12',
                'company_id' => $hvacCompany?->id ?? 1,
                'purchases' => 8400.00,
                'sales' => 24500.00,
                'recorded_by' => $accountant?->id,
                'notes' => 'عقود صيانة تكييف ومشتريات قطع غيار فلاتر',
            ],
            [
                'entry_number' => 'D-1104',
                'entry_date' => '2026-09-13',
                'company_id' => $logisticsCompany?->id ?? 1,
                'purchases' => 6100.00,
                'sales' => 19800.00,
                'recorded_by' => $noura?->id,
                'notes' => 'نقل وتوصيل شحنات ووقود أسطول النقل',
            ],
        ];

        foreach ($journals as $jData) {
            DailyJournal::firstOrCreate(
                ['entry_number' => $jData['entry_number']],
                $jData
            );
        }

        // 2. سجل المكافآت بالساعات والدقائق (PDF ص 18)
        $rewards = [
            [
                'reward_number' => 'B-0081',
                'reward_date' => '2026-09-10',
                'company_id' => $hvacCompany?->id ?? 1,
                'employee_id' => $foreignEmp?->id ?? 1,
                'hourly_rate' => 35.00,
                'hours_count' => 12,
                'minutes_count' => 30,
                'reason' => 'إنجاز أعمال صيانة تكييف طارئة خارج أوقات الدوام الرسمي',
                'location' => 'مجمع النخيل الطبي — فرع العليا',
                'status' => RewardStatus::PAID,
                'created_by' => $adminUser?->id,
                'notes' => 'تم الصرف الفعلي مع مسير رواتب سبتمبر',
            ],
            [
                'reward_number' => 'B-0082',
                'reward_date' => '2026-09-12',
                'company_id' => $contractingCompany?->id ?? 1,
                'employee_id' => $accountant?->id ?? 1,
                'hourly_rate' => 50.00,
                'hours_count' => 8,
                'minutes_count' => 45,
                'reason' => 'إعداد الميزانية الختامية وقوائم الضرائب والزكاة',
                'location' => 'المقر الرئيسي — الإدارة المالية',
                'status' => RewardStatus::APPROVED,
                'created_by' => $adminUser?->id,
                'notes' => 'معتمدة من المدير التنفيذي بانتظار الإيداع',
            ],
            [
                'reward_number' => 'B-0083',
                'reward_date' => '2026-09-14',
                'company_id' => $realEstateCompany?->id ?? 1,
                'employee_id' => $noura?->id ?? 1,
                'hourly_rate' => 45.00,
                'hours_count' => 15,
                'minutes_count' => 0,
                'reason' => 'إغلاق وتوقيع 5 عقود تجارية جديدة خلال أسبوع',
                'location' => 'فرع النرجس — إدارة التأجير',
                'status' => RewardStatus::APPROVED,
                'created_by' => $adminUser?->id,
                'notes' => 'مكافأة تميز في إبرام العقود السنوية',
            ],
        ];

        foreach ($rewards as $rData) {
            EmployeeReward::firstOrCreate(
                ['reward_number' => $rData['reward_number']],
                $rData
            );
        }
    }
}
