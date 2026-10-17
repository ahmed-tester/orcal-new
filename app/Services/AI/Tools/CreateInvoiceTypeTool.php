<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\InvoiceType;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateInvoiceTypeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_invoice_type';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج نوع فاتورة جديد في النظام (مثال: فاتورة خدمات تشغيلية، فاتورة صيانة، فاتورة استشارات).',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم أو مسمى نوع الفاتورة'
                    ],
                    'code' => [
                        'type' => 'STRING',
                        'description' => 'رمز أو كود النوع (مثال: INV-SERV، INV-MAINT)'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'وصف نوع الفاتورة'
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
            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم نوع الفاتورة مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $duplicate = InvoiceType::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): نوع الفاتورة ({$name}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                ];
            }

            $type = InvoiceType::create([
                'company_id'  => $companyId,
                'name'        => $name,
                'code'        => $arguments['code'] ?? null,
                'description' => $arguments['description'] ?? null,
                'status'      => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة نوع الفاتورة ({$name}) بنجاح.",
                'type_id' => $type->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ نوع الفاتورة: ' . $e->getMessage(),
            ];
        }
    }
}
