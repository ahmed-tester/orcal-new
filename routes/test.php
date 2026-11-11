<?php

// ─── Test, Demo & Setup Routes ───────────────────────────────────────────
// Routes used only for automated browser testing, seeding, and local development.
// Registered with the "web" middleware group via bootstrap/app.php.

use Illuminate\Support\Facades\Route;

// Operations Demo Seeder Route (for automated browser testing)
Route::get('/system/seed-operations-demo', function () {
    $seeder = new \Database\Seeders\OperationsDemoSeeder;
    $result = $seeder->run();

    return response()->json([
        'status' => 'success',
        'message' => 'Operations demo data seeded successfully',
        'data' => $result,
    ]);
})->name('system.seed-operations-demo');

Route::get('/seed-operations-demo', function () {
    $seeder = new \Database\Seeders\OperationsDemoSeeder;
    $result = $seeder->run();

    return response()->json([
        'status' => 'success',
        'message' => 'Operations demo data seeded successfully',
        'data' => $result,
    ]);
})->name('seed-operations-demo');

// Operations Workflow Permissions & Roles Sync Routes
Route::get('/system/sync-operations-permissions', function () {
    $result = \App\Services\OperationsPermissionService::syncPermissions();

    return response()->json([
        'status' => 'success',
        'message' => __('services_tracking.permissions.sync_success'),
        'details' => $result,
    ]);
})->name('system.sync-operations-permissions');

Route::get('/sync-operations-permissions', function () {
    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();
    $result = \App\Services\OperationsPermissionService::syncPermissions();

    return response()->json([
        'status' => 'success',
        'message' => 'All system roles and operations permissions seeded successfully',
        'details' => $result,
    ]);
})->name('sync-operations-permissions');

// Notification Test Route
Route::get('/test-notification', function () {
    $user = auth()->user()
        ?? \App\Models\User::where('email', 'finance@orcal.test')->first()
        ?? \App\Models\User::where('email', 'admin@admin.com')->first();

    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'No user found']);
    }

    \Filament\Notifications\Notification::make()
        ->title(__('services_tracking.notifications.test_title'))
        ->body(__('services_tracking.notifications.test_body'))
        ->icon('heroicon-o-bell-alert')
        ->status('success')
        ->sendToDatabase($user);

    return response()->json([
        'status' => 'success',
        'message' => 'Test notification sent to user: '.$user->email,
        'user_id' => $user->id,
    ]);
})->name('test-notification');
