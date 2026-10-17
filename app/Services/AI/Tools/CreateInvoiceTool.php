<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateInvoiceTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_invoice_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة فاتورة أو سند مبيعات وخدمات إلى النظام مع ربطها بالعميل والمبلغ، مع منع التكرار.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'invoice_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم الفاتورة الأصلي المسجل في المستند (اختياري)'
                    ],
                    'customer_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم العميل أو الجهة المستفيدة'
                    ],
                    'total_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي مبلغ الفاتورة بالريال السعودي'
                    ],
                    'paid_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المدفوع (اختياري، الافتراضي 0)'
                    ],
                    'issue_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ إصدار الفاتورة بصيغة YYYY-MM-DD (اختياري)'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات أو بيان الخدمات المقدمة بالفاتورة'
                    ]
                ],
                'required' => ['customer_name', 'total_amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $invoiceNumber = trim($arguments['invoice_number'] ?? '');
            $customerName = trim($arguments['customer_name'] ?? '');
            $totalAmount = (float)($arguments['total_amount'] ?? 0);
            $paidAmount = (float)($arguments['paid_amount'] ?? 0);
            $issueDate = trim($arguments['issue_date'] ?? date('Y-m-d'));
            $notes = trim($arguments['notes'] ?? '');

            if (empty($customerName)) {
                return ['success' => false, 'message' => 'اسم العميل مطلوب لإنشاء الفاتورة.'];
            }

            if ($totalAmount <= 0) {
                return ['success' => false, 'message' => 'مبلغ الفاتورة يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // 1. فحص عدم تكرار رقم الفاتورة إذا كان معلوماً
            if (!empty($invoiceNumber) && Invoice::where('invoice_number', $invoiceNumber)->exists()) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): الفاتورة برقم ({$invoiceNumber}) مسجلة مسبقاً في النظام ولم تتم إضافتها ثانية.",
                ];
            }

            // 2. البحث عن العميل أو إنشاؤه
            $customer = Customer::firstOrCreate(
                ['name' => $customerName],
                ['status' => GeneralStatus::ACTIVE]
            );

            // 3. فحص منع التكرار (نفس العميل + نفس المبلغ + نفس التاريخ)
            $duplicateInvoice = Invoice::where('company_id', $companyId)
                ->where('customer_id', $customer->id)
                ->where('total_amount', $totalAmount)
                ->whereDate('issue_date', $issueDate)
                ->first();

            if ($duplicateInvoice) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): فاتورة العميل ({$customerName}) بمبلغ " . number_format($totalAmount, 2) . " ر.س مسجلة مسبقاً برقم #{$duplicateInvoice->invoice_number} بتاريخ ({$issueDate})، تم تجاهلها لمنع التكرار.",
                ];
            }

            $remaining = max(0, $totalAmount - $paidAmount);
            $paymentStatus = PaymentStatus::UNPAID;
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = PaymentStatus::PAID;
            } elseif ($paidAmount > 0) {
                $paymentStatus = PaymentStatus::PARTIALLY_PAID;
            }

            $invoiceData = [
                'company_id' => $companyId,
                'customer_id' => $customer->id,
                'issue_date' => $issueDate ?: date('Y-m-d'),
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remaining,
                'payment_status' => $paymentStatus,
                'notes' => $notes ?: null,
                'created_by' => auth()->id(),
            ];

            if (!empty($invoiceNumber)) {
                $invoiceData['invoice_number'] = $invoiceNumber;
            }

            $invoice = Invoice::create($invoiceData);

            return [
                'success' => true,
                'message' => "تم إنشاء الفاتورة ({$invoice->invoice_number}) للعميل ({$customerName}) بإجمالي " . number_format($totalAmount, 2) . " ر.س",
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ الفاتورة: ' . $e->getMessage(),
            ];
        }
    }
}
