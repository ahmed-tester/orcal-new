<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\Employee;
use App\Models\RoscaFund;
use App\Models\RoscaFundMember;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateRoscaFundMemberTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_rosca_fund_member';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مشترك أو عضو في الجمعية المالية (الدورية) مع تحديد دوره (ترتيب القبض) والقسط الشهري.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'full_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المشترك الكامل أو اسم الموظف'
                    ],
                    'rosca_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الجمعية المالية (اختياري)'
                    ],
                    'turn_order' => [
                        'type' => 'INTEGER',
                        'description' => 'ترتيب الدور أو شهر استلام الجمعية (الافتراضي 1)'
                    ],
                    'monthly_installment' => [
                        'type' => 'NUMBER',
                        'description' => 'قيمة السهم أو القسط الشهري بالريال السعودي'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم الجوال'
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية أو الإقامة'
                    ],
                    'paid_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد إن وجد'
                    ]
                ],
                'required' => ['full_name', 'monthly_installment']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['full_name'] ?? '');
            $roscaName = trim($arguments['rosca_name'] ?? '');
            $turnOrder = max(1, (int)($arguments['turn_order'] ?? 1));
            $monthly = (float)($arguments['monthly_installment'] ?? 0);
            $phone = trim($arguments['phone'] ?? '');
            $nationalId = trim($arguments['national_id'] ?? '');
            $paid = (float)($arguments['paid_amount'] ?? 0);

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم مشترك الجمعية مطلوب.'];
            }

            if ($monthly <= 0) {
                return ['success' => false, 'message' => 'قسط الجمعية الشهري يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // العثور على الجمعية أو إنشاؤها
            $rosca = null;
            if (!empty($roscaName)) {
                $rosca = RoscaFund::where('company_id', $companyId)->where('name', $roscaName)->first();
            }
            if (!$rosca) {
                $rosca = RoscaFund::firstOrCreate(
                    ['company_id' => $companyId, 'name' => $roscaName ?: 'الجمعية الدورية الشهرية'],
                    ['status' => 'active', 'members_count' => 10, 'monthly_installment' => $monthly, 'start_date' => date('Y-m-d')]
                );
            }

            $employee = Employee::where('company_id', $companyId)
                ->whereHas('user', fn($q) => $q->where('name', 'like', "%{$name}%"))
                ->first();

            // فحص التكرار
            $existing = RoscaFundMember::where('rosca_fund_id', $rosca->id)
                ->where(function ($q) use ($name, $turnOrder, $employee) {
                    $q->where('full_name', $name)->orWhere('turn_order', $turnOrder);
                    if ($employee) $q->orWhere('employee_id', $employee->id);
                })
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المشترك ({$name}) أو الدور رقم ({$turnOrder}) مسجل مسبقاً في ({$rosca->name})، تم تخطيه.",
                ];
            }

            $member = RoscaFundMember::create([
                'rosca_fund_id' => $rosca->id,
                'member_type' => $employee ? 'employee' : 'external',
                'employee_id' => $employee?->id,
                'full_name' => $name,
                'phone' => $phone ?: ($employee?->user?->phone),
                'national_id' => $nationalId ?: ($employee?->saudiDetail?->national_id ?? $employee?->foreignDetail?->iqama_number),
                'turn_order' => $turnOrder,
                'monthly_installment' => $monthly,
                'installments_count' => $rosca->members_count,
                'paid_amount' => $paid,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل المشترك ({$name}) بنجاح في الجمعية ({$rosca->name}) بالدور رقم ({$turnOrder})",
                'record_name' => $name,
                'details' => [
                    'rosca' => $rosca->name,
                    'turn_order' => "الدور {$turnOrder}",
                    'monthly_installment' => number_format($monthly, 2) . ' ر.س',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ مشترك الجمعية: ' . $e->getMessage(),
            ];
        }
    }
}
