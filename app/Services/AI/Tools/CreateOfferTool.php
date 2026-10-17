<?php

namespace App\Services\AI\Tools;

use App\Enums\OfferStatus;
use App\Enums\OfferType;
use App\Enums\PricingType;
use App\Enums\WorkScope;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Offer;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateOfferTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_offer_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج عرض سعر أو مقترح تجاري لعميل مع تحديد الموضوع ونطاق العمل والمدة.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'subject' => [
                        'type' => 'STRING',
                        'description' => 'موضوع أو عنوان عرض السعر'
                    ],
                    'customer_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم العميل أو الجهة الموجه لها العرض'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'تفاصيل العرض'
                    ],
                    'execution_duration' => [
                        'type' => 'STRING',
                        'description' => 'مدة التنفيذ المقترحة (مثال: 30 يوم، 3 أشهر)'
                    ],
                    'offer_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ تقديم العرض بصيغة YYYY-MM-DD'
                    ]
                ],
                'required' => ['subject', 'customer_name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $subject = trim($arguments['subject'] ?? '');
            $customerName = trim($arguments['customer_name'] ?? '');
            $description = trim($arguments['description'] ?? '');
            $duration = trim($arguments['execution_duration'] ?? '30 يوم');
            $date = trim($arguments['offer_date'] ?? date('Y-m-d'));

            if (empty($subject) || empty($customerName)) {
                return ['success' => false, 'message' => 'موضوع العرض واسم العميل مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $customer = Customer::firstOrCreate(
                ['company_id' => $companyId, 'name' => $customerName],
                ['status' => 'active']
            );

            // فحص منع التكرار
            $existing = Offer::where('company_id', $companyId)
                ->where('customer_id', $customer->id)
                ->where('subject', $subject)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): عرض سعر بموضوع ({$subject}) للعميل ({$customerName}) مسجل مسبقاً برقم #{$existing->offer_number}، تم تخطيه.",
                ];
            }

            $offer = Offer::create([
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'offer_type' => OfferType::FORMAL,
                'subject' => $subject,
                'description' => $description ?: $subject,
                'work_scope' => WorkScope::COMPLETE,
                'pricing_type' => PricingType::TOTAL,
                'execution_duration' => $duration,
                'offer_date' => $date,
                'status' => OfferStatus::DRAFT,
                'created_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم إنشاء عرض السعر ({$subject}) للعميل ({$customerName}) بنجاح برقم #{$offer->offer_number}",
                'record_name' => "عرض #{$offer->offer_number}",
                'details' => [
                    'subject' => $subject,
                    'customer' => $customerName,
                    'date' => $date,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ عرض السعر: ' . $e->getMessage(),
            ];
        }
    }
}
