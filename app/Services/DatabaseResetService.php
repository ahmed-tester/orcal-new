<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseResetService
{
    /**
     * Tables that should NEVER be wiped.
     */
    protected static array $protectedTables = [
        'migrations',
        'settings',
        'roles',
        'permissions',
        'role_has_permissions',
        'model_has_roles',
        'model_has_permissions',
        'users', // Handled with selective filtering
        'cache',
        'cache_locks',
        'sessions',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ];

    /**
     * Wipe all operational data from the database while preserving Admins & Super Admins.
     *
     * @return array Summary of the operation
     */
    public static function resetDatabase(): array
    {
        // 1. Identify all admin & super admin user IDs to preserve
        $adminUserIds = User::where(function ($query) {
            $query->whereIn('user_type', [UserType::SUPER_ADMIN, UserType::ADMIN])
                ->orWhereHas('roles', function ($r) {
                    $r->whereIn('name', ['Super Admin', 'Admin']);
                });
        })
        ->when(auth()->id(), fn ($q) => $q->orWhere('id', auth()->id()))
        ->pluck('id')
        ->toArray();

        $truncatedTables = [];

        try {
            // Disable foreign key constraints temporarily
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

            // 2. Fetch all tables currently existing in the database
            $allTables = collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->values();

            // 3. Truncate all non-protected operational tables
            foreach ($allTables as $table) {
                if (!in_array($table, static::$protectedTables, true)) {
                    DB::statement("TRUNCATE TABLE `{$table}`;");
                    $truncatedTables[] = $table;
                }
            }

            // 4. Clean up non-admin users and their roles
            if (!empty($adminUserIds)) {
                DB::table('model_has_roles')
                    ->where('model_type', User::class)
                    ->whereNotIn('model_id', $adminUserIds)
                    ->delete();

                DB::table('users')
                    ->whereNotIn('id', $adminUserIds)
                    ->delete();
            }
        } catch (\Throwable $e) {
            Log::error('Database reset failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        } finally {
            // Always re-enable foreign key constraints
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        // Clear cache and views
        Artisan::call('optimize:clear');

        Log::info('Database reset executed successfully', [
            'by_user_id' => auth()->id(),
            'preserved_admin_count' => count($adminUserIds),
            'truncated_tables_count' => count($truncatedTables),
        ]);

        return [
            'success' => true,
            'preserved_admins' => count($adminUserIds),
            'truncated_tables_count' => count($truncatedTables),
            'truncated_tables' => $truncatedTables,
        ];
    }

    /**
     * Module definitions specifying tables belonging to each logical group.
     */
    public static array $moduleDefinitions = [
        'finance' => [
            'tables' => [
                'payment_collection_items',
                'treasury_transactions',
                'installment_payments',
                'installments',
                'installment_schedules',
                'installment_types',
                'payroll_payments',
                'payroll_records',
                'invoice_items',
                'invoices',
                'invoice_types',
                'debt_collections',
                'debts',
                'daily_journals',
                'pos_devices',
            ],
            'clean_users' => false,
        ],
        'hr' => [
            'tables' => [
                'biometric_logs',
                'attendance',
                'attendance_devices',
                'leaves',
                'leave_types',
                'employee_rewards',
                'supervision_shifts',
                'official_letters',
                'payroll_payments',
                'payroll_records',
                'employee_details',
                'guarantors',
                'job_titles',
                'employees',
            ],
            'clean_users' => true,
        ],
        'contracts_offers' => [
            'tables' => [
                'contract_vouchers',
                'offer_items',
                'offers',
                'contract_categories',
                'installment_payments',
                'installment_schedules',
                'installments',
                'installment_types',
                'service_transactions',
                'services',
            ],
            'clean_users' => false,
        ],
        'tasks_violations' => [
            'tables' => [
                'task_assignment_items',
                'task_assignments',
                'tasks',
                'weekly_notes',
                'violation_transactions',
                'violation_regulations',
                'violation_categories',
                'violation_types',
                'violations',
                'ticket_messages',
                'tickets',
                'notifications',
            ],
            'clean_users' => false,
        ],
        'funds_rosca' => [
            'tables' => [
                'loan_fund_loan_payments',
                'loan_fund_loans',
                'loan_fund_member_payments',
                'loan_fund_members',
                'loan_funds',
                'rosca_fund_payments',
                'rosca_fund_members',
                'rosca_funds',
            ],
            'clean_users' => false,
        ],
        'companies_clients' => [
            'tables' => [
                'company_attachments',
                'company_department',
                'departments',
                'branches',
                'customers',
                'owners',
                'faqs',
                'companies',
            ],
            'clean_users' => false,
        ],
    ];

    /**
     * Reset a specific module by key while maintaining system safety.
     *
     * @param string $moduleKey
     * @return array Summary of the operation
     */
    public static function resetModule(string $moduleKey): array
    {
        if (!isset(static::$moduleDefinitions[$moduleKey])) {
            throw new \InvalidArgumentException("Unknown module key: {$moduleKey}");
        }

        $config = static::$moduleDefinitions[$moduleKey];
        $tables = $config['tables'] ?? [];
        $truncatedTables = [];

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

            foreach ($tables as $table) {
                if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    DB::statement("TRUNCATE TABLE `{$table}`;");
                    $truncatedTables[] = $table;
                }
            }

            if (!empty($config['clean_users'])) {
                $adminUserIds = User::where(function ($query) {
                    $query->whereIn('user_type', [UserType::SUPER_ADMIN, UserType::ADMIN])
                        ->orWhereHas('roles', function ($r) {
                            $r->whereIn('name', ['Super Admin', 'Admin']);
                        });
                })
                ->when(auth()->id(), fn ($q) => $q->orWhere('id', auth()->id()))
                ->pluck('id')
                ->toArray();

                if (!empty($adminUserIds)) {
                    DB::table('model_has_roles')
                        ->where('model_type', User::class)
                        ->whereNotIn('model_id', $adminUserIds)
                        ->delete();

                    DB::table('users')
                        ->whereNotIn('id', $adminUserIds)
                        ->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::error("Module reset failed for {$moduleKey}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        }

        Artisan::call('optimize:clear');

        Log::info("Module reset executed successfully for {$moduleKey}", [
            'by_user_id' => auth()->id(),
            'module' => $moduleKey,
            'truncated_tables_count' => count($truncatedTables),
        ]);

        return [
            'success' => true,
            'module' => $moduleKey,
            'truncated_tables_count' => count($truncatedTables),
            'truncated_tables' => $truncatedTables,
        ];
    }
}
