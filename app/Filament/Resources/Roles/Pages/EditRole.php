<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label(__('admin.delete')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissionNames = $this->record->permissions->pluck('name')->toArray();

        $groups = [
            'permissions_admin' => [
                'view_any_admin', 'create_admin', 'update_admin', 'delete_admin',
            ],
            'permissions_roles' => [
                'view_any_role', 'create_role', 'update_role', 'delete_role',
            ],
            'permissions_org' => [
                'view_any_company', 'create_company', 'update_company', 'delete_company',
                'view_any_branch', 'create_branch', 'update_branch', 'delete_branch',
                'view_any_department', 'create_department', 'update_department', 'delete_department',
                'view_any_job_title', 'create_job_title', 'update_job_title', 'delete_job_title',
                'view_any_owner', 'create_owner', 'update_owner', 'delete_owner',
            ],
            'permissions_employees' => [
                'view_any_saudi_employee', 'create_saudi_employee', 'update_saudi_employee', 'delete_saudi_employee',
                'view_any_foreign_employee', 'create_foreign_employee', 'update_foreign_employee', 'delete_foreign_employee',
            ],
            'permissions_attendance' => [
                'view_any_attendance', 'create_attendance', 'update_attendance', 'delete_attendance', 'check_attendance_absent',
                'view_any_attendance_device', 'create_attendance_device', 'update_attendance_device', 'delete_attendance_device',
            ],
            'permissions_leaves' => [
                'view_any_leave', 'create_leave', 'update_leave', 'delete_leave', 'change_leave_status',
                'view_any_leave_type', 'create_leave_type', 'update_leave_type', 'delete_leave_type',
            ],
            'permissions_crm' => [
                'view_any_customer', 'create_customer', 'update_customer', 'delete_customer',
                'view_any_service', 'create_service', 'update_service', 'delete_service',
            ],
            'permissions_billing' => [
                'view_any_invoice', 'create_invoice', 'update_invoice', 'delete_invoice',
                'view_any_invoice_type', 'create_invoice_type', 'update_invoice_type', 'delete_invoice_type',
                'view_any_offer', 'create_offer', 'update_offer', 'delete_offer',
            ],
            'permissions_tasks' => [
                'view_any_task', 'create_task', 'update_task', 'delete_task',
                'view_any_task_assignment', 'create_task_assignment', 'update_task_assignment', 'delete_task_assignment',
                'view_any_weekly_note', 'create_weekly_note', 'update_weekly_note', 'delete_weekly_note',
            ],
            'permissions_settings' => [
                'view_any_setting', 'create_setting', 'update_setting', 'delete_setting', 'reset_database',
            ],
            'permissions_finance' => [
                'view_any_payment_collection_item', 'create_payment_collection_item', 'update_payment_collection_item', 'delete_payment_collection_item',
                'view_any_pos_device', 'create_pos_device', 'update_pos_device', 'delete_pos_device',
                'view_any_treasury_transaction', 'create_treasury_transaction', 'update_treasury_transaction', 'delete_treasury_transaction',
                'view_any_daily_journal', 'create_daily_journal', 'update_daily_journal', 'delete_daily_journal',
                'view_any_employee_reward', 'create_employee_reward', 'update_employee_reward', 'delete_employee_reward',
                'view_any_payroll_record', 'create_payroll_record', 'update_payroll_record', 'delete_payroll_record',
            ],
            'permissions_installments' => [
                'view_any_installment', 'create_installment', 'update_installment', 'delete_installment',
                'view_any_installment_payment', 'create_installment_payment', 'update_installment_payment', 'delete_installment_payment',
                'view_any_installment_type', 'create_installment_type', 'update_installment_type', 'delete_installment_type',
            ],
            'permissions_faq' => [
                'view_any_faq', 'create_faq', 'update_faq', 'delete_faq',
            ],
            'permissions_tickets' => [
                'view_any_ticket', 'create_ticket', 'update_ticket', 'delete_ticket', 'manage_tickets',
            ],
            'permissions_violations' => [
                'view_any_violation', 'create_violation', 'update_violation', 'delete_violation',
                'view_any_violation_category', 'create_violation_category', 'update_violation_category', 'delete_violation_category',
                'view_any_violation_type', 'create_violation_type', 'update_violation_type', 'delete_violation_type',
                'view_any_violation_transaction', 'create_violation_transaction', 'update_violation_transaction', 'delete_violation_transaction',
                'view_any_violation_regulation', 'create_violation_regulation', 'update_violation_regulation', 'delete_violation_regulation',
            ],
            'permissions_funds' => [
                'view_any_loan_fund', 'create_loan_fund', 'update_loan_fund', 'delete_loan_fund',
                'view_any_loan_fund_member', 'create_loan_fund_member', 'update_loan_fund_member', 'delete_loan_fund_member',
                'view_any_loan_fund_loan', 'create_loan_fund_loan', 'update_loan_fund_loan', 'delete_loan_fund_loan',
                'view_any_loan_fund_loan_payment', 'create_loan_fund_loan_payment', 'update_loan_fund_loan_payment', 'delete_loan_fund_loan_payment',
                'view_any_rosca_fund', 'create_rosca_fund', 'update_rosca_fund', 'delete_rosca_fund',
                'view_any_rosca_fund_member', 'create_rosca_fund_member', 'update_rosca_fund_member', 'delete_rosca_fund_member',
                'view_any_rosca_fund_payment', 'create_rosca_fund_payment', 'update_rosca_fund_payment', 'delete_rosca_fund_payment',
            ],
            'permissions_reports_and_tools' => [
                'view_general_reports', 'view_notifications_report', 'manage_translations', 'manage_smart_import', 'view_loan_fund_policy', 'view_rosca_fund_policy',
            ],
        ];

        foreach ($groups as $field => $perms) {
            $data[$field] = array_values(array_intersect($permissionNames, $perms));
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $permissions = array_filter(array_merge(
            $this->data['permissions_admin'] ?? [],
            $this->data['permissions_roles'] ?? [],
            $this->data['permissions_org'] ?? [],
            $this->data['permissions_employees'] ?? [],
            $this->data['permissions_attendance'] ?? [],
            $this->data['permissions_leaves'] ?? [],
            $this->data['permissions_crm'] ?? [],
            $this->data['permissions_billing'] ?? [],
            $this->data['permissions_tasks'] ?? [],
            $this->data['permissions_settings'] ?? [],
            $this->data['permissions_finance'] ?? [],
            $this->data['permissions_installments'] ?? [],
            $this->data['permissions_faq'] ?? [],
            $this->data['permissions_tickets'] ?? [],
            $this->data['permissions_violations'] ?? [],
            $this->data['permissions_funds'] ?? [],
            $this->data['permissions_reports_and_tools'] ?? [],
        ));

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        $this->record->syncPermissions($permissions);
    }
}
