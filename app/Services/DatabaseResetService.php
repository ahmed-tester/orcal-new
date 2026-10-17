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
}
