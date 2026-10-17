<?php

namespace App\Services\AI\Tools;

use App\Enums\PaymentItemCategory;
use App\Models\Company;
use App\Models\PaymentCollectionItem;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreatePaymentCollectionItemTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_payment_collection_item';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج بند دفع أو تحصيل جديد في الدليل المالي مع تصنيفه وسعره التقديري.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'sub_item' => [
                        'type' => 'STRING',
                        'description' => 'اسم البند الفرعي (مثال: إيجار فرع، صيانة أجهزة، سداد فواتير)'
                    ],
                    'main_item' => [
                        'type' => 'STRING',
                        'description' => 'التصنيف الرئيسي للبند (مثال: general_expenses, services, rent, maintenance, other)'
                    ],
                    'service_price' => [
                        'type' => 'NUMBER',
                        'description' => 'السعر أو القيمة المقدرة للبند بالريال السعودي (اختياري)'
                    ]
                ],
                'required' => ['sub_item']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $subItem = trim($arguments['sub_item'] ?? '');
            if (empty($subItem)) {
                return ['success' => false, 'message' => 'اسم بند الدفع/التحصيل مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $mainItemInput = strtolower(trim($arguments['main_item'] ?? 'general_expenses'));
            $mainItem = PaymentItemCategory::tryFrom($mainItemInput) ?? PaymentItemCategory::GENERAL_EXPENSES;

            // فحص منع التكرار
            $existing = PaymentCollectionItem::where('company_id', $companyId)
                ->where('sub_item', $subItem)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): البند ({$subItem}) مسجل مسبقاً برقم #{$existing->transaction_number}، تم تخطيه لمنع التكرار.",
                ];
            }

            $item = PaymentCollectionItem::create([
                'company_id'    => $companyId,
                'main_item'     => $mainItem,
                'sub_item'      => $subItem,
                'service_price' => (float)($arguments['service_price'] ?? 0),
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة بند الدفع والتحصيل ({$subItem}) بنجاح برقم #{$item->transaction_number}",
                'item_id' => $item->id,
                'name'    => $subItem,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ بند الدفع والتحصيل: ' . $e->getMessage(),
            ];
        }
    }
}
