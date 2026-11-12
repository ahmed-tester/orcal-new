<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class OperationsPermissionService
{
    /**
     * Get operational permissions and their translated display labels.
     *
     * @return array<string, string>
     */
    public static function getPermissions(): array
    {
        return [
            'escalate_service_transaction' => __('permissions.escalate_service_transaction'),
            'resolve_service_escalation' => __('permissions.resolve_service_escalation'),
            'manage_batch_deposits' => __('permissions.manage_batch_deposits'),
            'manage_suspense_amounts' => __('permissions.manage_suspense_amounts'),
            'resolve_deficiency_task' => __('permissions.resolve_deficiency_task'),
            'view_operational_dashboard' => __('permissions.view_operational_dashboard'),
            'record_service_receipt' => __('permissions.record_service_receipt'),
            'confirm_service_receipt' => __('permissions.confirm_service_receipt'),
            'request_cash_handover' => __('permissions.request_cash_handover'),
            'confirm_cash_handover' => __('permissions.confirm_cash_handover'),
            'record_service_disbursement' => __('permissions.record_service_disbursement'),
            'approve_self_paid_disbursement' => __('permissions.approve_self_paid_disbursement'),
            'pay_employee_reimbursement' => __('permissions.pay_employee_reimbursement'),
            'reverse_financial_transaction' => __('permissions.reverse_financial_transaction'),
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

        // Super Admin & General Manager Roles
        $superAdmins = Role::whereIn('name', ['مدير النظام', 'super_admin', 'Super Admin', 'مدير عام'])->get();
        foreach ($superAdmins as $superAdmin) {
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
