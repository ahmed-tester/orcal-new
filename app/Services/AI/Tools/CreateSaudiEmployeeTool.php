<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateSaudiEmployeeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_saudi_employee';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة موظف سعودي الجنسية فقط إلى النظام مع التحقق الصارم من الهوية الوطنية (تبدأ برقم 1 وتتكون من 10 أرقام).',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف السعودي الكامل'
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية الوطنية السعودية (10 أرقام وتبدأ برقم 1)'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم جوال الموظف'
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني للموظف (اختياري)'
                    ],
                    'basic_salary' => [
                        'type' => 'NUMBER',
                        'description' => 'الراتب الأساسي الشهري بالريال السعودي'
                    ],
                    'city' => [
                        'type' => 'STRING',
                        'description' => 'المدينة (اختياري)'
                    ],
                    'birth_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ الميلاد بصيغة YYYY-MM-DD (اختياري)'
                    ]
                ],
                'required' => ['name', 'national_id']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $name = trim($arguments['name'] ?? '');
        $nationalId = preg_replace('/[^0-9]/', '', $arguments['national_id'] ?? '');
        $phone = trim($arguments['phone'] ?? '');
        $email = trim($arguments['email'] ?? '');
        $salary = isset($arguments['basic_salary']) ? (float)$arguments['basic_salary'] : 0.0;
        $city = trim($arguments['city'] ?? '');
        $birthDate = trim($arguments['birth_date'] ?? '');

        // 1. فحص الاسم
        if (empty($name)) {
            return ['success' => false, 'message' => 'اسم الموظف السعودي مطلوب.'];
        }

        // 2. فاليديشن رقم الهوية الوطنية السعودية (10 أرقام وتبدأ بـ 1)
        if (strlen($nationalId) !== 10) {
            return [
                'success' => false,
                'message' => "خطأ في الهوية ({$name}): رقم الهوية الوطنية يجب أن يتكون من 10 أرقام تماماً.",
            ];
        }

        if (str_starts_with($nationalId, '2')) {
            return [
                'success' => false,
                'message' => "مرفوض ({$name}): رقم الهوية ({$nationalId}) يبدأ بـ (2) وهي هوية مقيم، ولا يمكن إضافته كموظف سعودي!",
            ];
        }

        if (!str_starts_with($nationalId, '1')) {
            return [
                'success' => false,
                'message' => "خطأ ({$name}): الهوية الوطنية السعودية يجب أن تبدأ برقم (1).",
            ];
        }

        // 3. التحقق من عدم تكرار الهوية
        $exists = EmployeeDetail::where('national_id', $nationalId)->exists();
        if ($exists) {
            return [
                'success' => false,
                'message' => "تنبيه (مكرر): رقم الهوية ({$nationalId}) مسجل مسبقاً في النظام للموظف ({$name})، تم تجاهله لمنع التكرار.",
            ];
        }

        // 4. التحقق من عدم تكرار رقم الجوال إذا وجد
        if (!empty($phone) && User::where('phone', $phone)->exists()) {
            return [
                'success' => false,
                'message' => "تنبيه (مكرر): رقم الجوال ({$phone}) مسجل مسبقاً في النظام للموظف ({$name}).",
            ];
        }

        try {
            return DB::transaction(function () use ($name, $nationalId, $phone, $email, $salary, $city, $birthDate) {
                $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

                $userEmail = !empty($email) ? $email : ('sa_emp_' . Str::random(8) . '@company.local');
                $user = User::create([
                    'name' => $name,
                    'email' => $userEmail,
                    'phone' => $phone ?: null,
                    'password' => Hash::make(Str::random(16)),
                    'user_type' => UserType::SAUDI_EMPLOYEE,
                    'status' => 'active',
                ]);

                $employee = Employee::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'basic_salary' => $salary,
                    'status' => GeneralStatus::ACTIVE,
                ]);

                EmployeeDetail::create([
                    'employee_id' => $employee->id,
                    'national_id' => $nationalId,
                    'nationality' => 'سعودي',
                    'city' => $city ?: null,
                    'birth_date' => $birthDate ?: null,
                ]);

                return [
                    'success' => true,
                    'message' => "تم إضافة الموظف السعودي ({$name}) بنجاح برقم وظيفي {$employee->employee_number}",
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'type' => 'saudi',
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "تعذر حفظ الموظف السعودي ({$name}): " . $e->getMessage(),
            ];
        }
    }
}
