<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Models\Company;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) {
            $company = Company::create([
                'company_number' => 'COMP-001',
                'trade_name' => 'مجموعة فواز القابضة',
            ]);
        }
        $companyId = $company->id;

        $types = [
            ['name' => 'إجازة سنوية', 'annual_balance' => 30, 'is_paid' => true],
            ['name' => 'إجازة مرضية', 'annual_balance' => 15, 'is_paid' => true],
            ['name' => 'إجازة طارئة', 'annual_balance' => 5, 'is_paid' => true],
            ['name' => 'إجازة بدون راتب', 'annual_balance' => 0, 'is_paid' => false],
            ['name' => 'إجازة زواج', 'annual_balance' => 5, 'is_paid' => true],
            ['name' => 'إجازة وفاة', 'annual_balance' => 5, 'is_paid' => true],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(
                ['company_id' => $companyId, 'name' => $type['name']],
                [
                    'annual_balance' => $type['annual_balance'],
                    'is_paid' => $type['is_paid'],
                    'requires_approval' => true,
                    'status' => 'active',
                ]
            );
        }
    }
}
