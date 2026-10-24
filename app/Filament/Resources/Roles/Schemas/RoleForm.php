<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    /**
     * Define permission groups aligned with the sidebar navigation.
     */
    public static function getPermissionGroups(): array
    {
        return [
            // 1. System Administration & Permissions
            'permissions_system' => [
                'label' => __('navigation.system_group'),
                'permissions' => [
                    'view_any_admin' => __('permissions.view_any_admin'),
                    'create_admin' => __('permissions.create_admin'),
                    'update_admin' => __('permissions.update_admin'),
                    'delete_admin' => __('permissions.delete_admin'),

                    'view_any_role' => __('permissions.view_any_role'),
                    'create_role' => __('permissions.create_role'),
                    'update_role' => __('permissions.update_role'),
                    'delete_role' => __('permissions.delete_role'),

                    'manage_translations' => __('permissions.manage_translations'),

                    'view_any_setting' => __('permissions.view_any_setting'),
                    'create_setting' => __('permissions.create_setting'),
                    'update_setting' => __('permissions.update_setting'),
                    'delete_setting' => __('permissions.delete_setting'),
                    'reset_database' => __('permissions.reset_database'),

                    'manage_smart_import' => __('permissions.manage_smart_import'),
                ],
            ],

            // 2. Organization & Structure
            'permissions_org' => [
                'label' => __('navigation.org_group'),
                'permissions' => [
                    'view_any_company' => __('permissions.view_any_company'),
                    'create_company' => __('permissions.create_company'),
                    'update_company' => __('permissions.update_company'),
                    'delete_company' => __('permissions.delete_company'),

                    'view_any_branch' => __('permissions.view_any_branch'),
                    'create_branch' => __('permissions.create_branch'),
                    'update_branch' => __('permissions.update_branch'),
                    'delete_branch' => __('permissions.delete_branch'),

                    'view_any_owner' => __('permissions.view_any_owner'),
                    'create_owner' => __('permissions.create_owner'),
                    'update_owner' => __('permissions.update_owner'),
                    'delete_owner' => __('permissions.delete_owner'),

                    'view_any_department' => __('permissions.view_any_department'),
                    'create_department' => __('permissions.create_department'),
                    'update_department' => __('permissions.update_department'),
                    'delete_department' => __('permissions.delete_department'),
                ],
            ],

            // 3. Human Resources
            'permissions_hr' => [
                'label' => __('navigation.hr_group'),
                'permissions' => [
                    'view_any_job_title' => __('permissions.view_any_job_title'),
                    'create_job_title' => __('permissions.create_job_title'),
                    'update_job_title' => __('permissions.update_job_title'),
                    'delete_job_title' => __('permissions.delete_job_title'),

                    'view_any_saudi_employee' => __('permissions.view_any_saudi_employee'),
                    'create_saudi_employee' => __('permissions.create_saudi_employee'),
                    'update_saudi_employee' => __('permissions.update_saudi_employee'),
                    'delete_saudi_employee' => __('permissions.delete_saudi_employee'),

                    'view_any_foreign_employee' => __('permissions.view_any_foreign_employee'),
                    'create_foreign_employee' => __('permissions.create_foreign_employee'),
                    'update_foreign_employee' => __('permissions.update_foreign_employee'),
                    'delete_foreign_employee' => __('permissions.delete_foreign_employee'),

                    'view_any_attendance' => __('permissions.view_any_attendance'),
                    'create_attendance' => __('permissions.create_attendance'),
                    'update_attendance' => __('permissions.update_attendance'),
                    'delete_attendance' => __('permissions.delete_attendance'),
                    'check_attendance_absent' => __('permissions.check_attendance_absent'),

                    'view_any_attendance_device' => __('permissions.view_any_attendance_device'),
                    'create_attendance_device' => __('permissions.create_attendance_device'),
                    'update_attendance_device' => __('permissions.update_attendance_device'),
                    'delete_attendance_device' => __('permissions.delete_attendance_device'),

                    'view_any_leave' => __('permissions.view_any_leave'),
                    'create_leave' => __('permissions.create_leave'),
                    'update_leave' => __('permissions.update_leave'),
                    'delete_leave' => __('permissions.delete_leave'),
                    'change_leave_status' => __('permissions.change_leave_status'),

                    'view_any_leave_type' => __('permissions.view_any_leave_type'),
                    'create_leave_type' => __('permissions.create_leave_type'),
                    'update_leave_type' => __('permissions.update_leave_type'),
                    'delete_leave_type' => __('permissions.delete_leave_type'),
                ],
            ],

            // 4. Accounting & Finance
            'permissions_finance' => [
                'label' => __('navigation.finance_group'),
                'permissions' => [
                    'view_any_treasury_transaction' => __('permissions.view_any_treasury_transaction'),
                    'create_treasury_transaction' => __('permissions.create_treasury_transaction'),
                    'update_treasury_transaction' => __('permissions.update_treasury_transaction'),
                    'delete_treasury_transaction' => __('permissions.delete_treasury_transaction'),

                    'view_any_payment_collection_item' => __('permissions.view_any_payment_collection_item'),
                    'create_payment_collection_item' => __('permissions.create_payment_collection_item'),
                    'update_payment_collection_item' => __('permissions.update_payment_collection_item'),
                    'delete_payment_collection_item' => __('permissions.delete_payment_collection_item'),

                    'view_any_invoice' => __('permissions.view_any_invoice'),
                    'create_invoice' => __('permissions.create_invoice'),
                    'update_invoice' => __('permissions.update_invoice'),
                    'delete_invoice' => __('permissions.delete_invoice'),

                    'view_any_payroll_record' => __('permissions.view_any_payroll_record'),
                    'create_payroll_record' => __('permissions.create_payroll_record'),
                    'update_payroll_record' => __('permissions.update_payroll_record'),
                    'delete_payroll_record' => __('permissions.delete_payroll_record'),

                    'view_any_employee_reward' => __('permissions.view_any_employee_reward'),
                    'create_employee_reward' => __('permissions.create_employee_reward'),
                    'update_employee_reward' => __('permissions.update_employee_reward'),
                    'delete_employee_reward' => __('permissions.delete_employee_reward'),

                    'view_any_invoice_type' => __('permissions.view_any_invoice_type'),
                    'create_invoice_type' => __('permissions.create_invoice_type'),
                    'update_invoice_type' => __('permissions.update_invoice_type'),
                    'delete_invoice_type' => __('permissions.delete_invoice_type'),

                    'view_any_daily_journal' => __('permissions.view_any_daily_journal'),
                    'create_daily_journal' => __('permissions.create_daily_journal'),
                    'update_daily_journal' => __('permissions.update_daily_journal'),
                    'delete_daily_journal' => __('permissions.delete_daily_journal'),

                    'view_any_pos_device' => __('permissions.view_any_pos_device'),
                    'create_pos_device' => __('permissions.create_pos_device'),
                    'update_pos_device' => __('permissions.update_pos_device'),
                    'delete_pos_device' => __('permissions.delete_pos_device'),
                ],
            ],

            // 5. Public Finance & Services
            'permissions_public_finance' => [
                'label' => __('navigation.public_finance_and_services'),
                'permissions' => [
                    'view_any_installment' => __('permissions.view_any_installment'),
                    'create_installment' => __('permissions.create_installment'),
                    'update_installment' => __('permissions.update_installment'),
                    'delete_installment' => __('permissions.delete_installment'),

                    'view_any_installment_payment' => __('permissions.view_any_installment_payment'),
                    'create_installment_payment' => __('permissions.create_installment_payment'),
                    'update_installment_payment' => __('permissions.update_installment_payment'),
                    'delete_installment_payment' => __('permissions.delete_installment_payment'),
                    'approve_installment_payment' => __('permissions.approve_installment_payment'),

                    'view_any_installment_type' => __('permissions.view_any_installment_type'),
                    'create_installment_type' => __('permissions.create_installment_type'),
                    'update_installment_type' => __('permissions.update_installment_type'),
                    'delete_installment_type' => __('permissions.delete_installment_type'),

                    'view_any_debt' => __('permissions.view_any_debt'),
                    'create_debt' => __('permissions.create_debt'),
                    'update_debt' => __('permissions.update_debt'),
                    'delete_debt' => __('permissions.delete_debt'),

                    'view_any_debt_collection' => __('permissions.view_any_debt_collection'),
                    'create_debt_collection' => __('permissions.create_debt_collection'),
                    'update_debt_collection' => __('permissions.update_debt_collection'),
                    'delete_debt_collection' => __('permissions.delete_debt_collection'),
                    'approve_debt_collection' => __('permissions.approve_debt_collection'),

                    'view_any_service_transaction' => __('permissions.view_any_service_transaction'),
                    'create_service_transaction' => __('permissions.create_service_transaction'),
                    'update_service_transaction' => __('permissions.update_service_transaction'),
                    'delete_service_transaction' => __('permissions.delete_service_transaction'),
                    'approve_service_transaction' => __('permissions.approve_service_transaction'),

                    'view_any_service' => __('permissions.view_any_service'),
                    'create_service' => __('permissions.create_service'),
                    'update_service' => __('permissions.update_service'),
                    'delete_service' => __('permissions.delete_service'),
                ],
            ],

            // 6. Task Management
            'permissions_tasks' => [
                'label' => __('navigation.tasks_group'),
                'permissions' => [
                    'view_any_supervision_shift' => __('permissions.view_any_supervision_shift'),
                    'create_supervision_shift' => __('permissions.create_supervision_shift'),
                    'update_supervision_shift' => __('permissions.update_supervision_shift'),
                    'delete_supervision_shift' => __('permissions.delete_supervision_shift'),

                    'view_any_task' => __('permissions.view_any_task'),
                    'create_task' => __('permissions.create_task'),
                    'update_task' => __('permissions.update_task'),
                    'delete_task' => __('permissions.delete_task'),

                    'view_any_task_assignment' => __('permissions.view_any_task_assignment'),
                    'create_task_assignment' => __('permissions.create_task_assignment'),
                    'update_task_assignment' => __('permissions.update_task_assignment'),
                    'delete_task_assignment' => __('permissions.delete_task_assignment'),

                    'view_any_weekly_note' => __('permissions.view_any_weekly_note'),
                    'create_weekly_note' => __('permissions.create_weekly_note'),
                    'update_weekly_note' => __('permissions.update_weekly_note'),
                    'delete_weekly_note' => __('permissions.delete_weekly_note'),
                ],
            ],

            // 7. Contracts, Sales & Billing
            'permissions_contracts' => [
                'label' => __('navigation.contracts_group'),
                'permissions' => [
                    'view_any_customer' => __('permissions.view_any_customer'),
                    'create_customer' => __('permissions.create_customer'),
                    'update_customer' => __('permissions.update_customer'),
                    'delete_customer' => __('permissions.delete_customer'),

                    'view_any_offer' => __('permissions.view_any_offer'),
                    'create_offer' => __('permissions.create_offer'),
                    'update_offer' => __('permissions.update_offer'),
                    'delete_offer' => __('permissions.delete_offer'),

                    'view_any_contract_voucher' => __('permissions.view_any_contract_voucher'),
                    'create_contract_voucher' => __('permissions.create_contract_voucher'),
                    'update_contract_voucher' => __('permissions.update_contract_voucher'),
                    'delete_contract_voucher' => __('permissions.delete_contract_voucher'),
                    'approve_contract_voucher' => __('permissions.approve_contract_voucher'),

                    'manage_contract_categories' => __('permissions.manage_contract_categories'),
                ],
            ],

            // 8. Violations
            'permissions_violations' => [
                'label' => __('navigation.violations_group'),
                'permissions' => [
                    'view_any_violation_category' => __('permissions.view_any_violation_category'),
                    'create_violation_category' => __('permissions.create_violation_category'),
                    'update_violation_category' => __('permissions.update_violation_category'),
                    'delete_violation_category' => __('permissions.delete_violation_category'),

                    'view_any_violation_type' => __('permissions.view_any_violation_type'),
                    'create_violation_type' => __('permissions.create_violation_type'),
                    'update_violation_type' => __('permissions.update_violation_type'),
                    'delete_violation_type' => __('permissions.delete_violation_type'),

                    'view_any_violation' => __('permissions.view_any_violation'),
                    'create_violation' => __('permissions.create_violation'),
                    'update_violation' => __('permissions.update_violation'),
                    'delete_violation' => __('permissions.delete_violation'),

                    'view_any_violation_transaction' => __('permissions.view_any_violation_transaction'),
                    'create_violation_transaction' => __('permissions.create_violation_transaction'),
                    'update_violation_transaction' => __('permissions.update_violation_transaction'),
                    'delete_violation_transaction' => __('permissions.delete_violation_transaction'),

                    'view_any_violation_regulation' => __('permissions.view_any_violation_regulation'),
                    'create_violation_regulation' => __('permissions.create_violation_regulation'),
                    'update_violation_regulation' => __('permissions.update_violation_regulation'),
                    'delete_violation_regulation' => __('permissions.delete_violation_regulation'),
                ],
            ],

            // 9. Funds & Societies
            'permissions_funds' => [
                'label' => __('navigation.funds_group'),
                'permissions' => [
                    'view_any_loan_fund' => __('permissions.view_any_loan_fund'),
                    'create_loan_fund' => __('permissions.create_loan_fund'),
                    'update_loan_fund' => __('permissions.update_loan_fund'),
                    'delete_loan_fund' => __('permissions.delete_loan_fund'),

                    'view_any_loan_fund_member' => __('permissions.view_any_loan_fund_member'),
                    'create_loan_fund_member' => __('permissions.create_loan_fund_member'),
                    'update_loan_fund_member' => __('permissions.update_loan_fund_member'),
                    'delete_loan_fund_member' => __('permissions.delete_loan_fund_member'),

                    'view_any_loan_fund_loan' => __('permissions.view_any_loan_fund_loan'),
                    'create_loan_fund_loan' => __('permissions.create_loan_fund_loan'),
                    'update_loan_fund_loan' => __('permissions.update_loan_fund_loan'),
                    'delete_loan_fund_loan' => __('permissions.delete_loan_fund_loan'),

                    'view_any_loan_fund_loan_payment' => __('permissions.view_any_loan_fund_loan_payment'),
                    'create_loan_fund_loan_payment' => __('permissions.create_loan_fund_loan_payment'),
                    'update_loan_fund_loan_payment' => __('permissions.update_loan_fund_loan_payment'),
                    'delete_loan_fund_loan_payment' => __('permissions.delete_loan_fund_loan_payment'),

                    'view_any_loan_fund_member_payment' => __('permissions.view_any_loan_fund_member_payment'),
                    'create_loan_fund_member_payment' => __('permissions.create_loan_fund_member_payment'),
                    'update_loan_fund_member_payment' => __('permissions.update_loan_fund_member_payment'),
                    'delete_loan_fund_member_payment' => __('permissions.delete_loan_fund_member_payment'),

                    'view_any_rosca_fund' => __('permissions.view_any_rosca_fund'),
                    'create_rosca_fund' => __('permissions.create_rosca_fund'),
                    'update_rosca_fund' => __('permissions.update_rosca_fund'),
                    'delete_rosca_fund' => __('permissions.delete_rosca_fund'),

                    'view_any_rosca_fund_member' => __('permissions.view_any_rosca_fund_member'),
                    'create_rosca_fund_member' => __('permissions.create_rosca_fund_member'),
                    'update_rosca_fund_member' => __('permissions.update_rosca_fund_member'),
                    'delete_rosca_fund_member' => __('permissions.delete_rosca_fund_member'),

                    'view_any_rosca_fund_payment' => __('permissions.view_any_rosca_fund_payment'),
                    'create_rosca_fund_payment' => __('permissions.create_rosca_fund_payment'),
                    'update_rosca_fund_payment' => __('permissions.update_rosca_fund_payment'),
                    'delete_rosca_fund_payment' => __('permissions.delete_rosca_fund_payment'),

                    'view_loan_fund_policy' => __('permissions.view_loan_fund_policy'),
                    'view_rosca_fund_policy' => __('permissions.view_rosca_fund_policy'),
                ],
            ],

            // 10. Official Letters
            'permissions_letters' => [
                'label' => __('navigation.letters_group'),
                'permissions' => [
                    'view_any_official_letter' => __('permissions.view_any_official_letter'),
                    'create_official_letter' => __('permissions.create_official_letter'),
                    'update_official_letter' => __('permissions.update_official_letter'),
                    'delete_official_letter' => __('permissions.delete_official_letter'),
                ],
            ],

            // 11. Reports & Notifications
            'permissions_reports' => [
                'label' => __('navigation.reports_group'),
                'permissions' => [
                    'view_general_reports' => __('permissions.view_general_reports'),
                    'view_notifications_report' => __('permissions.view_notifications_report'),
                ],
            ],

            // 12. Frequently Asked Questions (FAQ)
            'permissions_faq' => [
                'label' => __('navigation.faq_group'),
                'permissions' => [
                    'view_any_faq' => __('permissions.view_any_faq'),
                    'create_faq' => __('permissions.create_faq'),
                    'update_faq' => __('permissions.update_faq'),
                    'delete_faq' => __('permissions.delete_faq'),
                ],
            ],
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        $permissionSections = [];

        foreach (static::getPermissionGroups() as $fieldKey => $group) {
            $permissionSections[] = Section::make($group['label'])
                ->collapsible()
                ->schema([
                    CheckboxList::make($fieldKey)
                        ->hiddenLabel()
                        ->options($group['permissions'])
                        ->bulkToggleable()
                        ->dehydrated(false)
                        ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                ]);
        }

        return $schema
            ->columns(1)
            ->components([
                // 1. Role Info
                Section::make(__('roles.role_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('roles.role_name'))
                            ->placeholder(__('roles.role_name_placeholder'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),

                // 2. Permissions Distribution
                Section::make(__('roles.permissions_distribution'))
                    ->schema($permissionSections),
            ]);
    }
}
