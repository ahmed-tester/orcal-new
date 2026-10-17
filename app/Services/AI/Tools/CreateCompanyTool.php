<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateCompanyTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_company_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة مؤسسة أو شركة جديدة إلى النظام مع بيانات السجل التجاري والتراخيص، مع منع التكرار الصارم.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'trade_name' => [
                        'type' => 'STRING',
                        'description' => 'الاسم التجاري للمؤسسة أو الشركة (بالعربية)'
                    ],
                    'commercial_registration_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم السجل التجاري (10 أرقام)'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم هاتف أو جوال المؤسسة'
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني الرسمي للمؤسسة'
                    ],
                    'address' => [
                        'type' => 'STRING',
                        'description' => 'العنوان الوطني أو موقع المؤسسة'
                    ],
                    'municipality_license_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم رخصة البلدية (اختياري)'
                    ],
                    'civil_defense_license_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم تصريح الدفاع المدني (اختياري)'
                    ]
                ],
                'required' => ['trade_name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['trade_name'] ?? '');
            $cr = trim($arguments['commercial_registration_number'] ?? '');
            $phone = trim($arguments['phone'] ?? '');
            $email = trim($arguments['email'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم المنشأة أو الشركة مطلوب.'];
            }

            // 1. فحص منع التكرار برقم السجل التجاري
            if (!empty($cr) && Company::where('commercial_registration_number', $cr)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المؤسسة برقم السجل التجاري ({$cr}) مسجلة مسبقاً في النظام.",
                ];
            }

            // 2. فحص منع التكرار بالاسم التجاري المباشر
            if (Company::where('trade_name', $name)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المؤسسة باسم ({$name}) مسجلة مسبقاً في النظام ولم تتم إضافتها ثانية.",
                ];
            }

            // 3. فحص منع التكرار برقم الهاتف إذا كان مسجلاً
            if (!empty($phone) && Company::where('phone', $phone)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المؤسسة برقم الهاتف ({$phone}) مسجلة مسبقاً في النظام.",
                ];
            }

            // 4. فحص تطابق الاسم مع الأسماء السابقة بين القوسين
            $existingCompanies = Company::select('id', 'trade_name')->get();
            foreach ($existingCompanies as $existing) {
                $existingName = $existing->trade_name;
                if (
                    str_contains($existingName, $name) ||
                    str_contains($name, $existingName) ||
                    (str_contains($name, 'سابقاً') && str_contains($existingName, preg_replace('/.*سابقاً\s*[:)]?/u', '', $name)))
                ) {
                    return [
                        'success' => false,
                        'message' => "تنبيه (مكرر): المؤسسة ({$name}) مطابقة أو مرتبطة بمؤسسة موجودة بالفعل ({$existingName})، تم تجاهلها لمنع التكرار.",
                    ];
                }
            }

            $company = Company::create([
                'trade_name' => $name,
                'commercial_registration_number' => $cr ?: null,
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'address' => $arguments['address'] ?? null,
                'municipality_license_number' => $arguments['municipality_license_number'] ?? null,
                'civil_defense_license_number' => $arguments['civil_defense_license_number'] ?? null,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة المؤسسة/الشركة ({$name}) بنجاح برقم #{$company->id}",
                'company_id' => $company->id,
                'trade_name' => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ المنشأة: ' . $e->getMessage(),
            ];
        }
    }
}
