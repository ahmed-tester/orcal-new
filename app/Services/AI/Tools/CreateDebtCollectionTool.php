<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\Debt;
use App\Models\DebtCollection;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateDebtCollectionTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_debt_collection';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'تسجيل سند تحصيل أو سداد دفعة من مديونية قائمة مع تحديث رصيد الدين المتبقي آلياً.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'debt_identifier' => [
                        'type' => 'STRING',
                        'description' => 'رقم الدين (DBT-...) أو اسم المدين / المستفيد'
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المحصل / المسدد بالريال السعودي'
                    ],
                    'collection_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ التحصيل بصيغة YYYY-MM-DD (الافتراضي اليوم)'
                    ],
                    'payment_method' => [
                        'type' => 'STRING',
                        'description' => 'طريقة السداد: cash (نقداً)، bank_transfer (تحويل بنكي)، cheque (شيك)'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'بيان أو تفاصيل سند التحصيل'
                    ]
                ],
                'required' => ['debt_identifier', 'amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $ident = trim($arguments['debt_identifier'] ?? '');
            $amount = (float)($arguments['amount'] ?? 0);

            if (empty($ident) || $amount <= 0) {
                return ['success' => false, 'message' => 'بيانات الدين ومبلغ التحصيل مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // البحث عن الدين المستحق
            $debt = Debt::where('company_id', $companyId)
                ->where('remaining_amount', '>', 0)
                ->where(function ($q) use ($ident) {
                    $q->where('id', $ident)
                      ->orWhere('debt_number', $ident)
                      ->orWhere('external_name', 'like', "%{$ident}%")
                      ->orWhereHas('employee.user', fn($u) => $u->where('name', 'like', "%{$ident}%"));
                })->first();

            if (!$debt) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على مديونية مفتوحة بها رصيد مستحق لـ ({$ident}).",
                ];
            }

            $colDate = !empty($arguments['collection_date']) ? $arguments['collection_date'] : date('Y-m-d');
            $method = !empty($arguments['payment_method']) ? $arguments['payment_method'] : 'cash';

            $collection = DebtCollection::create([
                'debt_id'         => $debt->id,
                'company_id'      => $debt->company_id,
                'employee_id'     => $debt->employee_id,
                'collection_date' => $colDate,
                'amount'          => $amount,
                'payment_method'  => $method,
                'description'     => $arguments['description'] ?? "تحصيل دفعة دين [{$debt->debt_number}]",
                'executed_by'     => auth()->id(),
            ]);

            $freshDebt = $debt->fresh();
            $beneficiary = $freshDebt->beneficiary_name;

            return [
                'success' => true,
                'message' => "تم تسجيل سند تحصيل دين بمبلغ (" . number_format($amount, 2) . " ر.س) من ({$beneficiary}) بنجاح بسند #{$collection->voucher_number}. المتبقي: " . number_format($freshDebt->remaining_amount, 2) . " ر.س",
                'collection_id' => $collection->id,
                'name' => "تحصيل #{$collection->voucher_number} ({$beneficiary})",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ سند تحصيل الدين: ' . $e->getMessage(),
            ];
        }
    }
}
