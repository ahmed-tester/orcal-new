<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateBranchTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_branch_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج فرع أو موقع عمل جديد للمنشأة.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الفرع (مثال: فرع الرياض، فرع جدة الرئيسي)'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'هاتف الفرع'
                    ],
                    'address' => [
                        'type' => 'STRING',
                        'description' => 'عنوان الفرع أو المدينة'
                    ],
                    'email' => [
                        'type' => 'STRING',
                        'description' => 'البريد الإلكتروني للفرع'
                    ]
                ],
                'required' => ['name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            $phone = trim($arguments['phone'] ?? '');
            $address = trim($arguments['address'] ?? '');
            $email = trim($arguments['email'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم الفرع مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $existing = Branch::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): الفرع ({$name}) مسجل مسبقاً بكود #{$existing->branch_code}، تم تخطيه.",
                ];
            }

            $branch = Branch::create([
                'company_id' => $companyId,
                'name' => $name,
                'phone' => $phone ?: null,
                'address' => $address ?: null,
                'email' => $email ?: null,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل الفرع ({$name}) بنجاح بكود #{$branch->branch_code}",
                'record_name' => $name,
                'details' => [
                    'branch_code' => $branch->branch_code,
                    'phone' => $phone ?: '--',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الفرع: ' . $e->getMessage(),
            ];
        }
    }
}
