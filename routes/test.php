<?php

// ─── Test, Demo & Setup Routes ───────────────────────────────────────────
// Routes used only for automated browser testing, seeding, and local development.
// Registered with the "web" middleware group via bootstrap/app.php.

use App\Filament\Pages\CompanyDepartmentPerformance;
use App\Filament\Pages\SupervisorPerformance;
use App\Filament\Resources\PerformanceEscalations\PerformanceEscalationResource;
use App\Models\User;
use App\Services\OperationsPermissionService;
use Database\Seeders\DepartmentPerformanceDemoSeeder;
use Database\Seeders\OperationsDemoSeeder;
use Database\Seeders\PerformanceDashboardDemoSeeder;
use Database\Seeders\PerformanceEscalationDemoSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SupervisorPerformanceDemoSeeder;
use Database\Seeders\TicketsDemoSeeder;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

// Operations Demo Seeder Route (for automated browser testing & manual UAT)
$seedOperationsDemoHandler = function () {
    // 1. Seed all system roles and base permissions
    (new RolesAndPermissionsSeeder)->run();

    // 2. Sync all operational workflow permissions (receipt, handover, disbursement, etc.)
    $opsPermissionsResult = OperationsPermissionService::syncPermissions();

    // 3. Seed demo organization, branch, accounts, supervision shift, customer, and services catalog
    $seeder = new OperationsDemoSeeder;
    $result = $seeder->run();

    // 4. Reset Spatie cached permissions
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

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

Route::match(['get', 'post'], '/system/seed-performance-demo', function (Request $request) {
    abort_unless(PerformanceDashboardDemoSeeder::isAvailable(), 404);

    if (! $request->user()) {
        return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
    }

    abort_unless($request->user()->isSuperAdmin(), 403);

    return view('system.seed-performance-demo', [
        'companies' => $request->isMethod('post') ? app(PerformanceDashboardDemoSeeder::class)->run() : [],
        'dashboardUrl' => CompanyDepartmentPerformance::getUrl(panel: 'admin'),
    ]);
})->name('system.seed-performance-demo');

Route::match(['get', 'post'], '/system/seed-department-performance-demo', function (Request $request) {
    abort_unless(PerformanceDashboardDemoSeeder::isAvailable(), 404);

    if (! $request->user()) {
        return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
    }

    abort_unless($request->user()->isSuperAdmin(), 403);

    try {
        $companies = $request->isMethod('post') ? app(DepartmentPerformanceDemoSeeder::class)->run() : [];
    } catch (DomainException $exception) {
        return redirect()->route('system.seed-department-performance-demo')->withErrors($exception->getMessage());
    }

    return view('system.seed-performance-demo', [
        'companies' => $companies,
        'dashboardUrl' => CompanyDepartmentPerformance::getUrl(panel: 'admin'),
        'translation' => 'performance.department_demo',
        'submitRoute' => 'system.seed-department-performance-demo',
    ]);
})->name('system.seed-department-performance-demo');

Route::match(['get', 'post'], '/system/seed-supervisor-performance-demo', function (Request $request) {
    abort_unless(PerformanceDashboardDemoSeeder::isAvailable(), 404);

    if (! $request->user()) {
        return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
    }

    abort_unless($request->user()->isSuperAdmin(), 403);

    try {
        $results = $request->isMethod('post') ? app(SupervisorPerformanceDemoSeeder::class)->run() : [];
    } catch (DomainException $exception) {
        return redirect()->route('system.seed-supervisor-performance-demo')->withErrors($exception->getMessage());
    }

    return view('system.seed-performance-demo', [
        'companies' => array_map(fn (array $result) => [
            'label' => __('supervisor_performance.demo.result', $result),
            'url' => SupervisorPerformance::getUrl([
                'companyId' => $result['companyId'],
                'supervisorId' => $result['supervisorId'],
                'month' => $result['month'],
            ], panel: 'admin'),
        ], $results),
        'dashboardUrl' => SupervisorPerformance::getUrl(panel: 'admin'),
        'dashboardLabel' => __('supervisor_performance.navigation'),
        'translation' => 'supervisor_performance.demo',
        'submitRoute' => 'system.seed-supervisor-performance-demo',
    ]);
})->name('system.seed-supervisor-performance-demo');

Route::match(['get', 'post'], '/system/seed-performance-escalation-demo', function (Request $request) {
    abort_unless(PerformanceEscalationDemoSeeder::isAvailable(), 404);

    if (! $request->user()) {
        return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
    }

    abort_unless($request->user()->isSuperAdmin(), 403);

    return view('system.seed-performance-demo', [
        'companies' => $request->isMethod('post') ? app(PerformanceEscalationDemoSeeder::class)->run() : [],
        'dashboardUrl' => CompanyDepartmentPerformance::getUrl(panel: 'admin'),
        'escalationsUrl' => PerformanceEscalationResource::getUrl(panel: 'admin'),
        'translation' => 'performance.escalation_demo',
        'submitRoute' => 'system.seed-performance-escalation-demo',
    ]);
})->name('system.seed-performance-escalation-demo');

Route::match(['get', 'post'], '/system/seed-tickets-demo', function (Request $request) {
    if (! $request->user()) {
        return redirect()->guest(Filament::getPanel('admin')->getLoginUrl());
    }

    abort_unless($request->user()->isSuperAdmin(), 403);

    return view('system.seed-performance-demo', [
        'companies' => $request->isMethod('post') ? app(TicketsDemoSeeder::class)->run() : [],
        'dashboardUrl' => CompanyDepartmentPerformance::getUrl(panel: 'admin'),
        'translation' => 'performance.tickets_demo',
        'submitRoute' => 'system.seed-tickets-demo',
    ]);
})->name('system.seed-tickets-demo');

// Operations Workflow Permissions & Roles Sync Routes
Route::get('/system/sync-operations-permissions', function () {
    $result = OperationsPermissionService::syncPermissions();

    return response()->json([
        'status' => 'success',
        'message' => __('services_tracking.permissions.sync_success'),
        'details' => $result,
    ]);
})->name('system.sync-operations-permissions');

// Route::get('/system/sync-supervise-task-team-permission', function () {
//     abort_unless(auth()->user()?->isSuperAdmin(), 403);

//     $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
//         'name' => \App\Services\EmployeePerformanceAccessService::SUPERVISE_PERMISSION,
//         'guard_name' => 'web',
//     ]);

//     app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

//     return response()->json([
//         'status' => 'success',
//         'permission' => $permission->name,
//         'created' => $permission->wasRecentlyCreated,
//     ]);
// })->name('system.sync-supervise-task-team-permission');

Route::get('/sync-operations-permissions', function () {
    (new RolesAndPermissionsSeeder)->run();
    $result = OperationsPermissionService::syncPermissions();

    return response()->json([
        'status' => 'success',
        'message' => 'All system roles and operations permissions seeded successfully',
        'details' => $result,
    ]);
})->name('sync-operations-permissions');

// Notification Test Route
Route::get('/test-notification', function () {
    $user = auth()->user()
        ?? User::where('email', 'finance@orcal.test')->first()
        ?? User::where('email', 'admin@admin.com')->first();

    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'No user found']);
    }

    Notification::make()
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

Route::get('/system/seed-directives-permissions', function () {
    abort_unless(auth()->user()?->isSuperAdmin(), 403);

    $permissions = [
        'view_any_directive',
        'create_directive',
        'update_directive',
        'delete_directive',
        'view_any_directive_category',
        'create_directive_category',
        'update_directive_category',
        'delete_directive_category',
    ];

    $results = [];
    foreach ($permissions as $name) {
        $permission = Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);

        $results[$name] = $permission->wasRecentlyCreated ? 'created' : 'exists';
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return response()->json([
        'status' => 'success',
        'message' => 'Directives permissions synced successfully.',
        'permissions' => $results,
    ]);
})->name('system.seed-directives-permissions');

Route::get('/system/sync-document-renewal-permissions', function () {
    abort_unless(auth()->user()?->isSuperAdmin(), 403);

    $synced = \App\Services\Tasks\Renewals\RenewalDocumentRegistry::syncPermissionDefinitions();

    return response()->json([
        'status' => 'success',
        'message' => 'Document renewal permission definitions synced successfully (not auto-assigned to roles).',
        'permissions' => $synced,
    ]);
})->name('system.sync-document-renewal-permissions');
