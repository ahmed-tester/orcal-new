<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeaveSeeder extends Seeder
{
    public function run(): void
    {
        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        $adminUser = User::where('user_type', \App\Enums\UserType::SUPER_ADMIN)->first() ?? User::first();

        $typesData = [
            ['name' => 'إجازة سنوية', 'balance' => 30, 'paid' => true],
            ['name' => 'إجازة مرضية', 'balance' => 15, 'paid' => true],
            ['name' => 'إجازة طارئة', 'balance' => 5, 'paid' => true],
            ['name' => 'إجازة بدون راتب', 'balance' => 0, 'paid' => false],
            ['name' => 'إجازة زواج', 'balance' => 5, 'paid' => true],
            ['name' => 'إجازة وفاة', 'balance' => 5, 'paid' => true],
        ];

        foreach ($typesData as $t) {
            LeaveType::updateOrCreate(
                ['company_id' => $mainCompany->id, 'name' => $t['name']],
                [
                    'annual_balance' => $t['balance'],
                    'is_paid' => $t['paid'],
                    'requires_approval' => true,
                    'status' => 'active',
                ]
            );
        }

        $leaveTypes = LeaveType::where('company_id', $mainCompany->id)->get();
        $employees = Employee::where('company_id', $mainCompany->id)->get();

        if ($leaveTypes->isNotEmpty() && $employees->count() >= 2) {
            // Riyadh employee leave
            Leave::updateOrCreate(
                ['employee_id' => $employees[0]->id, 'start_date' => now()->startOfWeek()->format('Y-m-d')],
                [
                    'leave_type_id' => $leaveTypes[0]->id,
                    'end_date' => now()->startOfWeek()->addDays(2)->format('Y-m-d'),
                    'days' => 3,
                    'reason' => 'إجازة اعتيادية سنوية مجدولة',
                    'status' => 'pending',
                ]
            );

            // Jeddah employee leave
            if (isset($employees[1])) {
                Leave::updateOrCreate(
                    ['employee_id' => $employees[1]->id, 'start_date' => now()->addDays(5)->format('Y-m-d')],
                    [
                        'leave_type_id' => $leaveTypes[1]->id ?? $leaveTypes[0]->id,
                        'end_date' => now()->addDays(6)->format('Y-m-d'),
                        'days' => 2,
                        'reason' => 'مراجعة طبية ومرافقة',
                        'status' => 'approved',
                        'approved_by' => $adminUser?->id,
                        'approved_at' => now(),
                    ]
                );
            }
        }
    }
}
