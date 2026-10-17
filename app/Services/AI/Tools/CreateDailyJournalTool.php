<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\DailyJournal;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateDailyJournalTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_daily_journal';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج قيد يومية أو تقرير إغلاق مالي يومي (مبيعات، مشتريات، صافي الدخل اليومي، ونقدي وشبكة).',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'entry_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ اليومية بصيغة YYYY-MM-DD'
                    ],
                    'sales' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي المبيعات اليومية بالريال السعودي'
                    ],
                    'purchases' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي المشتريات والمصروفات اليومية بالريال السعودي'
                    ],
                    'sales_cash' => [
                        'type' => 'NUMBER',
                        'description' => 'مبيعات الكاش / النقدي إن وجدت'
                    ],
                    'sales_network' => [
                        'type' => 'NUMBER',
                        'description' => 'مبيعات مدى / الشبكة إن وجدت'
                    ],
                    'purchases_cash' => [
                        'type' => 'NUMBER',
                        'description' => 'مشتريات الكاش / النقدي إن وجدت'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات اليومية'
                    ]
                ],
                'required' => ['entry_date', 'sales']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $date = trim($arguments['entry_date'] ?? date('Y-m-d'));
            $sales = (float)($arguments['sales'] ?? 0);
            $purchases = (float)($arguments['purchases'] ?? 0);
            $salesCash = (float)($arguments['sales_cash'] ?? 0);
            $salesNetwork = (float)($arguments['sales_network'] ?? 0);
            $purchasesCash = (float)($arguments['purchases_cash'] ?? 0);
            $notes = trim($arguments['notes'] ?? '');

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // فحص منع التكرار لنفس اليوم والشركة
            $existing = DailyJournal::where('company_id', $companyId)
                ->whereDate('entry_date', $date)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): قيد يومية بتاريخ ({$date}) مسجل مسبقاً برقم #{$existing->entry_number}، تم تخطيه.",
                ];
            }

            $netAmount = round($sales - $purchases, 2);

            $journal = DailyJournal::create([
                'company_id' => $companyId,
                'entry_date' => $date,
                'sales' => $sales,
                'purchases' => $purchases,
                'net_amount' => $netAmount,
                'sales_cash' => $salesCash,
                'sales_network' => $salesNetwork,
                'purchases_cash' => $purchasesCash,
                'notes' => $notes ?: 'استيراد ذكي — قيد يومية',
                'recorded_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل يومية بتاريخ ({$date}) بنجاح: مبيعات " . number_format($sales, 2) . " ر.س | مشتريات " . number_format($purchases, 2) . " ر.س | الصافي: " . number_format($netAmount, 2) . " ر.س",
                'record_name' => "يومية {$date}",
                'details' => [
                    'date' => $date,
                    'sales' => number_format($sales, 2) . ' ر.س',
                    'net' => number_format($netAmount, 2) . ' ر.س',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ قيد اليومية: ' . $e->getMessage(),
            ];
        }
    }
}
