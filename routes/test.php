<?php

// ─── Test, Demo & Setup Routes ───────────────────────────────────────────
// Routes used only for automated browser testing, seeding, and local development.
// Registered with the "web" middleware group via bootstrap/app.php.

use Illuminate\Support\Facades\Route;

// Operations Demo Seeder Route (for automated browser testing & manual UAT)
$seedOperationsDemoHandler = function () {
    // 1. Seed all system roles and base permissions
    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();

    // 2. Sync all operational workflow permissions (receipt, handover, disbursement, etc.)
    $opsPermissionsResult = \App\Services\OperationsPermissionService::syncPermissions();

    // 3. Seed demo organization, branch, accounts, supervision shift, customer, and services catalog
    $seeder = new \Database\Seeders\OperationsDemoSeeder;
    $result = $seeder->run();

    // 4. Reset Spatie cached permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    return response()->json([
        'status' => 'success',
        'message' => 'تم تجهيز بيئة اختبار الخدمات والموظفين والصلاحيات بنجاح. يمكنك تسجيل الدخول بالبيانات أدناه وبدء اختبار الخدمات مباشرة.',
        'uat_test_service' => [
            'name' => 'خدمة تجريبية لاختبار الدورة المالية (UAT 500/300)',
            'price' => 500,
            'default_gov_fee' => 300,
            'target_type' => 'employee (خدمة موظف)',
        ],
        'seeded_accounts' => [
            [
                'role_label' => 'المنفذ (موظف سعودي)',
                'email' => 'operator@orcal.test',
                'password' => 'password',
                'role' => 'موظف',
                'testing_step' => 'إنشاء المعاملة + قبض أولي نقد 500 + طلب توريد النقد واستخراج كود PIN + متابعة الأداء في «لوحتي»',
            ],
            [
                'role_label' => 'أمين الخزنة والمحاسب',
                'email' => 'finance@orcal.test',
                'password' => 'password',
                'role' => 'محاسب مالي',
                'testing_step' => 'تأكيد سند القبض المعلق + استلام وتأكيد النقد في الخزنة برقم الوصل وكود PIN (+500)',
            ],
            [
                'role_label' => 'مدير العمليات',
                'email' => 'ops.manager@orcal.test',
                'password' => 'password',
                'role' => 'مدير عمليات',
                'testing_step' => 'سداد الرسوم المباشر من الخزنة (-300) + فحص المطابقة وإكمال المعاملة + اعتماد مطالبات السداد الشخصي',
            ],
            [
                'role_label' => 'مشرف النوبة الميداني',
                'email' => 'ops.supervisor@orcal.test',
                'password' => 'password',
                'role' => 'مشرف عمليات',
                'testing_step' => 'مراجعة أداء المنفذ (نوبة إشراف نشطة ومربوطة بالمنفذ للشهر الحالي)',
            ],
            [
                'role_label' => 'الموظف المستفيد',
                'email' => 'beneficiary@orcal.test',
                'password' => 'password',
                'role' => 'موظف',
                'testing_step' => 'اختياره كـ «موظف مستفيد» في المعاملة للتأكد من فصل إنجاز المنفذ عن المستفيد',
            ],
            [
                'role_label' => 'مدير النظام الرئيسي',
                'email' => 'admin@admin.com',
                'password' => 'password',
                'role' => 'مدير عام / Super Admin',
                'testing_step' => 'الرقابة الشاملة وإدارة الصلاحيات والإعدادات',
            ],
        ],
        'quick_uat_workflow' => [
            'step_1' => 'سجل دخول بـ operator@orcal.test وافتح «الخدمات والمتابعة ← إنشاء»، اختر خدمة موظف والخدمة التجريبية (500/300) والمستفيد beneficiary، فعّل القبض عند الإنشاء (500 نقد) وطلب التوريد.',
            'step_2' => 'سجل دخول بـ finance@orcal.test وأكد سند القبض المعلق، ثم من «الخزنة ← استلام وتأكيد النقد» استلم النقد برقم الوصل وكود PIN المنفذ (+500 حركة خزنة).',
            'step_3' => 'سجل دخول بـ ops.manager@orcal.test وسدد الرسوم المباشرة (300) من الخزنة (-300 حركة خزنة)، واضغط «إعادة فحص المطابقة» لتكتمل المعاملة.',
            'step_4' => 'افتح «لوحتي» بـ operator@orcal.test لتجد المعاملة في المكتملة والنواقص محلولة، وافتح أداء المنفذ بـ ops.supervisor@orcal.test للتأكد من ظهورها في نوبة الإشراف.',
        ],
        'data' => $result,
    ]);
};

Route::get('/system/seed-operations-demo', $seedOperationsDemoHandler)->name('system.seed-operations-demo');
Route::get('/seed-operations-demo', $seedOperationsDemoHandler)->name('seed-operations-demo');

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
