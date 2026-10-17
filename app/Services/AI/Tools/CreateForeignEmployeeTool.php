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

class CreateForeignEmployeeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_foreign_employee';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة موظف مقيم (غير سعودي) إلى النظام مع التحقق الصارم من رقم الإقامة (يبدأ برقم 2 ويتكون من 10 أرقام) والجنسية غير السعودية.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف المقيم الكامل'
                    ],
                    'iqama_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم الإقامة في السعودية (10 أرقام وتبدأ برقم 2)'
                    ],
                    'nationality' => [
                        'type' => 'STRING',
                        'description' => 'جنسية الموظف المقيم (مثال: مصري، هندي، يمني، فلبيني، باكستاني... ولا يمكن أن تكون سعودي)'
                    ],
                    'passport_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم جواز السفر (اختياري)'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم جوال الموظف'
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني (اختياري)'
                    ],
                    'basic_salary' => [
                        'type' => 'NUMBER',
                        'description' => 'الراتب الأساسي الشهري بالريال السعودي'
                    ],
                    'iqama_expiry' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ انتهاء الإقامة بصيغة YYYY-MM-DD (اختياري)'
                    ],
                    'work_permit_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم رخصة العمل (اختياري)'
                    ],
                    'sponsor_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الكفيل أو المنشأة السابقة (اختياري)'
                    ]
                ],
                'required' => ['name', 'iqama_number', 'nationality']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        $name = trim($arguments['name'] ?? '');
        $iqamaNumber = preg_replace('/[^0-9]/', '', $arguments['iqama_number'] ?? '');
        $nationality = trim($arguments['nationality'] ?? '');
        $passportNumber = trim($arguments['passport_number'] ?? '');
        $phone = trim($arguments['phone'] ?? '');
        $email = trim($arguments['email'] ?? '');
        $salary = isset($arguments['basic_salary']) ? (float)$arguments['basic_salary'] : 0.0;
        $iqamaExpiry = trim($arguments['iqama_expiry'] ?? '');
        $workPermitNumber = trim($arguments['work_permit_number'] ?? '');
        $sponsorName = trim($arguments['sponsor_name'] ?? '');

        // 1. فحص الاسم
        if (empty($name)) {
            return ['success' => false, 'message' => 'اسم الموظف المقيم مطلوب.'];
        }

        // 2. فحص الجنسية (يجب ألا تكون سعودي)
        if (empty($nationality)) {
            return ['success' => false, 'message' => "الجنسية مطلوبة للموظف المقيم ({$name})."];
        }

        if (in_array(mb_strtolower($nationality), ['سعودي', 'سعودية', 'saudi', 'saudi arabia', 'ksa'])) {
            return [
                'success' => false,
                'message' => "مرفوض ({$name}): تم تحديد الجنسية ({$nationality})، لا يمكن إضافة موظف سعودي في شاشة المقيمين!",
            ];
        }

        // 3. فاليديشن رقم الإقامة (10 أرقام وتبدأ بـ 2)
        if (strlen($iqamaNumber) !== 10) {
            return [
                'success' => false,
                'message' => "خطأ في الإقامة ({$name}): رقم الإقامة يجب أن يتكون من 10 أرقام تماماً.",
            ];
        }

        if (str_starts_with($iqamaNumber, '1')) {
            return [
                'success' => false,
                'message' => "مرفوض ({$name}): رقم الإقامة ({$iqamaNumber}) يبدأ بـ (1) وهو رقم هوية وطنية سعودية، ولا يمكن إضافته كمقيم!",
            ];
        }

        if (!str_starts_with($iqamaNumber, '2')) {
            return [
                'success' => false,
                'message' => "خطأ ({$name}): رقم إقامة المقيم يجب أن يبدأ برقم (2).",
            ];
        }

        // 4. فحص عدم التكرار برقم الإقامة
        $exists = EmployeeDetail::where('iqama_number', $iqamaNumber)->orWhere('national_id', $iqamaNumber)->exists();
        if ($exists) {
            return [
                'success' => false,
                'message' => "تنبيه (مكرر): رقم الإقامة ({$iqamaNumber}) مسجل مسبقاً في النظام للموظف ({$name})، تم تجاهله لمنع التكرار.",
            ];
        }

        // 5. فحص عدم التكرار برقم الجواز إذا وجد
        if (!empty($passportNumber) && EmployeeDetail::where('passport_number', $passportNumber)->exists()) {
            return [
                'success' => false,
                'message' => "تنبيه (مكرر): جواز السفر ({$passportNumber}) مسجل مسبقاً في النظام للموظف ({$name}).",
            ];
        }

        // 6. فحص عدم تكرار رقم الجوال
        if (!empty($phone) && User::where('phone', $phone)->exists()) {
            return [
                'success' => false,
                'message' => "تنبيه (مكرر): رقم الجوال ({$phone}) مسجل مسبقاً في النظام للموظف ({$name}).",
            ];
        }

        try {
            return DB::transaction(function () use ($name, $iqamaNumber, $nationality, $passportNumber, $phone, $email, $salary, $iqamaExpiry, $workPermitNumber, $sponsorName) {
                $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

                $userEmail = !empty($email) ? $email : ('for_emp_' . Str::random(8) . '@company.local');
                $user = User::create([
                    'name' => $name,
                    'email' => $userEmail,
                    'phone' => $phone ?: null,
                    'password' => Hash::make(Str::random(16)),
                    'user_type' => UserType::FOREIGN_EMPLOYEE,
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
                    'national_id' => $iqamaNumber,
                    'iqama_number' => $iqamaNumber,
                    'nationality' => $nationality,
                    'passport_number' => $passportNumber ?: null,
                    'iqama_expiry' => $iqamaExpiry ?: null,
                    'work_permit_number' => $workPermitNumber ?: null,
                    'sponsor_name' => $sponsorName ?: null,
                ]);

                return [
                    'success' => true,
                    'message' => "تم إضافة الموظف المقيم ({$name} - {$nationality}) بنجاح برقم وظيفي {$employee->employee_number}",
                    'employee_id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'type' => 'foreign',
                ];
            });
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "تعذر حفظ الموظف المقيم ({$name}): " . $e->getMessage(),
            ];
        }
    }
}
