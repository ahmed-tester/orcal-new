<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // All system permission groups
        $permissionGroups = [
            'المديرين وحسابات النظام' => [
                'view_any_admin' => 'استعراض قائمة المديرين',
                'create_admin' => 'إضافة مدير جديد',
                'update_admin' => 'تعديل بيانات المدير',
                'delete_admin' => 'حذف المدير',
            ],
            'الأدوار والصلاحيات' => [
                'view_any_role' => 'استعراض قائمة الأدوار',
                'create_role' => 'إضافة دور جديد',
                'update_role' => 'تعديل الدور والصلاحيات',
                'delete_role' => 'حذف الدور',
            ],
            'الشركات والهيكل التنظيمي' => [
                'view_any_company' => 'استعراض الشركات',
                'create_company' => 'إضافة شركة',
                'update_company' => 'تعديل بيانات الشركة',
                'delete_company' => 'حذف الشركة',
                'view_any_branch' => 'استعراض الفروع',
                'create_branch' => 'إضافة فرع',
                'update_branch' => 'تعديل بيانات الفرع',
                'delete_branch' => 'حذف الفرع',
                'view_any_department' => 'استعراض الأقسام',
                'create_department' => 'إضافة قسم',
                'update_department' => 'تعديل القسم',
                'delete_department' => 'حذف القسم',
                'view_any_job_title' => 'استعراض المسميات الوظيفية',
                'create_job_title' => 'إضافة مسمى وظيفي',
                'update_job_title' => 'تعديل مسمى وظيفي',
                'delete_job_title' => 'حذف مسمى وظيفي',
                'view_any_owner' => 'استعراض الملاك والشركاء',
                'create_owner' => 'إضافة مالك / شريك',
                'update_owner' => 'تعديل بيانات المالك',
                'delete_owner' => 'حذف المالك',
            ],
            'شؤون الموظفين' => [
                'view_any_saudi_employee' => 'استعراض الموظفين السعوديين',
                'create_saudi_employee' => 'إضافة موظف سعودي',
                'update_saudi_employee' => 'تعديل موظف سعودي',
                'delete_saudi_employee' => 'حذف موظف سعودي',
                'view_any_foreign_employee' => 'استعراض الموظفين المقيمين',
                'create_foreign_employee' => 'إضافة موظف مقيم',
                'update_foreign_employee' => 'تعديل موظف مقيم',
                'delete_foreign_employee' => 'حذف موظف مقيم',
            ],
            'الحضور والانصراف' => [
                'view_any_attendance' => 'استعراض سجلات الحضور والانصراف',
                'create_attendance' => 'تسجيل حضور / انصراف يدوي',
                'update_attendance' => 'تعديل سجل حضور',
                'delete_attendance' => 'حذف سجل حضور',
                'check_attendance_absent' => 'تشغيل فحص الغياب التلقائي',
            ],
            'الإجازات وأنواعها' => [
                'view_any_leave' => 'استعراض طلبات الإجازات',
                'create_leave' => 'تقديم طلب إجازة',
                'update_leave' => 'تعديل طلب إجازة',
                'delete_leave' => 'حذف طلب إجازة',
                'change_leave_status' => 'تغيير واعتماد حالة الإجازة',
                'view_any_leave_type' => 'استعراض أنواع الإجازات',
                'create_leave_type' => 'إضافة نوع إجازة',
                'update_leave_type' => 'تعديل نوع إجازة',
                'delete_leave_type' => 'حذف نوع إجازة',
            ],
            'العملاء والخدمات' => [
                'view_any_customer' => 'استعراض العملاء',
                'create_customer' => 'إضافة عميل جديد',
                'update_customer' => 'تعديل بيانات العميل',
                'delete_customer' => 'حذف العميل',
                'view_any_service' => 'استعراض الخدمات والمنتجات',
                'create_service' => 'إضافة خدمة',
                'update_service' => 'تعديل خدمة',
                'delete_service' => 'حذف خدمة',
            ],
            'الفواتير وعروض الأسعار' => [
                'view_any_invoice' => 'استعراض الفواتير',
                'create_invoice' => 'إنشاء فاتورة جديدة',
                'update_invoice' => 'تعديل فاتورة',
                'delete_invoice' => 'حذف فاتورة',
                'view_any_invoice_type' => 'استعراض أنواع الفواتير',
                'create_invoice_type' => 'إضافة نوع فاتورة',
                'update_invoice_type' => 'تعديل نوع فاتورة',
                'delete_invoice_type' => 'حذف نوع فاتورة',
                'view_any_offer' => 'استعراض عروض الأسعار والعقود',
                'create_offer' => 'إنشاء عرض سعر أو عقد',
                'update_offer' => 'تعديل عرض سعر أو عقد',
                'delete_offer' => 'حذف عرض سعر أو عقد',
                'view_any_contract_voucher' => 'استعراض الفواتير والمستخلصات',
                'create_contract_voucher' => 'إنشاء سند أو مستخلص أو طلب دفعة',
                'update_contract_voucher' => 'تعديل الفواتير والمستخلصات',
                'delete_contract_voucher' => 'حذف / إلغاء سند أو مستخلص',
                'approve_contract_voucher' => 'اعتماد السندات وترحيلها للخزينة',
                'manage_contract_categories' => 'إدارة تصنيفات العقود وعروض الأسعار',
            ],
            'المهام والملاحظات الأسبوعية' => [
                'view_any_task' => 'استعراض المهام العامة',
                'create_task' => 'إضافة مهمة عامة',
                'update_task' => 'تعديل مهمة عامة',
                'delete_task' => 'حذف مهمة عامة',
                'view_any_task_assignment' => 'استعراض تكليفات المهام',
                'create_task_assignment' => 'تكليف موظف بمهمة',
                'update_task_assignment' => 'تعديل تكليف مهمة',
                'delete_task_assignment' => 'حذف تكليف مهمة',
                'view_any_weekly_note' => 'استعراض الملاحظات الأسبوعية',
                'create_weekly_note' => 'إضافة ملاحظة أسبوعية',
                'update_weekly_note' => 'تعديل ملاحظة أسبوعية',
                'delete_weekly_note' => 'حذف ملاحظة أسبوعية',
            ],
            'إعدادات النظام العامة' => [
                'view_any_setting' => 'استعراض إعدادات النظام',
                'create_setting' => 'إضافة إعداد للنظام',
                'update_setting' => 'تعديل إعدادات النظام',
                'delete_setting' => 'حذف إعداد من النظام',
            ],
            'الأسئلة الشائعة' => [
                'view_any_faq' => 'استعراض الأسئلة الشائعة',
                'create_faq'   => 'إضافة سؤال شائع جديد',
                'update_faq'   => 'تعديل سؤال شائع',
                'delete_faq'   => 'حذف سؤال شائع',
            ],
            'المالية والمحاسبة والخزينة' => [
                'view_any_payment_collection_item' => 'استعراض بنود الدفع والتحصيل',
                'create_payment_collection_item' => 'إضافة بند دفع وتحصيل',
                'update_payment_collection_item' => 'تعديل بند دفع وتحصيل',
                'delete_payment_collection_item' => 'حذف بند دفع وتحصيل',
                'view_any_pos_device' => 'استعراض أجهزة نقاط البيع',
                'create_pos_device' => 'إضافة جهاز نقطة بيع',
                'update_pos_device' => 'تعديل جهاز نقطة بيع',
                'delete_pos_device' => 'حذف جهاز نقطة بيع',
                'view_any_treasury_transaction' => 'استعراض حركات الخزينة والسندات',
                'create_treasury_transaction' => 'تسجيل حركة خزينة جديدة',
                'update_treasury_transaction' => 'تعديل حركة خزينة',
                'delete_treasury_transaction' => 'حذف حركة خزينة',
                'view_any_daily_journal' => 'استعراض قيود اليومية',
                'create_daily_journal' => 'تسجيل قيد يومي جديد',
                'update_daily_journal' => 'تعديل قيد يومي',
                'delete_daily_journal' => 'حذف قيد يومي',
                'view_any_employee_reward' => 'استعراض سجل مكافآت الموظفين',
                'create_employee_reward' => 'تسجيل مكافأة جديدة للموظف',
                'update_employee_reward' => 'تعديل مكافأة موظف',
                'delete_employee_reward' => 'حذف مكافأة موظف',
                'view_any_payroll_record' => 'استعراض مسيرات الرواتب',
                'create_payroll_record' => 'إعداد مسير رواتب',
                'update_payroll_record' => 'تعديل مسير رواتب',
                'delete_payroll_record' => 'حذف مسير رواتب',
                'view_any_installment' => 'استعراض سجل الأقساط والسلف',
                'create_installment' => 'تسجيل قسط / سلفة جديدة',
                'update_installment' => 'تعديل قسط / سلفة',
                'delete_installment' => 'حذف قسط / سلفة',
                'view_any_installment_payment' => 'استعراض سندات تسديد الأقساط',
                'create_installment_payment' => 'تسجيل سند تسديد قسط',
                'update_installment_payment' => 'تعديل سند تسديد قسط',
                'delete_installment_payment' => 'حذف سند تسديد قسط',
                'view_any_installment_type' => 'استعراض أنواع الأقساط',
                'create_installment_type' => 'إضافة نوع قسط',
                'update_installment_type' => 'تعديل نوع قسط',
                'delete_installment_type' => 'حذف نوع قسط',
            ],
            'تذاكر الدعم الفني والمساعدة' => [
                'view_any_ticket' => 'استعراض تذاكر الدعم',
                'create_ticket' => 'إنشاء تذكرة دعم',
                'update_ticket' => 'تعديل والرد على تذاكر الدعم',
                'delete_ticket' => 'حذف تذكرة دعم',
                'manage_tickets' => 'إدارة وتعيين تذاكر الدعم',
            ],
            'المخالفات واللوائح' => [
                'view_any_violation' => 'استعراض سجل المخالفات',
                'create_violation' => 'تسجيل مخالفة جديدة',
                'update_violation' => 'تعديل بيانات المخالفة',
                'delete_violation' => 'حذف مخالفة',
                'view_any_violation_category' => 'استعراض أقسام وتصنيفات المخالفات',
                'create_violation_category' => 'إضافة قسم مخالفات',
                'update_violation_category' => 'تعديل قسم مخالفات',
                'delete_violation_category' => 'حذف قسم مخالفات',
                'view_any_violation_type' => 'استعراض أنواع ومسميات المخالفات',
                'create_violation_type' => 'إضافة نوع مخالفة',
                'update_violation_type' => 'تعديل نوع مخالفة',
                'delete_violation_type' => 'حذف نوع مخالفة',
                'view_any_violation_transaction' => 'استعراض صندوق تحصيل المخالفات',
                'create_violation_transaction' => 'تسجيل سند تحصيل مخالفة',
                'update_violation_transaction' => 'تعديل سند تحصيل مخالفة',
                'delete_violation_transaction' => 'حذف سند تحصيل مخالفة',
                'view_any_violation_regulation' => 'استعراض لوائح وأنظمة المخالفات',
                'create_violation_regulation' => 'إضافة مادة للائحة المخالفات',
                'update_violation_regulation' => 'تعديل مادة بلائحة المخالفات',
                'delete_violation_regulation' => 'حذف مادة من لائحة المخالفات',
            ],
            'صناديق التكافل والادخار والجمعيات' => [
                'view_any_loan_fund' => 'استعراض صناديق سلفني',
                'create_loan_fund' => 'إنشاء صندوق سلفني',
                'update_loan_fund' => 'تعديل صندوق سلفني',
                'delete_loan_fund' => 'حذف صندوق سلفني',
                'view_any_loan_fund_member' => 'استعراض أعضاء صندوق سلفني',
                'create_loan_fund_member' => 'إضافة عضو لصندوق سلفني',
                'update_loan_fund_member' => 'تعديل بيانات عضو سلفني',
                'delete_loan_fund_member' => 'حذف عضو من سلفني',
                'view_any_loan_fund_loan' => 'استعراض صرف سلف سلفني',
                'create_loan_fund_loan' => 'صرف سلفة جديدة',
                'update_loan_fund_loan' => 'تعديل بيانات صرف سلفة',
                'delete_loan_fund_loan' => 'إلغاء / حذف سلفة',
                'view_any_loan_fund_loan_payment' => 'استعراض سندات سداد سلفني',
                'create_loan_fund_loan_payment' => 'تسجيل سند سداد سلفة',
                'update_loan_fund_loan_payment' => 'تعديل سند سداد سلفة',
                'delete_loan_fund_loan_payment' => 'حذف سند سداد سلفة',
                'view_any_rosca_fund' => 'استعراض صناديق الجمعية',
                'create_rosca_fund' => 'إنشاء صندوق جمعية',
                'update_rosca_fund' => 'تعديل صندوق جمعية',
                'delete_rosca_fund' => 'حذف صندوق جمعية',
                'view_any_rosca_fund_member' => 'استعراض أعضاء الجمعية والأدوار',
                'create_rosca_fund_member' => 'إضافة عضو للجمعية',
                'update_rosca_fund_member' => 'تعديل دور وبيانات عضو الجمعية',
                'delete_rosca_fund_member' => 'حذف عضو من الجمعية',
                'view_any_rosca_fund_payment' => 'استعراض كشف سداد أقساط الجمعية',
                'create_rosca_fund_payment' => 'تسجيل سداد قسط جمعية',
                'update_rosca_fund_payment' => 'تعديل قسط جمعية',
                'delete_rosca_fund_payment' => 'حذف قسط جمعية',
            ],
            'التقارير وأدوات النظام' => [
                'view_general_reports' => 'استعراض الشاشة المركزية للتقارير',
                'view_notifications_report' => 'استعراض تقرير التنبيهات والوثائق',
                'manage_translations' => 'الوصول لإدارة الترجمات',
                'manage_smart_import' => 'الوصول للاستيراد الذكي (AI)',
                'view_loan_fund_policy' => 'استعراض وتعديل لائحة صندوق سلفني',
                'view_rosca_fund_policy' => 'استعراض وتعديل لائحة صندوق الجمعية',
            ],
        ];

        $allPermissionNames = [];

        foreach ($permissionGroups as $group => $permissions) {
            foreach ($permissions as $name => $arabicLabel) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => 'web']
                );
                $allPermissionNames[] = $name;
            }
        }

        // 1. Super Admin & General Manager Roles
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions($allPermissionNames);

        $generalManagerRole = Role::firstOrCreate(['name' => 'مدير عام', 'guard_name' => 'web']);
        $generalManagerRole->syncPermissions($allPermissionNames);

        // 2. HR Manager Role (مدير موارد بشرية)
        $hrManagerRole = Role::firstOrCreate(['name' => 'مدير موارد بشرية', 'guard_name' => 'web']);
        $hrManagerRole->syncPermissions([
            'view_any_company', 'view_any_branch', 'view_any_department', 'view_any_job_title',
            'view_any_saudi_employee', 'create_saudi_employee', 'update_saudi_employee', 'delete_saudi_employee',
            'view_any_foreign_employee', 'create_foreign_employee', 'update_foreign_employee', 'delete_foreign_employee',
            'view_any_attendance', 'create_attendance', 'update_attendance', 'delete_attendance', 'check_attendance_absent',
            'view_any_leave', 'create_leave', 'update_leave', 'delete_leave', 'change_leave_status',
            'view_any_leave_type', 'create_leave_type', 'update_leave_type',
            'view_any_employee_reward', 'create_employee_reward', 'update_employee_reward',
            'view_any_payroll_record', 'create_payroll_record', 'update_payroll_record',
            'view_any_installment', 'create_installment', 'update_installment',
        ]);

        // 3. Attendance Officer Role (مسؤول حضور وانصراف)
        $attendanceOfficerRole = Role::firstOrCreate(['name' => 'مسؤول حضور وانصراف', 'guard_name' => 'web']);
        $attendanceOfficerRole->syncPermissions([
            'view_any_attendance', 'create_attendance', 'update_attendance', 'check_attendance_absent',
            'view_any_leave', 'create_leave', 'update_leave', 'change_leave_status', 'view_any_leave_type',
            'view_any_saudi_employee', 'view_any_foreign_employee',
        ]);

        // 4. Branch Manager Role (مدير فرع)
        $branchManagerRole = Role::firstOrCreate(['name' => 'مدير فرع', 'guard_name' => 'web']);
        $branchManagerRole->syncPermissions([
            'view_any_branch', 'view_any_department', 'view_any_job_title',
            'view_any_saudi_employee', 'create_saudi_employee', 'update_saudi_employee',
            'view_any_foreign_employee', 'create_foreign_employee', 'update_foreign_employee',
            'view_any_attendance', 'create_attendance', 'update_attendance', 'check_attendance_absent',
            'view_any_leave', 'create_leave', 'update_leave', 'change_leave_status', 'view_any_leave_type',
            'view_any_customer', 'view_any_service', 'view_any_invoice',
            'view_any_offer', 'create_offer', 'update_offer',
            'view_any_task', 'create_task', 'update_task',
            'view_any_weekly_note', 'create_weekly_note', 'update_weekly_note',
        ]);

        // 5. Accountant / Financial Manager Role (مدير مالي ومحاسب)
        $accountantRole = Role::firstOrCreate(['name' => 'مدير مالي ومحاسب', 'guard_name' => 'web']);
        $accountantRole->syncPermissions([
            'view_any_payment_collection_item',
            'create_payment_collection_item',
            'update_payment_collection_item',
            'view_any_pos_device',
            'create_pos_device',
            'update_pos_device',
            'view_any_treasury_transaction',
            'create_treasury_transaction',
            'update_treasury_transaction',
            'view_any_invoice',
            'create_invoice',
            'update_invoice',
            'view_any_offer',
            'create_offer',
            'update_offer',
            'view_any_daily_journal',
            'create_daily_journal',
            'update_daily_journal',
            'view_any_employee_reward',
            'create_employee_reward',
            'update_employee_reward',
            'view_any_payroll_record',
            'create_payroll_record',
            'update_payroll_record',
            'view_any_installment',
            'create_installment',
            'update_installment',
            'view_any_installment_payment',
            'create_installment_payment',
            'update_installment_payment',
            'view_any_installment_type',
            'create_installment_type',
            'update_installment_type',
        ]);

        // Also alias 'محاسب مالي'
        $finAccountantRole = Role::firstOrCreate(['name' => 'محاسب مالي', 'guard_name' => 'web']);
        $finAccountantRole->syncPermissions($accountantRole->permissions);

        // دور مسؤول المقاولات والعقود والفوترة
        $contractingRole = Role::firstOrCreate(['name' => 'مسؤول المقاولات والعقود', 'guard_name' => 'web']);
        $contractingRole->syncPermissions([
            'view_any_customer', 'create_customer', 'update_customer',
            'view_any_offer', 'create_offer', 'update_offer',
            'view_any_contract_voucher', 'create_contract_voucher', 'update_contract_voucher', 'approve_contract_voucher',
            'manage_contract_categories',
        ]);

        // 6. Operations Manager Role (مدير عمليات / مشرف عمليات)
        $operationsRole = Role::firstOrCreate(['name' => 'مدير عمليات', 'guard_name' => 'web']);
        $operationsRole->syncPermissions([
            'view_any_admin',
            'create_admin',
            'update_admin',
            'view_any_role',
            'view_any_company',
            'view_any_branch',
            'view_any_department',
            'view_any_saudi_employee',
            'view_any_foreign_employee',
            'view_any_attendance',
            'view_any_leave',
            'view_any_task',
            'view_any_task_assignment',
            'view_any_weekly_note',
        ]);

        $opsSupervisorRole = Role::firstOrCreate(['name' => 'مشرف عمليات', 'guard_name' => 'web']);
        $opsSupervisorRole->syncPermissions($operationsRole->permissions);

        // 7. Regular Employee Role (موظف)
        $employeeRole = Role::firstOrCreate(['name' => 'موظف', 'guard_name' => 'web']);
        $employeeRole->syncPermissions([
            'view_any_leave', 'create_leave',
            'view_any_task',
            'view_any_faq',
        ]);

        // ── FAQ permissions for other roles ────────────────────────────────────
        // HR Manager: full FAQ access (they maintain the knowledge base)
        $hrManagerRole->givePermissionTo([
            'view_any_faq', 'create_faq', 'update_faq', 'delete_faq',
        ]);

        // Branch Manager: can view & edit FAQs, not delete
        $branchManagerRole->givePermissionTo([
            'view_any_faq', 'create_faq', 'update_faq',
        ]);

        // Operations Manager / Supervisor: view only
        $operationsRole->givePermissionTo(['view_any_faq']);
        $opsSupervisorRole->givePermissionTo(['view_any_faq']);

        // Financial roles: view only
        $accountantRole->givePermissionTo(['view_any_faq']);
        $finAccountantRole->givePermissionTo(['view_any_faq']);

        // Attendance Officer: view only
        $attendanceOfficerRole->givePermissionTo(['view_any_faq']);

        // Branch Manager: already given above

        // 8. Assign Super Admin role to default admin user
        $adminUser = User::where('email', 'admin@admin.com')->first();
        if ($adminUser) {
            $adminUser->assignRole($superAdminRole);
        }
    }
}
