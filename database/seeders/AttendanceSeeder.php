<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        $mainBranch = Branch::where('company_id', $mainCompany->id)->first();
        if ($mainBranch) {
            AttendanceDevice::firstOrCreate(
                ['company_id' => $mainCompany->id, 'code' => 'DEV-RUH-MAIN'],
                [
                    'branch_id' => $mainBranch->id,
                    'name' => 'جهاز بصمة المدخل الرئيسي - فرع الرياض',
                    'ip_address' => '192.168.1.200',
                    'location' => 'البوابة الرئيسية - الدور الأرضي',
                    'device_type' => 'ZKTeco BioPro 300',
                    'status' => 'active',
                    'last_seen_at' => now(),
                ]
            );
        }

        $employees = Employee::where('company_id', $mainCompany->id)->get();
        foreach ($employees as $emp) {
            Attendance::updateOrCreate(
                ['employee_id' => $emp->id, 'date' => now()->format('Y-m-d')],
                [
                    'check_in' => '08:05:00',
                    'check_out' => '17:00:00',
                    'total_hours' => 8.0,
                    'status' => 'present',
                ]
            );
        }
    }
}
