<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\JobTitle;
use App\Models\User;
use App\Services\OperationsPermissionService;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OperationsDemoSeeder extends Seeder
{
    /**
     * Prepare environment: organization structure, roles, and test users.
     *
     * @return array<string, mixed>
     */
    public function run(): array
    {
        // 1. Seed all system roles and permissions first
        (new RolesAndPermissionsSeeder)->run();

        // 2. Sync operations workflow permissions
        OperationsPermissionService::syncPermissions();

        return DB::transaction(function () {
            // 2. Demonstration company
            $company = Company::firstOrCreate(
                ['commercial_registration_number' => '1010889900'],
                [
                    'company_number' => 'COMP-ORCAL-01',
                    'trade_name' => 'مؤسسة أوركال لخدمات الأعمال والحلول الحكومية',
                    'trade_name_en' => 'Orcal Business & Gov Solutions Est.',
                    'address' => 'الرياض - طريق الملك فهد - مركز العليا للأعمال',
                    'address_en' => 'Riyadh - King Fahd Rd - Olaya Business Center',
                    'postal_code' => '12214',
                    'phone' => '0114889900',
                    'email' => 'contact@orcal-services.test',
                    'status' => 'active',
                ]
            );

            // 3. Branches, departments and job titles
            $branch = Branch::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'الفرع الرئيسي - الرياض'],
                ['branch_code' => 'BR-01', 'status' => 'active']
            );

            $opsDept = Department::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'إدارة العمليات والخدمات الحكومية'],
                ['department_code' => 'DEPT-OPS', 'status' => 'active']
            );

            $finDept = Department::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'الشؤون المالية والخزينة'],
                ['department_code' => 'DEPT-FIN', 'status' => 'active']
            );

            $titleOpsManager = JobTitle::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'مدير عمليات'],
                ['job_code' => 'JOB-OM', 'status' => 'active']
            );

            $titleSupervisor = JobTitle::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'مشرف متابعة ومطابقة'],
                ['job_code' => 'JOB-SUP', 'status' => 'active']
            );

            $titleAccountant = JobTitle::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'محاسب مالي أول'],
                ['job_code' => 'JOB-ACC', 'status' => 'active']
            );

            $titleOfficer = JobTitle::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'أخصائي خدمات ومعاملات'],
                ['job_code' => 'JOB-OFF', 'status' => 'active']
            );

            // 4. Create testing users with appropriate roles
            $usersMeta = [
                'admin' => [
                    'name' => 'مدير النظام الرئيسي',
                    'email' => 'admin@admin.com',
                    'role' => 'مدير عام',
                    'type' => UserType::SUPER_ADMIN,
                    'dept' => $opsDept,
                    'job' => $titleOpsManager,
                    'is_saudi' => true,
                    'emp_no' => 'EMP-ADM-00',
                    'salary' => 25000,
                ],
                'ops_manager' => [
                    'name' => 'سلطان بن عبدالعزيز المنصور',
                    'email' => 'ops.manager@orcal.test',
                    'role' => 'مدير عمليات',
                    'type' => UserType::SAUDI_EMPLOYEE,
                    'dept' => $opsDept,
                    'job' => $titleOpsManager,
                    'is_saudi' => true,
                    'emp_no' => 'EMP-OPS-01',
                    'salary' => 18000,
                ],
                'ops_supervisor' => [
                    'name' => 'نايف بن خالد الدوسري',
                    'email' => 'ops.supervisor@orcal.test',
                    'role' => 'مشرف عمليات',
                    'type' => UserType::SUPERVISOR,
                    'dept' => $opsDept,
                    'job' => $titleSupervisor,
                    'is_saudi' => true,
                    'emp_no' => 'EMP-OPS-02',
                    'salary' => 13500,
                ],
                'accountant' => [
                    'name' => 'عمر بن فاروق المحاسب',
                    'email' => 'finance@orcal.test',
                    'role' => 'محاسب مالي',
                    'type' => UserType::FOREIGN_EMPLOYEE,
                    'dept' => $finDept,
                    'job' => $titleAccountant,
                    'is_saudi' => false,
                    'emp_no' => 'EMP-FIN-01',
                    'salary' => 11000,
                ],
                'operator' => [
                    'name' => 'أحمد بن سعيد المنفذ',
                    'email' => 'operator@orcal.test',
                    'role' => 'موظف',
                    'type' => UserType::SAUDI_EMPLOYEE,
                    'dept' => $opsDept,
                    'job' => $titleOfficer,
                    'is_saudi' => true,
                    'emp_no' => 'EMP-SRV-01',
                    'salary' => 8500,
                ],
                'beneficiary' => [
                    'name' => 'خالد بن فهد المستفيد',
                    'email' => 'beneficiary@orcal.test',
                    'role' => 'موظف',
                    'type' => UserType::SAUDI_EMPLOYEE,
                    'dept' => $opsDept,
                    'job' => $titleOfficer,
                    'is_saudi' => true,
                    'emp_no' => 'EMP-BEN-01',
                    'salary' => 7000,
                ],
            ];

            $seededUsers = [];
            $createdEmployees = [];

            foreach ($usersMeta as $key => $meta) {
                $user = User::updateOrCreate(
                    ['email' => $meta['email']],
                    [
                        'name' => $meta['name'],
                        'password' => Hash::make('password'),
                        'user_type' => $meta['type'],
                        'status' => 'active',
                        'email_verified_at' => now(),
                    ]
                );

                $role = Role::firstOrCreate(['name' => $meta['role'], 'guard_name' => 'web']);
                $user->syncRoles([$role]);

                if ($key === 'admin') {
                    $superRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
                    $user->assignRole($superRole);
                }

                $employee = Employee::updateOrCreate(
                    ['employee_number' => $meta['emp_no']],
                    [
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'department_id' => $meta['dept']->id,
                        'job_title_id' => $meta['job']->id,
                        'employment_type' => 'full_time',
                        'hire_date' => '2024-01-01',
                        'basic_salary' => $meta['salary'],
                        'status' => 'active',
                    ]
                );

                EmployeeDetail::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'nationality' => $meta['is_saudi'] ? 'saudi' : 'egyptian',
                        'national_id' => $meta['is_saudi'] ? ('10'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT)) : null,
                        'iqama_number' => ! $meta['is_saudi'] ? ('20'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT)) : null,
                    ]
                );

                $createdEmployees[$key] = $employee;

                FilamentNotification::make()
                    ->title(__('services_tracking.notifications.welcome_title'))
                    ->body(__('services_tracking.notifications.welcome_body', ['role' => $meta['role']]))
                    ->icon('heroicon-o-check-circle')
                    ->status('success')
                    ->sendToDatabase($user);

                $seededUsers[] = [
                    'key' => $key,
                    'role' => $meta['role'],
                    'name' => $meta['name'],
                    'email' => $meta['email'],
                    'password' => 'password',
                    'permissions_count' => $user->getAllPermissions()->count(),
                ];
            }

            // 5. Active supervision shift for supervisor testing
            $supervisorEmp = $createdEmployees['ops_supervisor'] ?? null;
            $operatorEmp = $createdEmployees['operator'] ?? null;
            if ($supervisorEmp && $operatorEmp) {
                \App\Models\SupervisionShift::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'supervisor_id' => $supervisorEmp->id,
                        'title' => 'نوبة إشراف ومتابعة العمليات الشهرية',
                    ],
                    [
                        'type' => 'supervision',
                        'assigned_employees' => [(int) $operatorEmp->id],
                        'start_date' => now()->startOfMonth()->toDateString(),
                        'end_date' => now()->endOfMonth()->toDateString(),
                        'status' => 'active',
                        'notes' => 'نوبة إشراف نشطة لتغطية متابعة أداء الموظف المنفذ',
                    ]
                );
            }

            // 6. Demonstration test customer
            \App\Models\Customer::updateOrCreate(
                ['commercial_registration_number' => '1010998877'],
                [
                    'company_id' => $company->id,
                    'name' => 'شركة الرواد للتجارة والمقاولات (عميل تجريبي)',
                    'customer_type' => 'company',
                    'phone' => '0551122334',
                    'tax_number' => '300099887700003',
                    'status' => 'active',
                ]
            );

            // 7. Seed diversified services catalog
            $servicesResult = (new ServicesCatalogSeeder)->run($company->id);

            // 8. Reset cached permissions
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            return [
                'company' => $company->trade_name,
                'branch' => $branch->name,
                'users' => $seededUsers,
                'services_count' => $servicesResult['created_count'] ?? 0,
                'status' => 'ready_for_testing',
            ];
        });
    }
}
