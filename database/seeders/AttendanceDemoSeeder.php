<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $demoEmployees = [
            [
                'name' => 'أ. فواز بن سعد القحطاني',
                'email' => 'fawaz.q@fawazholding.sa',
                'phone' => '0551122334',
                'number' => 'EMP-1023',
                'check_in' => '07:55:00',
                'check_out' => '17:10:00',
                'late_hours' => 0.00,
                'overtime_hours' => 0.00,
                'status' => 'present',
            ],
            [
                'name' => 'م. خالد بن فواز العتيبي',
                'email' => 'khaled.otaibi@fawazholding.sa',
                'phone' => '0559988776',
                'number' => 'EMP-1024',
                'check_in' => '08:05:00',
                'check_out' => '17:30:00',
                'late_hours' => 0.25,
                'overtime_hours' => 1.50,
                'status' => 'present',
            ],
            [
                'name' => 'أ. سالم بن محمد الدوسري',
                'email' => 'salem.d@fawazholding.sa',
                'phone' => '0553344556',
                'number' => 'EMP-1025',
                'check_in' => '08:40:00',
                'check_out' => '17:00:00',
                'late_hours' => 1.00,
                'overtime_hours' => 0.00,
                'status' => 'late',
            ],
            [
                'name' => 'أ. نورة بنت عبدالله الشمري',
                'email' => 'noura.sh@fawazholding.sa',
                'phone' => '0557788990',
                'number' => 'EMP-1026',
                'check_in' => '07:45:00',
                'check_out' => '18:15:00',
                'late_hours' => 0.00,
                'overtime_hours' => 2.00,
                'status' => 'present',
            ],
            [
                'name' => 'أ. يوسف بن علي الحربي',
                'email' => 'youssef.h@fawazholding.sa',
                'phone' => '0556677889',
                'number' => 'EMP-1027',
                'check_in' => null,
                'check_out' => null,
                'late_hours' => 0.00,
                'overtime_hours' => 0.00,
                'status' => 'absent',
            ],
        ];

        foreach ($demoEmployees as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                    'user_type' => UserType::SAUDI_EMPLOYEE,
                    'status' => 'active',
                ]
            );

            $employee = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_number' => $data['number'],
                    'basic_salary' => 8500,
                    'status' => 'active',
                ]
            );

            // تسجيل أيام إضافية للموظف لدعم البحث عن جميع أيام حضور الموظف
            $dates = [
                '2026-09-13' => ['in' => $data['check_in'], 'out' => $data['check_out'], 'late' => $data['late_hours'], 'ot' => $data['overtime_hours'], 'status' => $data['status']],
                '2026-09-12' => ['in' => '07:58:00', 'out' => '17:05:00', 'late' => 0.00, 'ot' => 0.10, 'status' => 'present'],
                '2026-09-11' => ['in' => '08:15:00', 'out' => '17:30:00', 'late' => 0.25, 'ot' => 0.50, 'status' => 'present'],
                '2026-09-10' => ['in' => '08:00:00', 'out' => '17:00:00', 'late' => 0.00, 'ot' => 0.00, 'status' => 'present'],
                '2026-09-09' => ['in' => '07:50:00', 'out' => '18:00:00', 'late' => 0.00, 'ot' => 1.00, 'status' => 'present'],
            ];

            foreach ($dates as $day => $info) {
                Attendance::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $day,
                    ],
                    [
                        'check_in' => $info['in'],
                        'check_out' => $info['out'],
                        'late_hours' => $info['late'],
                        'overtime_hours' => $info['ot'],
                        'total_hours' => ($info['in'] && $info['out']) ? 8.5 : 0,
                        'check_in_source' => 'fingerprint',
                        'check_out_source' => 'fingerprint',
                        'status' => $info['status'],
                        'notes' => null,
                    ]
                );
            }
        }
    }
}
