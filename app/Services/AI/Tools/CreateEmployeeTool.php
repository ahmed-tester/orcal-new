<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;
use App\Services\CompanyFormService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateEmployeeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_employee_record';
    }

    /**
     * الواجهة التي يراها Gemini فقط (بدون أي تفاصيل عن الداتابيز أو الكود الداخلي)
     */
    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة موظف جديد إلى النظام بناءً على البيانات المستخرجة من الملف',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'الاسم الكامل للموظف باللغة العربية (أو الاسم الأساسي)',
                    ],
                    'name_en' => [
                        'type' => 'STRING',
                        'description' => 'الاسم الكامل للموظف باللغة الإنجليزية إن وُجد في الملف (اختياري)',
                    ],
                    'company_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المؤسسة أو الشركة التابع لها الموظف إن وُجدت في الملف (اختياري)',
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية الوطنية أو رقم الإقامة',
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم هاتف الموظف',
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني للموظف (اختياري)',
                    ],
                    'basic_salary' => [
                        'type' => 'NUMBER',
                        'description' => 'الراتب الأساسي الشهري (اختياري)',
                    ],
                    'nationality' => [
                        'type' => 'STRING',
                        'description' => 'جنسية الموظف (مثال: سعودي، مصري... اختياري)',
                    ],
                    'iqama_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم الإقامة إذا كان موظفاً غير سعودي (اختياري)',
                    ],
                ],
                'required' => ['name'],
            ],
        ];
    }

    /**
     * التنفيذ الحقيقي والآمن داخل السيرفر (مخفي ومحمي تماماً)
     */
    public function execute(array $arguments): array
    {
        $name = trim($arguments['name'] ?? '');
        $nameEn = trim($arguments['name_en'] ?? '');
        $nationalId = trim($arguments['national_id'] ?? '');
        $phone = trim($arguments['phone'] ?? '');
        $email = trim($arguments['email'] ?? '');
        $salary = isset($arguments['basic_salary']) ? (float) $arguments['basic_salary'] : 0.0;
        $nationality = trim($arguments['nationality'] ?? '');
        $iqamaNumber = trim($arguments['iqama_number'] ?? '');

        // Always split and clean bilingual names (handles attached texts like "MOHAMMED SAHIDمحمد شاهد")
        $bilingual = EmployeeNameMatcher::splitBilingualName($name);
        if ($bilingual['arabic'] !== '' && $bilingual['latin'] !== '') {
            $name = $bilingual['arabic'];
            if (empty($nameEn)) {
                $nameEn = $bilingual['latin'];
            }
        } elseif ($bilingual['arabic'] === '' && $bilingual['latin'] !== '' && empty($nameEn)) {
            $nameEn = $bilingual['latin'];
        }

        // English-only rows: keep the Latin name as the primary user name
        // instead of rejecting the whole row.
        if ($name === '' && $nameEn !== '') {
            $name = $nameEn;
        }

        // Clean $nameEn if it accidentally contained Arabic letters
        if (! empty($nameEn)) {
            $enBilingual = EmployeeNameMatcher::splitBilingualName($nameEn);
            if ($enBilingual['latin'] !== '') {
                $nameEn = $enBilingual['latin'];
            }
        }

        if (empty($name)) {
            return [
                'success' => false,
                'message' => 'اسم الموظف مطلوب ولا يمكن تركه فارغاً.',
            ];
        }

        $companyName = trim($arguments['company_name'] ?? '');
        $fallbackCompanyId = ! empty($arguments['company_id']) ? (int) $arguments['company_id'] : null;
        $companyRes = CompanyFormService::resolveOrCreateCompanyByName($companyName, $fallbackCompanyId);
        $companyId = $companyRes['company_id'];

        return DB::transaction(function () use ($name, $nameEn, $nationalId, $phone, $email, $salary, $nationality, $iqamaNumber, $companyId) {
            // 1. تحديد نوع الموظف بذكاء (سعودي أم غير سعودي)
            $userType = UserType::detect(
                nationalId: $nationalId ?: null,
                iqamaNumber: $iqamaNumber ?: null,
                nationality: $nationality ?: null,
                name: $name
            );
            $isSaudi = ($userType === UserType::SAUDI_EMPLOYEE);

            $userEmail = ! empty($email) ? $email : ('emp_'.Str::random(8).'@company.local');
            $user = User::create([
                'name' => $name,
                'email' => $userEmail,
                'phone' => $phone ?: null,
                'password' => Hash::make(Str::random(16)),
                'user_type' => $userType,
                'status' => 'active',
            ]);

            // 2. إنشاء سجل الموظف
            $employee = Employee::create([
                'user_id' => $user->id,
                'company_id' => $companyId,
                'basic_salary' => $salary,
                'status' => GeneralStatus::ACTIVE,
                'metadata' => ! empty($nameEn) ? ['name_en' => $nameEn] : null,
            ]);

            // 3. إنشاء تفاصيل الموظف (الهوية / الإقامة / الجنسية)
            EmployeeDetail::create([
                'employee_id' => $employee->id,
                'national_id' => $isSaudi ? ($nationalId ?: null) : null,
                'iqama_number' => ! $isSaudi ? ($iqamaNumber ?: ($nationalId ?: null)) : null,
                'nationality' => $nationality ?: ($isSaudi ? 'سعودي' : null),
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة الموظف ({$name}) بنجاح برقم وظيفي {$employee->employee_number}",
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'name' => $name,
            ];
        });
    }
}
