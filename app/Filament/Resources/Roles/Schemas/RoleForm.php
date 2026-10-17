<?php

namespace App\Filament\Resources\Roles\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // 1. بيانات الدور
                Section::make(__('roles.role_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('roles.role_name'))
                            ->placeholder(__('roles.role_name_placeholder'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),

                // 2. توزيع الصلاحيات
                Section::make(__('roles.permissions_distribution'))
                    ->schema([
                        // 1. مجموعة المديرين
                        Section::make(__('permissions.admins_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_admin')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_admin' => __('permissions.view_any_admin'),
                                        'create_admin' => __('permissions.create_admin'),
                                        'update_admin' => __('permissions.update_admin'),
                                        'delete_admin' => __('permissions.delete_admin'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 2. مجموعة الأدوار والصلاحيات
                        Section::make(__('permissions.roles_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_roles')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_role' => __('permissions.view_any_role'),
                                        'create_role' => __('permissions.create_role'),
                                        'update_role' => __('permissions.update_role'),
                                        'delete_role' => __('permissions.delete_role'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 3. مجموعة الشركات والهيكل التنظيمي
                        Section::make(__('permissions.organization_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_org')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_company' => __('permissions.view_any_company'),
                                        'create_company' => __('permissions.create_company'),
                                        'update_company' => __('permissions.update_company'),
                                        'delete_company' => __('permissions.delete_company'),

                                        'view_any_branch' => __('permissions.view_any_branch'),
                                        'create_branch' => __('permissions.create_branch'),
                                        'update_branch' => __('permissions.update_branch'),
                                        'delete_branch' => __('permissions.delete_branch'),

                                        'view_any_department' => __('permissions.view_any_department'),
                                        'create_department' => __('permissions.create_department'),
                                        'update_department' => __('permissions.update_department'),
                                        'delete_department' => __('permissions.delete_department'),

                                        'view_any_job_title' => __('permissions.view_any_job_title'),
                                        'create_job_title' => __('permissions.create_job_title'),
                                        'update_job_title' => __('permissions.update_job_title'),
                                        'delete_job_title' => __('permissions.delete_job_title'),

                                        'view_any_owner' => __('permissions.view_any_owner'),
                                        'create_owner' => __('permissions.create_owner'),
                                        'update_owner' => __('permissions.update_owner'),
                                        'delete_owner' => __('permissions.delete_owner'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 4. مجموعة شؤون الموظفين
                        Section::make(__('permissions.employees_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_employees')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_saudi_employee' => __('permissions.view_any_saudi_employee'),
                                        'create_saudi_employee' => __('permissions.create_saudi_employee'),
                                        'update_saudi_employee' => __('permissions.update_saudi_employee'),
                                        'delete_saudi_employee' => __('permissions.delete_saudi_employee'),

                                        'view_any_foreign_employee' => __('permissions.view_any_foreign_employee'),
                                        'create_foreign_employee' => __('permissions.create_foreign_employee'),
                                        'update_foreign_employee' => __('permissions.update_foreign_employee'),
                                        'delete_foreign_employee' => __('permissions.delete_foreign_employee'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 5. مجموعة الحضور والانصراف
                        Section::make(__('permissions.attendance_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_attendance')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_attendance' => __('permissions.view_any_attendance'),
                                        'create_attendance' => __('permissions.create_attendance'),
                                        'update_attendance' => __('permissions.update_attendance'),
                                        'delete_attendance' => __('permissions.delete_attendance'),
                                        'check_attendance_absent' => __('permissions.check_attendance_absent'),

                                        'view_any_attendance_device' => __('permissions.view_any_attendance_device'),
                                        'create_attendance_device' => __('permissions.create_attendance_device'),
                                        'update_attendance_device' => __('permissions.update_attendance_device'),
                                        'delete_attendance_device' => __('permissions.delete_attendance_device'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 3]),
                            ]),

                        // 6. مجموعة الإجازات
                        Section::make(__('permissions.leaves_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_leaves')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_leave' => __('permissions.view_any_leave'),
                                        'create_leave' => __('permissions.create_leave'),
                                        'update_leave' => __('permissions.update_leave'),
                                        'delete_leave' => __('permissions.delete_leave'),
                                        'change_leave_status' => __('permissions.change_leave_status'),

                                        'view_any_leave_type' => __('permissions.view_any_leave_type'),
                                        'create_leave_type' => __('permissions.create_leave_type'),
                                        'update_leave_type' => __('permissions.update_leave_type'),
                                        'delete_leave_type' => __('permissions.delete_leave_type'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 3]),
                            ]),

                        // 7. مجموعة العملاء والخدمات
                        Section::make(__('permissions.crm_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_crm')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_customer' => __('permissions.view_any_customer'),
                                        'create_customer' => __('permissions.create_customer'),
                                        'update_customer' => __('permissions.update_customer'),
                                        'delete_customer' => __('permissions.delete_customer'),

                                        'view_any_service' => __('permissions.view_any_service'),
                                        'create_service' => __('permissions.create_service'),
                                        'update_service' => __('permissions.update_service'),
                                        'delete_service' => __('permissions.delete_service'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 8. مجموعة الفواتير والمالية وعروض الأسعار
                        Section::make(__('permissions.billing_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_billing')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_invoice' => __('permissions.view_any_invoice'),
                                        'create_invoice' => __('permissions.create_invoice'),
                                        'update_invoice' => __('permissions.update_invoice'),
                                        'delete_invoice' => __('permissions.delete_invoice'),

                                        'view_any_invoice_type' => __('permissions.view_any_invoice_type'),
                                        'create_invoice_type' => __('permissions.create_invoice_type'),
                                        'update_invoice_type' => __('permissions.update_invoice_type'),
                                        'delete_invoice_type' => __('permissions.delete_invoice_type'),

                                        'view_any_offer' => __('permissions.view_any_offer'),
                                        'create_offer' => __('permissions.create_offer'),
                                        'update_offer' => __('permissions.update_offer'),
                                        'delete_offer' => __('permissions.delete_offer'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 9. مجموعة المهام والملاحظات
                        Section::make(__('permissions.tasks_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_tasks')
                                    ->hiddenLabel()
                                    ->options([
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
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 10. مجموعة إعدادات النظام
                        Section::make(__('permissions.settings_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_settings')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_setting' => __('permissions.view_any_setting'),
                                        'create_setting' => __('permissions.create_setting'),
                                        'update_setting' => __('permissions.update_setting'),
                                        'delete_setting' => __('permissions.delete_setting'),
                                        'reset_database' => __('permissions.reset_database'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 3]),
                            ]),

                        // 11. مجموعة المالية والمحاسبة والخزينة
                        Section::make(__('permissions.finance_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_finance')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_payment_collection_item' => __('permissions.view_any_payment_collection_item'),
                                        'create_payment_collection_item' => __('permissions.create_payment_collection_item'),
                                        'update_payment_collection_item' => __('permissions.update_payment_collection_item'),
                                        'delete_payment_collection_item' => __('permissions.delete_payment_collection_item'),

                                        'view_any_pos_device' => __('permissions.view_any_pos_device'),
                                        'create_pos_device' => __('permissions.create_pos_device'),
                                        'update_pos_device' => __('permissions.update_pos_device'),
                                        'delete_pos_device' => __('permissions.delete_pos_device'),

                                        'view_any_treasury_transaction' => __('permissions.view_any_treasury_transaction'),
                                        'create_treasury_transaction' => __('permissions.create_treasury_transaction'),
                                        'update_treasury_transaction' => __('permissions.update_treasury_transaction'),
                                        'delete_treasury_transaction' => __('permissions.delete_treasury_transaction'),

                                        'view_any_daily_journal' => __('permissions.view_any_daily_journal'),
                                        'create_daily_journal' => __('permissions.create_daily_journal'),
                                        'update_daily_journal' => __('permissions.update_daily_journal'),
                                        'delete_daily_journal' => __('permissions.delete_daily_journal'),

                                        'view_any_employee_reward' => __('permissions.view_any_employee_reward'),
                                        'create_employee_reward' => __('permissions.create_employee_reward'),
                                        'update_employee_reward' => __('permissions.update_employee_reward'),
                                        'delete_employee_reward' => __('permissions.delete_employee_reward'),

                                        'view_any_payroll_record' => __('permissions.view_any_payroll_record'),
                                        'create_payroll_record' => __('permissions.create_payroll_record'),
                                        'update_payroll_record' => __('permissions.update_payroll_record'),
                                        'delete_payroll_record' => __('permissions.delete_payroll_record'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 12. مجموعة الأقساط والتقسيط
                        Section::make(__('permissions.installments_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_installments')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_installment' => __('permissions.view_any_installment'),
                                        'create_installment' => __('permissions.create_installment'),
                                        'update_installment' => __('permissions.update_installment'),
                                        'delete_installment' => __('permissions.delete_installment'),

                                        'view_any_installment_payment' => __('permissions.view_any_installment_payment'),
                                        'create_installment_payment' => __('permissions.create_installment_payment'),
                                        'update_installment_payment' => __('permissions.update_installment_payment'),
                                        'delete_installment_payment' => __('permissions.delete_installment_payment'),

                                        'view_any_installment_type' => __('permissions.view_any_installment_type'),
                                        'create_installment_type' => __('permissions.create_installment_type'),
                                        'update_installment_type' => __('permissions.update_installment_type'),
                                        'delete_installment_type' => __('permissions.delete_installment_type'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        Section::make(__('permissions.faq_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_faq')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_faq' => __('permissions.view_any_faq'),
                                        'create_faq'   => __('permissions.create_faq'),
                                        'update_faq'   => __('permissions.update_faq'),
                                        'delete_faq'   => __('permissions.delete_faq'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 13. مجموعة تذاكر الدعم
                        Section::make(__('permissions.tickets_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_tickets')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_ticket' => __('permissions.view_any_ticket'),
                                        'create_ticket' => __('permissions.create_ticket'),
                                        'update_ticket' => __('permissions.update_ticket'),
                                        'delete_ticket' => __('permissions.delete_ticket'),
                                        'manage_tickets' => __('permissions.manage_tickets'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 3]),
                            ]),

                        // 14. مجموعة المخالفات واللوائح
                        Section::make(__('permissions.violations_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_violations')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_violation' => __('permissions.view_any_violation'),
                                        'create_violation' => __('permissions.create_violation'),
                                        'update_violation' => __('permissions.update_violation'),
                                        'delete_violation' => __('permissions.delete_violation'),

                                        'view_any_violation_category' => __('permissions.view_any_violation_category'),
                                        'create_violation_category' => __('permissions.create_violation_category'),
                                        'update_violation_category' => __('permissions.update_violation_category'),
                                        'delete_violation_category' => __('permissions.delete_violation_category'),

                                        'view_any_violation_type' => __('permissions.view_any_violation_type'),
                                        'create_violation_type' => __('permissions.create_violation_type'),
                                        'update_violation_type' => __('permissions.update_violation_type'),
                                        'delete_violation_type' => __('permissions.delete_violation_type'),

                                        'view_any_violation_transaction' => __('permissions.view_any_violation_transaction'),
                                        'create_violation_transaction' => __('permissions.create_violation_transaction'),
                                        'update_violation_transaction' => __('permissions.update_violation_transaction'),
                                        'delete_violation_transaction' => __('permissions.delete_violation_transaction'),

                                        'view_any_violation_regulation' => __('permissions.view_any_violation_regulation'),
                                        'create_violation_regulation' => __('permissions.create_violation_regulation'),
                                        'update_violation_regulation' => __('permissions.update_violation_regulation'),
                                        'delete_violation_regulation' => __('permissions.delete_violation_regulation'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 15. صناديق التكافل والادخار (سلفني والجمعية)
                        Section::make(__('permissions.funds_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_funds')
                                    ->hiddenLabel()
                                    ->options([
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
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),

                        // 16. التقارير وأدوات النظام
                        Section::make(__('permissions.reports_and_tools_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_reports_and_tools')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_general_reports' => __('permissions.view_general_reports'),
                                        'view_notifications_report' => __('permissions.view_notifications_report'),
                                        'manage_translations' => __('permissions.manage_translations'),
                                        'manage_smart_import' => __('permissions.manage_smart_import'),
                                        'view_loan_fund_policy' => __('permissions.view_loan_fund_policy'),
                                        'view_rosca_fund_policy' => __('permissions.view_rosca_fund_policy'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 3]),
                            ]),

                        // 17. الأسئلة الشائعة وقاعدة المعرفة
                        Section::make(__('permissions.faq_group'))
                            ->collapsible()
                            ->schema([
                                CheckboxList::make('permissions_faq')
                                    ->hiddenLabel()
                                    ->options([
                                        'view_any_faq' => __('permissions.view_any_faq'),
                                        'create_faq'   => __('permissions.create_faq'),
                                        'update_faq'   => __('permissions.update_faq'),
                                        'delete_faq'   => __('permissions.delete_faq'),
                                    ])
                                    ->bulkToggleable()
                                    ->dehydrated(false)
                                    ->columns(['default' => 2, 'sm' => 2, 'lg' => 4]),
                            ]),
                    ]),
            ]);
    }
}
