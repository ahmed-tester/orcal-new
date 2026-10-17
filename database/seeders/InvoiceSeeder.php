<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        $mainBranch = Branch::where('company_id', $mainCompany->id)->first();
        $adminUser = User::where('user_type', \App\Enums\UserType::SUPER_ADMIN)->first() ?? User::first();
        $customers = Customer::all();
        $services = Service::where('company_id', $mainCompany->id)->get();
        $employees = Employee::where('company_id', $mainCompany->id)->get();

        if ($customers->isEmpty() || $services->isEmpty() || $employees->isEmpty()) {
            return;
        }

        $invoiceTypesData = [
            ['name' => 'فاتورة ضريبية قياسية', 'code' => 'INV-TAX', 'desc' => 'فاتورة ضريبية رسمية تخضع لضريبة القيمة المضافة 15%'],
            ['name' => 'فاتورة خدمات استشارية', 'code' => 'INV-CONSULT', 'desc' => 'فاتورة خدمات استشارات الأعمال والإدارة والتدريب'],
            ['name' => 'فاتورة تعاقد دوري واشتراك', 'code' => 'INV-SUB', 'desc' => 'فاتورة عقود الصيانة والتشغيل والخدمات الدورية'],
            ['name' => 'فاتورة تعقيب وإجراءات حكومية', 'code' => 'INV-GOV', 'desc' => 'فاتورة معاملات مقيم، قوى، بلدي، والدفاع المدني'],
        ];

        $invoiceTypes = [];
        foreach ($invoiceTypesData as $it) {
            $invoiceTypes[] = InvoiceType::updateOrCreate(
                ['company_id' => $mainCompany->id, 'code' => $it['code']],
                [
                    'name' => $it['name'],
                    'description' => $it['desc'],
                    'status' => 'active',
                ]
            );
        }

        $invoicesData = [
            [
                'number' => 'INV-2026-00101',
                'cust_idx' => 0,
                'emp_idx' => 0,
                'type_idx' => 0,
                'date' => '2026-09-05',
                'status' => 'paid',
                'items' => [
                    ['service_idx' => 3, 'qty' => 2, 'price' => 2500.00],
                    ['service_idx' => 5, 'qty' => 1, 'price' => 3500.00],
                ],
                'notes' => 'تم استلام الدفعة كاملة عبر تحويل بنكي سريع',
            ],
            [
                'number' => 'INV-2026-00102',
                'cust_idx' => 1,
                'emp_idx' => 1,
                'type_idx' => 1,
                'date' => '2026-09-10',
                'status' => \App\Enums\PaymentStatus::PARTIALLY_PAID,
                'items' => [
                    ['service_idx' => 4, 'qty' => 1, 'price' => 5000.00],
                    ['service_idx' => 0, 'qty' => 5, 'price' => 350.00],
                ],
                'notes' => 'دفعة مقدمة 50% مستلمة، والمتبقي عند اكتمال الترخيص',
            ],
            [
                'number' => 'INV-2026-00103',
                'cust_idx' => 2,
                'emp_idx' => 2,
                'type_idx' => 3,
                'date' => '2026-09-15',
                'status' => \App\Enums\PaymentStatus::UNPAID,
                'items' => [
                    ['service_idx' => 1, 'qty' => 4, 'price' => 850.00],
                    ['service_idx' => 2, 'qty' => 2, 'price' => 1200.00],
                ],
                'notes' => 'فاتورة تجديد وإصدار إقامات لعمال المنشأة تحت التحصيل',
            ],
            [
                'number' => 'INV-2026-00104',
                'cust_idx' => 4,
                'emp_idx' => 4,
                'type_idx' => 0,
                'date' => '2026-09-18',
                'status' => \App\Enums\PaymentStatus::PAID,
                'items' => [
                    ['service_idx' => 0, 'qty' => 1, 'price' => 350.00],
                    ['service_idx' => 1, 'qty' => 1, 'price' => 850.00],
                ],
                'notes' => 'سداد نقدي فوري بالفرع',
            ],
        ];

        foreach ($invoicesData as $inv) {
            $cust = $customers[$inv['cust_idx']] ?? $customers->first();
            $emp = $employees[$inv['emp_idx']] ?? $employees->first();
            $type = $invoiceTypes[$inv['type_idx']] ?? $invoiceTypes[0];

            // Calculate totals
            $subtotal = 0;
            $itemsData = [];
            foreach ($inv['items'] as $it) {
                $srv = $services[$it['service_idx']] ?? $services->first();
                $lineSubtotal = $it['qty'] * $it['price'];
                $taxRate = 15.0;
                $taxAmount = round($lineSubtotal * ($taxRate / 100), 2);
                $lineTotal = $lineSubtotal + $taxAmount;

                $subtotal += $lineTotal;

                $itemsData[] = [
                    'service_id' => $srv->id,
                    'item_name' => $srv->name,
                    'quantity' => $it['qty'],
                    'unit_price' => $it['price'],
                    'subtotal' => $lineSubtotal,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $lineTotal,
                ];
            }

            $totalAmount = $subtotal;
            $paidAmount = match ($inv['status']) {
                \App\Enums\PaymentStatus::PAID, 'paid' => $totalAmount,
                \App\Enums\PaymentStatus::PARTIALLY_PAID, 'partially_paid', 'partial' => round($totalAmount / 2, 2),
                default => 0.00,
            };
            $remainingAmount = round($totalAmount - $paidAmount, 2);

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $inv['number']],
                [
                    'company_id' => $mainCompany->id,
                    'branch_id' => $mainBranch?->id,
                    'invoice_type_id' => $type->id,
                    'customer_id' => $cust->id,
                    'employee_id' => $emp->id,
                    'issue_date' => $inv['date'],
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_status' => $inv['status'],
                    'is_paid_by_applicant' => true,
                    'created_by' => $adminUser?->id,
                    'notes' => $inv['notes'],
                ]
            );

            // Delete old items and insert fresh
            InvoiceItem::where('invoice_id', $invoice->id)->delete();
            foreach ($itemsData as $itemRow) {
                $itemRow['invoice_id'] = $invoice->id;
                InvoiceItem::create($itemRow);
            }
        }
    }
}
