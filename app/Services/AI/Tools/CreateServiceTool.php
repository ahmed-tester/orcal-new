<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\Service;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateServiceTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_service_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة خدمة جديدة وسعرها إلى قائمة أسعار الخدمات بالنظام مع منع التكرار.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الخدمة أو البند'
                    ],
                    'price' => [
                        'type' => 'NUMBER',
                        'description' => 'سعر الخدمة بالريال السعودي'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات أو تفاصيل إضافية عن الخدمة (اختياري)'
                    ]
                ],
                'required' => ['name', 'price']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            $price = (float)($arguments['price'] ?? 0);

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم الخدمة مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // فحص منع التكرار بنفس الاسم في نفس المؤسسة
            $duplicate = Service::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): الخدمة ({$name}) مسجلة مسبقاً بسعر " . number_format($duplicate->price, 2) . " ر.س، تم تجاهلها لمنع التكرار.",
                ];
            }

            $service = Service::create([
                'company_id' => $companyId,
                'name' => $name,
                'price' => $price,
                'notes' => $arguments['notes'] ?? null,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة الخدمة ({$name}) بسعر " . number_format($price, 2) . " ر.س",
                'service_id' => $service->id,
                'name' => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ الخدمة: ' . $e->getMessage(),
            ];
        }
    }
}
