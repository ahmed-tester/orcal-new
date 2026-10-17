<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyAttachment;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companiesData = [
            [
                'company_number' => 'COMP-001',
                'trade_name' => 'مجموعة فواز القابضة للاستثمار',
                'trade_name_en' => 'Fawaz Holding Group For Investment',
                'address' => 'الرياض - طريق الملك فهد - برج فواز للأعمال',
                'address_en' => 'Riyadh - King Fahd Road - Fawaz Business Tower - KSA',
                'postal_code' => '12211',
                'phone' => '0112345678',
                'email' => 'info@fawaz-holding.com',
                'commercial_registration_number' => '1010987654',
                'commercial_registration_issue_date' => '2020-01-15',
                'commercial_registration_expiry' => '2030-01-14',
                'municipality_license_number' => 'MUN-2023-9821',
                'municipality_license_issue_date' => '2023-03-01',
                'municipality_license_expiry' => '2027-02-28',
                'civil_defense_license_number' => 'CD-884920',
                'civil_defense_license_issue_date' => '2023-05-10',
                'civil_defense_license_expiry' => '2027-05-09',
                'interior_ministry_number' => '7001928374',
                'labor_ministry_number' => '1-849302',
                'social_insurance_number' => '392817402',
                'zakat_number' => '300192837400003',
                'chamber_of_commerce_number' => 'CH-192834',
                'status' => 'active',
            ],
            [
                'company_number' => 'COMP-002',
                'trade_name' => 'مؤسسة فواز للخدمات اللوجستية والنقل',
                'trade_name_en' => 'Fawaz Logistics & Transport Est.',
                'address' => 'جدة - حي الأندلس - طريق الأمير سلطان',
                'address_en' => 'Jeddah - Al-Andalus Dist. - Prince Sultan Rd - KSA',
                'postal_code' => '23326',
                'phone' => '0129876543',
                'email' => 'logistics@fawaz-holding.com',
                'commercial_registration_number' => '4030876543',
                'commercial_registration_issue_date' => '2021-06-01',
                'commercial_registration_expiry' => '2031-05-31',
                'municipality_license_number' => 'MUN-2024-1102',
                'municipality_license_issue_date' => '2024-01-10',
                'municipality_license_expiry' => '2028-01-09',
                'civil_defense_license_number' => 'CD-991203',
                'civil_defense_license_issue_date' => '2024-02-15',
                'civil_defense_license_expiry' => '2028-02-14',
                'interior_ministry_number' => '7009876543',
                'labor_ministry_number' => '2-998877',
                'social_insurance_number' => '482910394',
                'zakat_number' => '300987654300003',
                'chamber_of_commerce_number' => 'CH-554433',
                'status' => 'active',
            ],
            [
                'company_number' => 'COMP-003',
                'trade_name' => 'شركة فواز لحلول التقنية والاتصالات',
                'trade_name_en' => 'Fawaz Tech & Communications Solutions Co.',
                'address' => 'الدمام - حي الشاطئ - مجمع الأعمال الذكية',
                'address_en' => 'Dammam - Al-Shati Dist. - Smart Business Complex - KSA',
                'postal_code' => '32414',
                'phone' => '0138765432',
                'email' => 'tech@fawaz-holding.com',
                'commercial_registration_number' => '2050765432',
                'commercial_registration_issue_date' => '2022-09-01',
                'commercial_registration_expiry' => '2032-08-31',
                'municipality_license_number' => 'MUN-2024-5541',
                'municipality_license_issue_date' => '2024-04-01',
                'municipality_license_expiry' => '2028-03-31',
                'civil_defense_license_number' => 'CD-772910',
                'civil_defense_license_issue_date' => '2024-04-10',
                'civil_defense_license_expiry' => '2028-04-09',
                'interior_ministry_number' => '7007654321',
                'labor_ministry_number' => '3-445566',
                'social_insurance_number' => '592019483',
                'zakat_number' => '300765432100003',
                'chamber_of_commerce_number' => 'CH-887766',
                'status' => 'active',
            ],
        ];

        foreach ($companiesData as $item) {
            $company = Company::updateOrCreate(['commercial_registration_number' => $item['commercial_registration_number']], $item);

            // Attachments
            $attachments = [
                ['type' => 'commercial_registration', 'title' => 'شهادة السجل التجاري المعتمد', 'doc_number' => $item['commercial_registration_number'], 'start' => $item['commercial_registration_issue_date'], 'end' => $item['commercial_registration_expiry']],
                ['type' => 'municipality_license', 'title' => 'رخصة البلدية للمقر الرئيسي', 'doc_number' => $item['municipality_license_number'], 'start' => $item['municipality_license_issue_date'], 'end' => $item['municipality_license_expiry']],
                ['type' => 'civil_defense', 'title' => 'تصريح السلامة من الدفاع المدني', 'doc_number' => $item['civil_defense_license_number'], 'start' => $item['civil_defense_license_issue_date'], 'end' => $item['civil_defense_license_expiry']],
                ['type' => 'zakat_certificate', 'title' => 'شهادة الزكاة والدخل والضريبة', 'doc_number' => $item['zakat_number'], 'start' => '2025-01-01', 'end' => '2027-04-30'],
                ['type' => 'saudization_certificate', 'title' => 'شهادة التوطين والسعودة (نطاقات)', 'doc_number' => 'SAUDI-2026-88', 'start' => '2026-01-01', 'end' => '2027-01-01'],
            ];

            foreach ($attachments as $att) {
                CompanyAttachment::firstOrCreate(
                    ['company_id' => $company->id, 'document_number' => $att['doc_number']],
                    [
                        'attachment_type' => $att['type'],
                        'title' => $att['title'],
                        'start_date' => $att['start'],
                        'end_date' => $att['end'],
                        'file_path' => 'attachments/sample.pdf',
                        'notes' => 'وثيقة رسمية سارية المفعول مسجلة بالنظام',
                    ]
                );
            }
        }

        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        // Branches
        $branchesData = [
            ['name' => 'فرع الرياض الرئيسي', 'code' => 'BR-RUH-01', 'phone' => '0112345001', 'email' => 'ruh@fawaz-holding.com', 'address' => 'الرياض - طريق الملك فهد'],
            ['name' => 'فرع جدة الإقليمي', 'code' => 'BR-JED-02', 'phone' => '0129876002', 'email' => 'jed@fawaz-holding.com', 'address' => 'جدة - طريق الأمير سلطان'],
            ['name' => 'فرع الدمام الشرقي', 'code' => 'BR-DMM-03', 'phone' => '0138765003', 'email' => 'dmm@fawaz-holding.com', 'address' => 'الدمام - حي الشاطئ'],
            ['name' => 'فرع المدينة المنورة', 'code' => 'BR-MED-04', 'phone' => '0148201004', 'email' => 'med@fawaz-holding.com', 'address' => 'المدينة المنورة - طريق الملك عبدالله'],
        ];

        foreach ($branchesData as $b) {
            Branch::updateOrCreate(
                ['company_id' => $mainCompany->id, 'branch_code' => $b['code']],
                [
                    'name' => $b['name'],
                    'phone' => $b['phone'],
                    'email' => $b['email'],
                    'address' => $b['address'],
                    'attendance_settings' => [
                        'work_start' => '08:00',
                        'work_end' => '17:00',
                        'grace_period_minutes' => 15,
                    ],
                    'status' => 'active',
                ]
            );
        }

        // Departments
        $deptsData = [
            ['name' => 'الإدارة العامة والقيادة', 'code' => 'DEP-EXEC-01'],
            ['name' => 'إدارة الموارد البشرية وشؤون الموظفين', 'code' => 'DEP-HR-02'],
            ['name' => 'إدارة الشؤون المالية والمحاسبة', 'code' => 'DEP-FIN-03'],
            ['name' => 'إدارة العمليات واللوجستيات', 'code' => 'DEP-OPS-04'],
            ['name' => 'إدارة تقنية المعلومات والتحول الرقمي', 'code' => 'DEP-IT-05'],
            ['name' => 'إدارة المبيعات والتسويق', 'code' => 'DEP-SALES-06'],
        ];

        $departments = [];
        foreach ($deptsData as $d) {
            $departments[] = Department::updateOrCreate(
                ['company_id' => $mainCompany->id, 'department_code' => $d['code']],
                ['name' => $d['name'], 'status' => 'active']
            );
        }

        // Job Titles
        $jobsData = [
            ['name' => 'الرئيس التنفيذي (CEO)', 'code' => 'JOB-CEO-01', 'dept_idx' => 0, 'order' => 1],
            ['name' => 'مدير الموارد البشرية', 'code' => 'JOB-HR-01', 'dept_idx' => 1, 'order' => 2],
            ['name' => 'أخصائي توظيف وموارد بشرية', 'code' => 'JOB-HR-02', 'dept_idx' => 1, 'order' => 3],
            ['name' => 'محاسب عام أول', 'code' => 'JOB-FIN-01', 'dept_idx' => 2, 'order' => 2],
            ['name' => 'محاسب تكاليف ومطالبات', 'code' => 'JOB-FIN-02', 'dept_idx' => 2, 'order' => 3],
            ['name' => 'مدير العمليات والتشغيل', 'code' => 'JOB-OPS-01', 'dept_idx' => 3, 'order' => 2],
            ['name' => 'مشرف عمليات ونقل', 'code' => 'JOB-OPS-02', 'dept_idx' => 3, 'order' => 3],
            ['name' => 'مهندس برمجيات ونظم', 'code' => 'JOB-IT-01', 'dept_idx' => 4, 'order' => 2],
            ['name' => 'مسؤول دعم فني وشبكات', 'code' => 'JOB-IT-02', 'dept_idx' => 4, 'order' => 3],
            ['name' => 'مسؤول مبيعات وتسويق', 'code' => 'JOB-SALES-01', 'dept_idx' => 5, 'order' => 3],
        ];

        foreach ($jobsData as $j) {
            $dept = $departments[$j['dept_idx']] ?? $departments[0];
            JobTitle::updateOrCreate(
                ['company_id' => $mainCompany->id, 'job_code' => $j['code']],
                [
                    'department_id' => $dept->id,
                    'name' => $j['name'],
                    'sort_order' => $j['order'],
                    'job_description' => 'المسؤوليات والواجبات المهنية المعتمدة للوظيفة',
                    'status' => 'active',
                ]
            );
        }

        // Owners
        $ownersData = [
            ['name' => 'الشيخ فواز بن عبدالعزيز آل مقرن', 'email' => 'fawaz.owner@fawaz-holding.com', 'phone' => '0505551122', 'national_id' => '1012345678', 'pct' => 70.00],
            ['name' => 'تركي بن فواز آل مقرن', 'email' => 'turki.owner@fawaz-holding.com', 'phone' => '0505553344', 'national_id' => '1023456789', 'pct' => 20.00],
            ['name' => 'سارة بنت فواز آل مقرن', 'email' => 'sara.owner@fawaz-holding.com', 'phone' => '0505555566', 'national_id' => '1034567890', 'pct' => 10.00],
        ];

        foreach ($ownersData as $o) {
            $user = User::updateOrCreate(
                ['email' => $o['email']],
                [
                    'name' => $o['name'],
                    'phone' => $o['phone'],
                    'password' => Hash::make('password'),
                    'user_type' => \App\Enums\UserType::OWNER,
                    'status' => 'active',
                ]
            );

            Owner::updateOrCreate(
                ['user_id' => $user->id, 'company_id' => $mainCompany->id],
                [
                    'nationality' => 'saudi',
                    'national_id' => $o['national_id'],
                    'birth_date' => '1980-05-12',
                    'employment_date' => '2020-01-01',
                    'ownership_percentage' => $o['pct'],
                    'status' => 'active',
                ]
            );
        }
    }
}
