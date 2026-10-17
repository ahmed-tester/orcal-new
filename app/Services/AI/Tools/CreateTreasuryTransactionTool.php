<?php

namespace App\Services\AI\Tools;

use App\Enums\PaymentItemCategory;
use App\Enums\TreasuryTransactionType;
use App\Models\Company;
use App\Models\PaymentCollectionItem;
use App\Models\TreasuryTransaction;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateTreasuryTransactionTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_treasury_transaction';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة حركة خزينة أو سند قبض/صرف مالي إلى النظام مع منع التكرار.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'transaction_type' => [
                        'type' => 'STRING',
                        'description' => 'نوع الحركة: incoming (سند قبض / إيداع) أو outgoing (سند صرف / مدفوعات)'
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المالي بالريال السعودي (يجب أن يكون أكبر من الصفر)'
                    ],
                    'recipient_or_payer' => [
                        'type' => 'STRING',
                        'description' => 'اسم المستلم أو المدفوع له أو المودع'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'البيان أو تفاصيل سبب الصرف/القبض'
                    ],
                    'transaction_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ الحركة بصيغة YYYY-MM-DD (اختياري، الافتراضي تاريخ اليوم)'
                    ]
                ],
                'required' => ['amount', 'transaction_type', 'recipient_or_payer']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $amount = (float)($arguments['amount'] ?? 0);
            $type = strtolower(trim($arguments['transaction_type'] ?? 'incoming'));
            $recipientOrPayer = trim($arguments['recipient_or_payer'] ?? '');
            $description = trim($arguments['description'] ?? '');
            $date = trim($arguments['transaction_date'] ?? date('Y-m-d'));

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'يجب أن يكون المبلغ المالي أكبر من الصفر.'];
            }

            if (empty($recipientOrPayer)) {
                return ['success' => false, 'message' => 'اسم المستلم أو الدافع مطلوب لسند الخزينة.'];
            }

            $transactionType = in_array($type, ['outgoing', 'صرف', 'expense', 'payment'])
                ? TreasuryTransactionType::OUTGOING
                : TreasuryTransactionType::INCOMING;

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $typeName = $transactionType === TreasuryTransactionType::INCOMING ? 'سند قبض' : 'سند صرف';

            // فحص منع التكرار (نفس الشركة + نفس النوع + نفس المبلغ + نفس المستلم + نفس التاريخ)
            $duplicate = TreasuryTransaction::where('company_id', $companyId)
                ->where('transaction_type', $transactionType)
                ->where('amount', $amount)
                ->where('recipient_or_payer', $recipientOrPayer)
                ->whereDate('transaction_date', $date ?: date('Y-m-d'))
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): {$typeName} بمبلغ " . number_format($amount, 2) . " ر.س لصالح ({$recipientOrPayer}) مسجل مسبقاً برقم #{$duplicate->transaction_number}، تم تجاهله لمنع التكرار.",
                ];
            }

            // العثور على بند دفع/تحصيل أو إنشاؤه افتراضياً
            $paymentItem = PaymentCollectionItem::where('company_id', $companyId)->first();
            if (!$paymentItem) {
                $paymentItem = PaymentCollectionItem::create([
                    'company_id' => $companyId,
                    'main_item' => $transactionType === TreasuryTransactionType::INCOMING 
                        ? PaymentItemCategory::SERVICES 
                        : PaymentItemCategory::GENERAL_EXPENSES,
                    'sub_item' => $description ?: 'بند عام للعمليات',
                    'service_price' => 0,
                ]);
            }

            $transaction = TreasuryTransaction::create([
                'company_id' => $companyId,
                'transaction_type' => $transactionType,
                'payment_collection_item_id' => $paymentItem->id,
                'amount' => $amount,
                'recipient_or_payer' => $recipientOrPayer,
                'description' => $description ?: ($transactionType === TreasuryTransactionType::INCOMING ? 'سند قبض' : 'سند صرف'),
                'transaction_date' => $date ?: date('Y-m-d'),
                'executed_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة {$typeName} بمبلغ " . number_format($amount, 2) . " ر.س لصالح ({$recipientOrPayer}) برقم #{$transaction->transaction_number}",
                'transaction_id' => $transaction->id,
                'amount' => $amount,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ حركة الخزينة: ' . $e->getMessage(),
            ];
        }
    }
}
