<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\PaymentCollectionItem;
use App\Models\PosDevice;
use App\Models\TreasuryTransaction;
use Illuminate\Database\Seeder;

class FinanceStageOneSeeder extends Seeder
{
    public function run(): void
    {
        $hvacCompany = Company::where('trade_name', 'LIKE', '%تكييف%')->first() ?? Company::first();
        $realEstateCompany = Company::where('trade_name', 'LIKE', '%عقارية%')->first() ?? Company::first();
        $contractingCompany = Company::where('trade_name', 'LIKE', '%مقاولات%')->first() ?? Company::first();
        $logisticsCompany = Company::where('trade_name', 'LIKE', '%لوجستية%')->first() ?? Company::first();

        // 1. بنود الدفع والتحصيل (من صفحة 15 بالـ PDF)
        $items = [
            [
                'transaction_number' => 'I-0041',
                'company_id' => $logisticsCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::GENERAL_EXPENSES->value,
                'sub_item' => 'وقود وإكراميات مندوبين',
                'service_price' => 0,
            ],
            [
                'transaction_number' => 'I-0044',
                'company_id' => $contractingCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::VIOLATIONS->value,
                'sub_item' => 'مخالفات بلدية — عدم تجديد رخصة',
                'service_price' => 0,
            ],
            [
                'transaction_number' => 'I-0052',
                'company_id' => $hvacCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::SERVICES->value,
                'sub_item' => 'عقد صيانة دورية لمكيفات',
                'service_price' => 1200.00,
            ],
            [
                'transaction_number' => 'I-0057',
                'company_id' => $contractingCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::SALARY_COLLECTION->value,
                'sub_item' => 'تحصيل رواتب — مؤسسة فواز للمقاولات',
                'service_price' => 500.00,
            ],
            [
                'transaction_number' => 'I-0061',
                'company_id' => $realEstateCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::INVOICES->value,
                'sub_item' => 'فواتير كهرباء ومياه — الفروع',
                'service_price' => 0,
            ],
            [
                'transaction_number' => 'I-0063',
                'company_id' => $contractingCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::SALARY_PAYMENT->value,
                'sub_item' => 'تسليم رواتب — جميع المؤسسات',
                'service_price' => 200.00,
            ],
            [
                'transaction_number' => 'I-0070',
                'company_id' => $realEstateCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::RENT->value,
                'sub_item' => 'تحصيل — إيجار عقار سكني',
                'service_price' => 0,
            ],
            [
                'transaction_number' => 'I-0071',
                'company_id' => $realEstateCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::RENT->value,
                'sub_item' => 'تحصيل — إيجار محلات تجارية',
                'service_price' => 0,
            ],
            [
                'transaction_number' => 'I-0072',
                'company_id' => $contractingCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::GENERAL_EXPENSES->value,
                'sub_item' => 'دفع — مستخلص مقاول',
                'service_price' => 0,
            ],
            [
                'transaction_number' => 'I-0073',
                'company_id' => $contractingCompany?->id ?? 1,
                'main_item' => \App\Enums\PaymentItemCategory::GENERAL_EXPENSES->value,
                'sub_item' => 'دفع — مشتريات بضاعة',
                'service_price' => 0,
            ],
        ];

        $createdItems = [];
        foreach ($items as $itemData) {
            $createdItems[$itemData['sub_item']] = PaymentCollectionItem::firstOrCreate(
                ['transaction_number' => $itemData['transaction_number']],
                $itemData
            );
        }

        $hvacCompany = Company::where('trade_name', 'LIKE', '%تكييف%')->first() ?? Company::first();
        $realEstateCompany = Company::where('trade_name', 'LIKE', '%عقارية%')->first() ?? Company::first();
        $contractingCompany = Company::where('trade_name', 'LIKE', '%مقاولات%')->first() ?? Company::first();
        $logisticsCompany = Company::where('trade_name', 'LIKE', '%لوجستية%')->first() ?? Company::first();

        $fawazOwner = Owner::first();

        $salemEmp = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%سالم%'))->first();
        $nouraEmp = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%نورة%'))->first();
        $azizEmp = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%عبدالعزيز%'))->first();

        if ($realEstateCompany) {
            PosDevice::firstOrCreate(
                ['device_number' => 'POS-88'],
                [
                    'company_id' => $realEstateCompany->id,
                    'commercial_registration_number' => $realEstateCompany->commercial_registration_number ?? '4030123456',
                    'owner_id' => $fawazOwner?->id,
                    'owner_identity_number' => $fawazOwner?->identity_number ?? '1001234567',
                    'owner_phone' => $fawazOwner?->phone ?? '0500111222',
                    'device_number' => 'POS-88',
                    'card_number' => 'MID-2291',
                    'bank_or_wallet_name' => 'مصرف الراجحي',
                    'account_number' => 'SA4410000075862019466040',
                    'accountant_id' => $salemEmp?->id,
                    'accountant_name' => 'أ. سالم بن محمد الدوسري',
                    'accountant_identity_number' => '1023456789',
                    'accountant_email' => $salemEmp?->user?->email ?? 'salem.aldosari@example.com',
                    'accountant_phone' => '0500333444',
                    'accountant_password' => '1234',
                    'status' => 'temporarily_disabled',
                    'notes' => 'الجهاز مربوط بشبكة المدى — معطل مؤقتاً حتى انتهاء تحديث بوابة الدفع المتوقع 20 سبتمبر',
                ]
            );
        }

        if ($hvacCompany) {
            PosDevice::firstOrCreate(
                ['device_number' => 'POS-90'],
                [
                    'company_id' => $hvacCompany->id,
                    'commercial_registration_number' => $hvacCompany->commercial_registration_number,
                    'owner_id' => $fawazOwner?->id,
                    'device_number' => 'POS-90',
                    'card_number' => 'MID-3350',
                    'bank_or_wallet_name' => 'STC Pay',
                    'account_number' => 'SA0380000000608010167519',
                    'accountant_id' => $nouraEmp?->id,
                    'accountant_name' => 'أ. نورة بنت عبدالله الشمري',
                    'accountant_identity_number' => '1099887766',
                    'accountant_email' => $nouraEmp?->user?->email ?? 'noura.alshammari@example.com',
                    'accountant_phone' => '0505555666',
                    'accountant_password' => '5678',
                    'status' => 'active',
                ]
            );
        }

        if ($contractingCompany) {
            PosDevice::firstOrCreate(
                ['device_number' => 'POS-71'],
                [
                    'company_id' => $contractingCompany->id,
                    'commercial_registration_number' => $contractingCompany->commercial_registration_number,
                    'owner_id' => $fawazOwner?->id,
                    'device_number' => 'POS-71',
                    'card_number' => 'MID-1188',
                    'bank_or_wallet_name' => 'بنك الرياض',
                    'account_number' => 'SA1122334455667788990011',
                    'accountant_id' => $salemEmp?->id,
                    'accountant_name' => 'أ. سالم بن محمد الدوسري',
                    'accountant_identity_number' => '1023456789',
                    'accountant_email' => $salemEmp?->user?->email ?? 'salem.aldosari@example.com',
                    'accountant_phone' => '0500333444',
                    'accountant_password' => '9988',
                    'status' => 'active',
                ]
            );
        }

        if ($logisticsCompany) {
            PosDevice::firstOrCreate(
                ['device_number' => 'POS-64'],
                [
                    'company_id' => $logisticsCompany->id,
                    'commercial_registration_number' => $logisticsCompany->commercial_registration_number,
                    'owner_id' => $fawazOwner?->id,
                    'device_number' => 'POS-64',
                    'card_number' => 'MID-7742',
                    'bank_or_wallet_name' => 'مصرف الإنماء',
                    'account_number' => 'SA9988776655443322110099',
                    'accountant_id' => $azizEmp?->id,
                    'accountant_name' => 'أ. عبدالعزيز بن سالم المطيري',
                    'accountant_identity_number' => '1082334455',
                    'accountant_email' => $azizEmp?->user?->email ?? 'abdulaziz.almutairi@example.com',
                    'accountant_phone' => '0550246810',
                    'accountant_password' => '4433',
                    'status' => 'inactive',
                ]
            );
        }

        // 3. حركات وسندات الخزينة (من صفحة 13 و 14 بالـ PDF)
        $accountantEmployee = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%سالم%'))->first() ?? Employee::first();
        $nouraEmployee = Employee::whereHas('user', fn ($q) => $q->where('name', 'LIKE', '%نورة%'))->first() ?? $accountantEmployee;

        $rawTransactions = [
            [
                'transaction_number' => 'T-00344',
                'voucher_number' => 'R-0087',
                'transaction_date' => '2026-09-10',
                'company_id' => $contractingCompany?->id ?? 1,
                'transaction_type' => 'outgoing',
                'payment_collection_item_id' => $createdItems['دفع — مشتريات بضاعة']->id ?? 1,
                'recipient_or_payer' => 'شركة التوريدات العالمية',
                'amount' => 22000.00,
                'description' => 'شراء قطع ومستلزمات كهربائية ومواد بناء',
                'executed_by' => $accountantEmployee?->id,
                'balance_after' => 128200.00,
            ],
            [
                'transaction_number' => 'T-00345',
                'voucher_number' => 'R-0088',
                'transaction_date' => '2026-09-11',
                'company_id' => $realEstateCompany?->id ?? 1,
                'transaction_type' => 'incoming',
                'payment_collection_item_id' => $createdItems['تحصيل — إيجار محلات تجارية']->id ?? 1,
                'recipient_or_payer' => 'شركة الأفق العقارية',
                'amount' => 35500.00,
                'description' => 'دفعة إيجار المحلات التجارية — فرع العليا',
                'executed_by' => $nouraEmployee?->id,
                'balance_after' => 163700.00,
            ],
            [
                'transaction_number' => 'T-00346',
                'voucher_number' => 'R-0089',
                'transaction_date' => '2026-09-12',
                'company_id' => $contractingCompany?->id ?? 1,
                'transaction_type' => 'outgoing',
                'payment_collection_item_id' => $createdItems['دفع — مستخلص مقاول']->id ?? 1,
                'recipient_or_payer' => 'مكتب العاصمة للاستشارات',
                'amount' => 25450.00,
                'description' => 'دفعة أولى مستخلص أعمال تشطيب — مشروع فيلا الياسمين',
                'executed_by' => $accountantEmployee?->id,
                'balance_after' => 138250.00,
            ],
            [
                'transaction_number' => 'T-00347',
                'voucher_number' => 'R-0090',
                'transaction_date' => '2026-09-13',
                'company_id' => $hvacCompany?->id ?? 1,
                'transaction_type' => 'incoming',
                'payment_collection_item_id' => $createdItems['عقد صيانة دورية لمكيفات']->id ?? 1,
                'recipient_or_payer' => 'مجمع النخيل الطبي',
                'amount' => 7500.00,
                'description' => 'دفعة عقد صيانة دورية شهرية',
                'executed_by' => $accountantEmployee?->id,
                'balance_after' => 145750.00,
            ],
            [
                'transaction_number' => 'T-00348',
                'voucher_number' => 'R-0091',
                'transaction_date' => '2026-09-14',
                'company_id' => $realEstateCompany?->id ?? 1,
                'transaction_type' => 'incoming',
                'payment_collection_item_id' => $createdItems['تحصيل — إيجار عقار سكني']->id ?? 1,
                'recipient_or_payer' => 'شركة الرواد لمواد البناء',
                'amount' => 18000.00,
                'description' => 'دفعة إيجار شهر سبتمبر — وحدة 12',
                'executed_by' => $nouraEmployee?->id,
                'balance_after' => 156750.00,
            ],
        ];

        foreach ($rawTransactions as $tx) {
            TreasuryTransaction::firstOrCreate(
                ['transaction_number' => $tx['transaction_number']],
                $tx
            );
        }
    }
}
