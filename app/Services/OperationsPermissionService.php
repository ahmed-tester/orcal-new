<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OperationsPermissionService
{
    /**
     * Get operational permissions and their Arabic display labels.
     *
     * @return array<string, string>
     */
    public static function getPermissions(): array
    {
        return [
            'escalate_service_transaction' => 'تصعيد معاملة الخدمة للإدارة',
            'resolve_service_escalation' => 'حل وفك تصعيد معاملات الخدمات',
            'manage_batch_deposits' => 'إجراء وإدارة الإيداعات البنكية المجمعة',
            'manage_suspense_amounts' => 'إدارة وربط المبالغ والحوالات المعلقة',
            'resolve_deficiency_task' => 'معالجة وإغلاق مهام النواقص الرقابية',
            'view_operational_dashboard' => 'استعراض لوحة المسار والرقابة التشغيلية',
            'record_service_receipt' => 'تسجيل سندات القبض المالية للخدمات',
            'confirm_service_receipt' => 'تأكيد وعكس سندات القبض المالية',
            'request_cash_handover' => 'طلب توريد نقد للخزنة',
            'confirm_cash_handover' => 'استلام وتأكيد توريد النقد في الخزنة',
            'record_service_disbursement' => 'تسجيل سداد رسوم الخدمات',
            'approve_self_paid_disbursement' => 'اعتماد السداد الشخصي للموظف',
            'pay_employee_reimbursement' => 'صرف تعويضات الموظفين',
            'reverse_financial_transaction' => 'عكس الحركات المالية المعتمدة',
        ];
    }

    /**
     * Sync and assign operations workflow permissions across system roles.
     *
     * @return array{created_permissions: array<int, string>, roles_updated: array<string, array<int, string>>}
     */
    public static function syncPermissions(): array
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = static::getPermissions();
        $createdPermissions = [];

        foreach ($permissions as $name => $label) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web']
            );
            $createdPermissions[] = $name;
        }

        $rolesUpdated = [];

        // Super Admin Role
        $superAdmin = Role::where('name', 'مدير النظام')->orWhere('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(array_keys($permissions));
            $rolesUpdated[$superAdmin->name] = array_keys($permissions);
        }

        // Operations Managers and Supervisors
        $opsRoles = Role::whereIn('name', ['مدير عمليات', 'مشرف عمليات'])->get();
        $opsPermissions = [
            'escalate_service_transaction',
            'resolve_service_escalation',
            'resolve_deficiency_task',
            'view_operational_dashboard',
            'manage_batch_deposits',
            'manage_suspense_amounts',
            'record_service_receipt',
            'confirm_service_receipt',
            'request_cash_handover',
            'confirm_cash_handover',
            'record_service_disbursement',
            'approve_self_paid_disbursement',
            'reverse_financial_transaction',
        ];

        foreach ($opsRoles as $opsRole) {
            $opsRole->givePermissionTo($opsPermissions);
            $rolesUpdated[$opsRole->name] = $opsPermissions;
        }

        // Finance & Accounting Roles
        $financeRoles = Role::whereIn('name', ['محاسب', 'محاسب مالي'])->get();
        $financePermissions = [
            'manage_batch_deposits',
            'manage_suspense_amounts',
            'view_operational_dashboard',
            'record_service_receipt',
            'confirm_service_receipt',
            'confirm_cash_handover',
            'approve_self_paid_disbursement',
            'pay_employee_reimbursement',
            'reverse_financial_transaction',
        ];

        foreach ($financeRoles as $finRole) {
            $finRole->givePermissionTo($financePermissions);
            $rolesUpdated[$finRole->name] = $financePermissions;
        }

        // Regular Employee Roles
        $empRoles = Role::whereIn('name', ['موظف'])->get();
        $empPermissions = [
            'escalate_service_transaction',
            'record_service_receipt',
            'request_cash_handover',
            'record_service_disbursement',
        ];

        foreach ($empRoles as $empRole) {
            $empRole->givePermissionTo($empPermissions);
            $rolesUpdated[$empRole->name] = $empPermissions;
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return [
            'created_permissions' => $createdPermissions,
            'roles_updated' => $rolesUpdated,
        ];
    }
}
