<?php

namespace App\Services\AI\Tools;

use App\Enums\FundPaymentStatus;
use App\Models\RoscaFundMember;
use App\Models\RoscaFundPayment;
use App\Services\AI\Contracts\GeminiToolInterface;
use Carbon\Carbon;

class CreateRoscaPaymentTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_rosca_payment';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'تسجيل سداد دفعة أو قسط شهري لمشترك في الجمعية المالية.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'member_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم المشترك أو رقم عضويته أو هويته في الجمعية المالية'
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد بالريال السعودي'
                    ],
                    'period_month' => [
                        'type' => 'STRING',
                        'description' => 'شهر القسط أو الدفعة بصيغة YYYY-MM (مثال: 2026-10)'
                    ],
                    'payment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ السداد الفعلي بصيغة YYYY-MM-DD (الافتراضي اليوم)'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات الدفعة'
                    ]
                ],
                'required' => ['member_identifier', 'amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $ident = trim($arguments['member_identifier'] ?? '');
            $amount = (float)($arguments['amount'] ?? 0);

            if (empty($ident) || $amount <= 0) {
                return ['success' => false, 'message' => 'بيانات المشترك ومبلغ السداد مطلوبان.'];
            }

            $member = RoscaFundMember::where(function ($q) use ($ident) {
                $q->where('id', $ident)
                  ->orWhere('member_number', $ident)
                  ->orWhere('member_name', 'like', "%{$ident}%")
                  ->orWhere('national_id', $ident);
            })->first();

            if (!$member) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على المشترك ({$ident}) في الجمعيات المالية.",
                ];
            }

            $monthDate = !empty($arguments['period_month']) ? Carbon::parse($arguments['period_month'])->startOfMonth()->toDateString() : date('Y-m-01');
            $payDate = !empty($arguments['payment_date']) ? $arguments['payment_date'] : date('Y-m-d');

            $payment = RoscaFundPayment::create([
                'rosca_fund_id'        => $member->rosca_fund_id,
                'rosca_fund_member_id' => $member->id,
                'period_month'         => $monthDate,
                'scheduled_amount'     => (float)($member->share_amount ?? $amount),
                'paid_amount'          => $amount,
                'payment_status'       => FundPaymentStatus::PAID,
                'payment_date'         => $payDate,
                'received_by'          => auth()->id(),
                'notes'                => $arguments['notes'] ?? 'استيراد ذكي — قسط جمعية',
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل سداد قسط جمعية بمبلغ (" . number_format($amount, 2) . " ر.س) للمشترك ({$member->member_name}) بنجاح عن شهر (" . date('Y-m', strtotime($monthDate)) . ").",
                'payment_id' => $payment->id,
                'name' => "سداد قسط: {$member->member_name}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ سداد قسط الجمعية: ' . $e->getMessage(),
            ];
        }
    }
}
