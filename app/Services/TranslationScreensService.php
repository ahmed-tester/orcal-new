<?php

namespace App\Services;

class TranslationScreensService
{
    /**
     * Map each translation file to the translation keys of the screens/pages that use it.
     * Using translation keys ensures screen names stay synchronized whenever translations are edited.
     *
     * @var array<string, array<int, string>>
     */
    protected const FILE_SCREEN_KEYS = [
        'admin.php' => ['translations.screens.common_ui', 'navigation.users'],
        'asset_funds.php' => ['navigation.asset_funds', 'navigation.asset_fund_vouchers', 'navigation.asset_fund_statement'],
        'attendance.php' => ['navigation.attendance'],
        'attendance_devices.php' => ['navigation.attendance_devices'],
        'auth.php' => ['translations.screens.auth'],
        'contract_categories.php' => ['navigation.contract_categories', 'navigation.offers'],
        'contract_vouchers.php' => ['navigation.contract_vouchers'],
        'customers.php' => ['navigation.customers', 'navigation.offers'],
        'dashboard.php' => ['dashboard.title'],
        'debts.php' => ['navigation.debts', 'navigation.debt_collections', 'navigation.system_documentation'],
        'documentation.php' => ['navigation.system_documentation'],
        'employees.php' => ['navigation.employees', 'navigation.saudi_employees', 'navigation.foreign_employees', 'navigation.temporary_employees'],
        'faq.php' => ['navigation.faqs'],
        'finance.php' => [
            'navigation.treasury_transactions',
            'navigation.payroll_records',
            'navigation.employee_rewards',
            'navigation.installments',
            'navigation.pos_devices',
            'navigation.daily_journals',
        ],
        'funds.php' => [
            'navigation.loan_funds',
            'navigation.loan_fund_policy',
            'navigation.rosca_funds',
            'navigation.rosca_fund_policy',
        ],
        'guarantors.php' => ['translations.screens.guarantors'],
        'invoices.php' => ['navigation.invoices', 'navigation.invoice_types'],
        'leaves.php' => ['navigation.leaves', 'navigation.leave_types', 'navigation.attendance'],
        'navigation.php' => ['translations.screens.sidebar_navigation'],
        'navigation_order.php' => ['navigation_order.page_title'],
        'notifications.php' => ['translations.screens.notifications'],
        'offers.php' => ['navigation.offers', 'navigation.contract_vouchers'],
        'official_letters.php' => ['navigation.official_letters'],
        'organization.php' => ['navigation.companies', 'navigation.branches', 'navigation.departments', 'organization.job_titles.plural'],
        'owners.php' => ['navigation.owners'],
        'pagination.php' => ['translations.screens.pagination'],
        'passwords.php' => ['translations.screens.passwords'],
        'permissions.php' => ['navigation.roles'],
        'reports.php' => ['navigation.general_reports', 'navigation.notifications_report'],
        'roles.php' => ['navigation.roles'],
        'services.php' => ['navigation.services', 'navigation.services_tracking'],
        'services_tracking.php' => ['navigation.services_tracking'],
        'settings.php' => ['navigation.settings'],
        'smart_import.php' => ['navigation.smart_file_import'],
        'tasks.php' => ['navigation.tasks', 'navigation.task_assignments', 'navigation.supervision_shifts'],
        'tickets.php' => ['navigation.tickets'],
        'translations.php' => ['translations.title'],
        'user_types.php' => ['translations.screens.user_types'],
        'validation.php' => ['translations.screens.validation'],
        'violations.php' => ['navigation.violations', 'navigation.violation_categories', 'navigation.violation_types', 'navigation.violation_transactions', 'navigation.violation_regulations'],
        'weekly_notes.php' => ['navigation.weekly_notes'],
    ];

    /**
     * Resolve the translated screen names associated with a translation file.
     *
     * @return array<int, string>
     */
    public function getScreensForFile(string $fileName, ?string $locale = null): array
    {
        $keys = self::FILE_SCREEN_KEYS[$fileName] ?? [];

        if ($keys === []) {
            return [pathinfo($fileName, PATHINFO_FILENAME)];
        }

        $screens = array_map(
            static function (string $key) use ($locale): string {
                $translated = (string) __($key, [], $locale);

                if ($translated === $key && $locale !== 'ar') {
                    return (string) __($key, [], 'ar');
                }

                return $translated;
            },
            $keys
        );

        return array_values(array_unique(array_filter($screens)));
    }
}
