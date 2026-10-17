<?php

namespace App\Services\AI\Tools;

use App\Models\LoanFundLoan;
use App\Models\LoanFundLoanPayment;
use App\Models\LoanFundMember;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateLoanFundPaymentTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_loan_fund_payment';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'تسجيل إيصال سداد أو دفعة من أقساط قروض الصندوق لمشترك مع تحديث الرصيد المتبقي للقرض.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'member_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم المشترك أو رقم عضويته أو رقم القرض'
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد بالريال السعودي'
                    ],
                    'payment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ السداد بصيغة YYYY-MM-DD (الافتراضي اليوم)'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات السداد'
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

            // البحث عن القرض المفتوح للمشترك
            $loan = LoanFundLoan::where('is_closed', false)
                ->where(function ($q) use ($ident) {
                    $q->where('id', $ident)
                      ->orWhere('loan_number', $ident)
                      ->orWhereHas('member', fn($m) => $m->where('member_name', 'like', "%{$ident}%")->orWhere('member_number', $ident)->orWhere('national_id', $ident));
                })->first();

            if (!$loan) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على قرض مفتوح ومستحق للمشترك ({$ident}) لسداد القسط.",
                ];
            }

            $payDate = !empty($arguments['payment_date']) ? $arguments['payment_date'] : date('Y-m-d');
            $newPaid = (float)$loan->paid_amount + $amount;
            $newRemaining = max(0, (float)$loan->loan_amount - $newPaid);

            $payment = LoanFundLoanPayment::create([
                'loan_fund_loan_id'   => $loan->id,
                'loan_fund_member_id' => $loan->loan_fund_member_id,
                'installment_date'    => $payDate,
                'scheduled_amount'    => $loan->monthly_installment,
                'collected_amount'    => $amount,
                'remaining_balance'   => $newRemaining,
                'is_closed'           => ($newRemaining <= 0),
                'received_by'         => auth()->id(),
                'notes'               => $arguments['notes'] ?? 'استيراد ذكي — سداد قسط صندوق',
            ]);

            // تحديث القرض
            $loan->paid_amount = $newPaid;
            $loan->remaining_amount = $newRemaining;
            if ($newRemaining <= 0) {
                $loan->is_closed = true;
                $loan->status = 'completed';
                $loan->closure_date = $payDate;
            }
            $loan->save();

            $memberName = $loan->member?->member_name ?? "#{$loan->loan_fund_member_id}";

            return [
                'success' => true,
                'message' => "تم تسجيل سداد قسط بمبلغ (" . number_format($amount, 2) . " ر.س) للمشترك ({$memberName}) بنجاح بسند #{$payment->voucher_number}. المتبقي من القرض: " . number_format($newRemaining, 2) . " ر.س",
                'payment_id' => $payment->id,
                'name' => "سداد قسط #{$payment->voucher_number}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ سداد قسط الصندوق: ' . $e->getMessage(),
            ];
        }
    }
}
