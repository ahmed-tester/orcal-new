<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ComprehensiveDataSeeder extends Seeder
{
    /**
     * Seed the comprehensive core data by delegating to dedicated domain seeders.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class, // 1. الأدوار والصلاحيات
            UserSeeder::class,                // 2. مستخدمو الإدارة الرئيسيون
            CompanySeeder::class,             // 3. الشركات، الفروع، الأقسام، المسميات، والملاك
            EmployeeSeeder::class,            // 4. الموظفون السعوديون والأجانب ومسؤولو الحضور
            LeaveSeeder::class,               // 5. أنواع الإجازات وطلبات الإجازة
            AttendanceSeeder::class,          // 6. أجهزة البصمة وسجلات الحضور
            CustomerAndServiceSeeder::class,  // 7. دليل العملاء وقائمة الخدمات
            InvoiceSeeder::class,             // 8. أنواع الفواتير والفواتير وسطورها
        ]);
    }
}
