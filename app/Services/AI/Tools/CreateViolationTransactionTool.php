<?php

namespace App\Services\AI\Tools;

use App\Enums\ViolationTransactionType;
use App\Models\Company;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateViolationTransactionTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_transaction';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج حركة أو سند في صندوق تحصيل المخالفات (قبض تحصيل غرامة / صرف تسوية).',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'مبلغ السند بالريال السعودي'
                    ],
                    'violation_identifier' => [
                        'type' => 'STRING',
                        'description' => 'رقم المخالفة أو رقم السند المرتبط بها'
                    ],
                    'transaction_type' => [
                        'type' => 'STRING',
                        'description' => 'نوع الحركة: incoming (سند قبض/تحصيل) أو outgoing (سند صرف)'
                    ],
                    'transaction_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ السند بصيغة YYYY-MM-DD (الافتراضي اليوم)'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'البيان أو سبب السند'
                    ]
                ],
                'required' => ['amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $amount = (float)($arguments['amount'] ?? 0);
            if ($amount <= 0) {
                return ['success' => false, 'message' => 'مبلغ حركة المخالفة يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $violationId = null;
            if (!empty($arguments['violation_identifier'])) {
                $ident = trim($arguments['violation_identifier']);
                $viol = Violation::where('company_id', $companyId)
                    ->where(function ($q) use ($ident) {
                        $q->where('id', $ident)
                          ->orWhere('violation_number', $ident);
                    })->first();
                $violationId = $viol?->id;
            }

            $typeInput = strtolower(trim($arguments['transaction_type'] ?? 'incoming'));
            $txType = ($typeInput === 'outgoing') ? ViolationTransactionType::OUTGOING : ViolationTransactionType::INCOMING;
            $txDate = !empty($arguments['transaction_date']) ? $arguments['transaction_date'] : date('Y-m-d');

            $tx = ViolationTransaction::create([
                'company_id'       => $companyId,
                'violation_id'     => $violationId,
                'transaction_type' => $txType,
                'amount'           => $amount,
                'transaction_date' => $txDate,
                'description'      => $arguments['description'] ?? 'استيراد ذكي — سند صندوق المخالفات',
                'executed_by'      => auth()->id(),
            ]);

            $typeLabel = ($txType === ViolationTransactionType::INCOMING) ? 'قبض تحصيل' : 'صرف';

            return [
                'success' => true,
                'message' => "تم تسجيل سند {$typeLabel} في صندوق المخالفات بمبلغ (" . number_format($amount, 2) . " ر.س) بنجاح برقم #{$tx->voucher_number}",
                'transaction_id' => $tx->id,
                'name' => "سند #{$tx->voucher_number}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ حركة صندوق المخالفات: ' . $e->getMessage(),
            ];
        }
    }
}
