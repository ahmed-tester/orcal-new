<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        $branches = Branch::where('company_id', $mainCompany->id)->get();
        $departments = Department::where('company_id', $mainCompany->id)->get();
        $jobTitles = JobTitle::where('company_id', $mainCompany->id)->get();

        $employeeList = [
            // Saudi Employees
            [
                'name' => 'فهد بن خالد العتيبي',
                'email' => 'fahad.otb@fawaz-holding.com',
                'phone' => '0551234567',
                'is_saudi' => true,
                'emp_no' => 'EMP-1001',
                'dept_idx' => 1,
                'job_idx' => 1,
                'salary' => 18500,
                'national_id' => '1088776655',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1988-03-20',
                'city' => 'الرياض',
                'nationality' => 'saudi',
            ],
            [
                'name' => 'عبدالله بن محمد القحطاني',
                'email' => 'abdullah.qht@fawaz-holding.com',
                'phone' => '0552345678',
                'is_saudi' => true,
                'emp_no' => 'EMP-1002',
                'dept_idx' => 2,
                'job_idx' => 3,
                'salary' => 14000,
                'national_id' => '1099887766',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1992-07-14',
                'city' => 'الرياض',
                'nationality' => 'saudi',
            ],
            [
                'name' => 'ريم بنت صالح المطيري',
                'email' => 'reem.mtr@fawaz-holding.com',
                'phone' => '0553456789',
                'is_saudi' => true,
                'emp_no' => 'EMP-1003',
                'dept_idx' => 1,
                'job_idx' => 2,
                'salary' => 11500,
                'national_id' => '1077665544',
                'gender' => 'female',
                'marital' => 'single',
                'birth' => '1995-11-05',
                'city' => 'الرياض',
                'nationality' => 'saudi',
            ],
            [
                'name' => 'سلطان بن عبدالعزيز الدوسري',
                'email' => 'sultan.dos@fawaz-holding.com',
                'phone' => '0554567890',
                'is_saudi' => true,
                'emp_no' => 'EMP-1004',
                'dept_idx' => 3,
                'job_idx' => 6,
                'salary' => 12500,
                'national_id' => '1066554433',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1990-09-18',
                'city' => 'الرياض',
                'nationality' => 'saudi',
            ],
            [
                'name' => 'فيصل بن ناصر الشهري',
                'email' => 'faisal.shh@fawaz-holding.com',
                'phone' => '0555678901',
                'is_saudi' => true,
                'emp_no' => 'EMP-1005',
                'dept_idx' => 5,
                'job_idx' => 9,
                'salary' => 10000,
                'national_id' => '1055443322',
                'gender' => 'male',
                'marital' => 'single',
                'birth' => '1996-01-25',
                'city' => 'الرياض',
                'nationality' => 'saudi',
            ],

            // Foreign Employees (المقيمين)
            [
                'name' => 'أحمد عبدالهادي محمود',
                'email' => 'ahmed.hady@fawaz-holding.com',
                'phone' => '0561122334',
                'is_saudi' => false,
                'emp_no' => 'EMP-2001',
                'dept_idx' => 4,
                'job_idx' => 7,
                'salary' => 16000,
                'iqama_no' => '2345678901',
                'iqama_exp' => '2028-06-15',
                'passport_no' => 'A19827364',
                'passport_exp' => '2029-08-20',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1991-04-10',
                'city' => 'الرياض',
                'nationality' => 'egyptian',
            ],
            [
                'name' => 'محمد طارق إبراهيم',
                'email' => 'mohamed.tarek@fawaz-holding.com',
                'phone' => '0562233445',
                'is_saudi' => false,
                'emp_no' => 'EMP-2002',
                'dept_idx' => 2,
                'job_idx' => 4,
                'salary' => 9500,
                'iqama_no' => '2456789012',
                'iqama_exp' => '2027-10-30',
                'passport_no' => 'A28391029',
                'passport_exp' => '2028-11-15',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1993-08-22',
                'city' => 'الرياض',
                'nationality' => 'egyptian',
            ],
            [
                'name' => 'سليم راشد خان',
                'email' => 'salim.khan@fawaz-holding.com',
                'phone' => '0563344556',
                'is_saudi' => false,
                'emp_no' => 'EMP-2003',
                'dept_idx' => 4,
                'job_idx' => 8,
                'salary' => 8500,
                'iqama_no' => '2567890123',
                'iqama_exp' => '2027-12-25',
                'passport_no' => 'PK8829102',
                'passport_exp' => '2028-05-10',
                'gender' => 'male',
                'marital' => 'single',
                'birth' => '1994-12-02',
                'city' => 'الرياض',
                'nationality' => 'pakistani',
            ],
            [
                'name' => 'سريكانت راو',
                'email' => 'srikant.rao@fawaz-holding.com',
                'phone' => '0564455667',
                'is_saudi' => false,
                'emp_no' => 'EMP-2004',
                'dept_idx' => 4,
                'job_idx' => 7,
                'salary' => 13500,
                'iqama_no' => '2678901234',
                'iqama_exp' => '2028-02-18',
                'passport_no' => 'IND992019',
                'passport_exp' => '2029-01-30',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1989-06-16',
                'city' => 'الرياض',
                'nationality' => 'indian',
            ],
            [
                'name' => 'رضوان يوسف حسن',
                'email' => 'radwan.yousef@fawaz-holding.com',
                'phone' => '0565566778',
                'is_saudi' => false,
                'emp_no' => 'EMP-2005',
                'dept_idx' => 3,
                'job_idx' => 6,
                'salary' => 7800,
                'iqama_no' => '2789012345',
                'iqama_exp' => '2027-08-14',
                'passport_no' => 'SD7728190',
                'passport_exp' => '2028-09-05',
                'gender' => 'male',
                'marital' => 'married',
                'birth' => '1992-02-28',
                'city' => 'الرياض',
                'nationality' => 'sudanese',
            ],
        ];

        foreach ($employeeList as $idx => $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'user_type' => $data['is_saudi'] ? \App\Enums\UserType::SAUDI_EMPLOYEE : \App\Enums\UserType::FOREIGN_EMPLOYEE,
                    'status' => 'active',
                ]
            );

            $branch = $branches[$idx % count($branches)] ?? $branches->first();
            $dept = $departments[$data['dept_idx']] ?? $departments->first();
            $job = $jobTitles[$data['job_idx']] ?? $jobTitles->first();

            $employee = Employee::updateOrCreate(
                ['employee_number' => $data['emp_no']],
                [
                    'user_id' => $user->id,
                    'company_id' => $mainCompany->id,
                    'branch_id' => $branch?->id,
                    'department_id' => $dept?->id,
                    'job_title_id' => $job?->id,
                    'employment_type' => 'full_time',
                    'hire_date' => '2023-01-01',
                    'basic_salary' => $data['salary'],
                    'bank_iban' => 'SA0380000' . str_pad($user->id, 15, '0', STR_PAD_LEFT),
                    'daily_work_hours' => 8.0,
                    'weekly_work_hours' => 48.0,
                    'contract_duration' => 'سنتان',
                    'contract_end_date' => '2027-01-01',
                    'status' => 'active',
                ]
            );

            EmployeeDetail::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'nationality' => $data['nationality'],
                    'national_id' => $data['national_id'] ?? null,
                    'iqama_number' => $data['iqama_no'] ?? null,
                    'iqama_expiry' => $data['iqama_exp'] ?? null,
                    'passport_number' => $data['passport_no'] ?? null,
                    'passport_expiry' => $data['passport_exp'] ?? null,
                    'insurance_expiry' => '2027-12-31',
                    'work_permit_number' => $data['is_saudi'] ? null : 'WP-' . $employee->id . '981',
                    'work_permit_expiry' => $data['is_saudi'] ? null : '2027-11-30',
                    'sponsor_name' => $data['is_saudi'] ? null : $mainCompany->trade_name,
                    'birth_date' => $data['birth'],
                    'gender' => $data['gender'],
                    'marital_status' => $data['marital'],
                    'city' => $data['city'],
                    'address' => $data['city'] . ' - المملكة العربية السعودية',
                ]
            );
        }

        // Attendance Officers
        $hrDept = $departments[1] ?? $departments->first();
        $officerJob = $jobTitles->first();

        if (isset($branches[0])) {
            $riyadhUser = User::updateOrCreate(
                ['email' => 'attendance.riyadh@fawaz-holding.com'],
                [
                    'name' => 'خالد الدوسري (مسؤول حضور فرع الرياض)',
                    'phone' => '0504445566',
                    'password' => Hash::make('password'),
                    'user_type' => \App\Enums\UserType::SUPERVISOR,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $riyadhUser->assignRole('مسؤول حضور وانصراف');

            $riyadhEmployee = Employee::updateOrCreate(
                ['employee_number' => 'EMP-OFFICER-01'],
                [
                    'user_id' => $riyadhUser->id,
                    'company_id' => $mainCompany->id,
                    'branch_id' => $branches[0]->id,
                    'department_id' => $hrDept?->id,
                    'job_title_id' => $officerJob?->id,
                    'employment_type' => 'full_time',
                    'hire_date' => '2023-01-01',
                    'basic_salary' => 9000,
                    'bank_iban' => 'SA0380000' . str_pad((string)$riyadhUser->id, 15, '0', STR_PAD_LEFT),
                    'daily_work_hours' => 8.0,
                    'weekly_work_hours' => 48.0,
                    'status' => 'active',
                ]
            );

            EmployeeDetail::updateOrCreate(
                ['employee_id' => $riyadhEmployee->id],
                [
                    'nationality' => 'saudi',
                    'national_id' => '1011223344',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'marital_status' => 'married',
                    'city' => 'الرياض',
                    'address' => 'الرياض - المملكة العربية السعودية',
                ]
            );
        }

        if (isset($branches[1])) {
            $jeddahUser = User::updateOrCreate(
                ['email' => 'attendance.jeddah@fawaz-holding.com'],
                [
                    'name' => 'ماجد الحربي (مسؤول حضور فرع جدة)',
                    'phone' => '0505556677',
                    'password' => Hash::make('password'),
                    'user_type' => \App\Enums\UserType::SUPERVISOR,
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $jeddahUser->assignRole('مسؤول حضور وانصراف');

            $jeddahEmployee = Employee::updateOrCreate(
                ['employee_number' => 'EMP-OFFICER-02'],
                [
                    'user_id' => $jeddahUser->id,
                    'company_id' => $mainCompany->id,
                    'branch_id' => $branches[1]->id,
                    'department_id' => $hrDept?->id,
                    'job_title_id' => $officerJob?->id,
                    'employment_type' => 'full_time',
                    'hire_date' => '2023-01-01',
                    'basic_salary' => 9000,
                    'bank_iban' => 'SA0380000' . str_pad((string)$jeddahUser->id, 15, '0', STR_PAD_LEFT),
                    'daily_work_hours' => 8.0,
                    'weekly_work_hours' => 48.0,
                    'status' => 'active',
                ]
            );

            EmployeeDetail::updateOrCreate(
                ['employee_id' => $jeddahEmployee->id],
                [
                    'nationality' => 'saudi',
                    'national_id' => '1022334455',
                    'birth_date' => '1991-05-15',
                    'gender' => 'male',
                    'marital_status' => 'married',
                    'city' => 'جدة',
                    'address' => 'جدة - المملكة العربية السعودية',
                ]
            );
        }
    }
}
