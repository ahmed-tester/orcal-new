<?php

namespace App\Services\AI\Tools;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Company;
use App\Models\Violation;
use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateViolationTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مخالفة نظامية أو غرامة مالية على المنشأة مع تحديد جهة الإصدار والمبلغ ومواعيد الاعتراض والسداد.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'external_violation_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم المخالفة الخارجي أو رقم القرار من الجهة المصدرة'
                    ],
                    'violation_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'قيمة الغرامة أو المخالفة بالريال السعودي'
                    ],
                    'issuing_authority' => [
                        'type' => 'STRING',
                        'description' => 'الجهة المصدرة (مثال: أمانة الرياض، وزارة الموارد البشرية، الزكاة والضريبة، المرور)'
                    ],
                    'violation_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ تحرير المخالفة بصيغة YYYY-MM-DD'
                    ],
                    'category_name' => [
                        'type' => 'STRING',
                        'description' => 'تصنيف المخالفة (مثال: بلديات، عمل وعمال، سلامة وصحة، مرور)'
                    ],
                    'type_name' => [
                        'type' => 'STRING',
                        'description' => 'نوع المخالفة المحدد (مثال: عدم تجديد رخصة، مخالفة اشتراطات بيئية)'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'نص أو وصف المخالفة'
                    ],
                    'paid_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد إن وجد'
                    ]
                ],
                'required' => ['violation_amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $amount = (float)($arguments['violation_amount'] ?? 0);
            $externalNumber = trim($arguments['external_violation_number'] ?? '');
            $authority = trim($arguments['issuing_authority'] ?? 'جهة حكومية');
            $date = trim($arguments['violation_date'] ?? date('Y-m-d'));
            $categoryName = trim($arguments['category_name'] ?? 'مخالفات عامة');
            $typeName = trim($arguments['type_name'] ?? 'مخالفة نظامية');
            $description = trim($arguments['description'] ?? '');
            $paid = (float)($arguments['paid_amount'] ?? 0);

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'مبلغ المخالفة يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // فحص منع التكرار بناءً على الرقم الخارجي
            if (!empty($externalNumber)) {
                $duplicate = Violation::where('company_id', $companyId)
                    ->where('external_violation_number', $externalNumber)
                    ->first();
                if ($duplicate) {
                    return [
                        'success' => false,
                        'message' => "تنبيه (مكرر): مخالفة برقم خارجي ({$externalNumber}) مسجلة مسبقاً برقم #{$duplicate->violation_number}، تم تخطيها.",
                    ];
                }
            }

            // العثور على التصنيف أو إنشاؤه
            $category = ViolationCategory::firstOrCreate(
                ['name' => $categoryName ?: 'مخالفات عامة'],
                ['code' => 'GEN', 'status' => 'active']
            );

            // العثور على النوع
            $type = ViolationType::firstOrCreate(
                ['name' => $typeName ?: 'مخالفة نظامية', 'violation_category_id' => $category->id],
                ['type_code' => 'VIO', 'status' => 'active']
            );

            $remaining = max(0, $amount - $paid);
            $paymentStatus = $paid >= $amount ? ViolationPaymentStatus::PAID : ($paid > 0 ? ViolationPaymentStatus::PARTIAL : ViolationPaymentStatus::UNPAID);

            $violation = Violation::create([
                'company_id' => $companyId,
                'violation_category_id' => $category->id,
                'violation_type_id' => $type->id,
                'external_violation_number' => $externalNumber ?: null,
                'violation_date' => $date,
                'issuing_authority' => $authority,
                'violation_amount' => $amount,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'description' => $description ?: 'استيراد ذكي — مخالفة وغرامة',
                'stage' => ViolationStage::DISCOVERY,
                'payment_status' => $paymentStatus,
                'recorded_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل مخالفة جديدة بنجاح بمبلغ " . number_format($amount, 2) . " ر.س صادرة من ({$authority}) برقم #{$violation->violation_number}",
                'record_name' => "مخالفة #{$violation->violation_number}",
                'details' => [
                    'amount' => number_format($amount, 2) . ' ر.س',
                    'authority' => $authority,
                    'date' => $date,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المخالفة: ' . $e->getMessage(),
            ];
        }
    }
}
