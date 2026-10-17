<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Database\Seeder;

class CustomerAndServiceSeeder extends Seeder
{
    public function run(): void
    {
        $mainCompany = Company::first();
        if (!$mainCompany) {
            return;
        }

        $customersData = [
            [
                'customer_type' => 'company',
                'name' => 'شركة أرامكو السعودية لخدمات الطاقة',
                'national_id' => null,
                'commercial_registration_number' => '1010112233',
                'tax_number' => '300011223300003',
                'phone' => '0138761000',
                'email' => 'contracts@aramco-energy.com',
                'address' => 'الظهران - مجمع أرامكو الرئيسي',
                'city' => 'الظهران',
                'status' => 'active',
            ],
            [
                'customer_type' => 'government',
                'name' => 'الهيئة العامة للمنشآت الصغيرة والمتوسطة (منشآت)',
                'national_id' => null,
                'commercial_registration_number' => '7001928475',
                'tax_number' => '300192847500003',
                'phone' => '0114920000',
                'email' => 'info@monshaat.gov.sa',
                'address' => 'الرياض - طريق الثمامة',
                'city' => 'الرياض',
                'status' => 'active',
            ],
            [
                'customer_type' => 'establishment',
                'name' => 'مؤسسة النجم الساطع للمقاولات العامة',
                'national_id' => null,
                'commercial_registration_number' => '1010445566',
                'tax_number' => '300044556600003',
                'phone' => '0559876543',
                'email' => 'star.contracting@gmail.com',
                'address' => 'الرياض - حي السلي',
                'city' => 'الرياض',
                'status' => 'active',
            ],
            [
                'customer_type' => 'company',
                'name' => 'شركة الأفق الذهبي للاستشارات الإدارية',
                'national_id' => null,
                'commercial_registration_number' => '4030667788',
                'tax_number' => '300066778800003',
                'phone' => '0126543210',
                'email' => 'contact@goldhorizon.com',
                'address' => 'جدة - طريق الملك عبدالعزيز',
                'city' => 'جدة',
                'status' => 'active',
            ],
            [
                'customer_type' => 'individual',
                'name' => 'خالد بن منصور الدوسري',
                'national_id' => '1044332211',
                'commercial_registration_number' => null,
                'tax_number' => null,
                'phone' => '0501239876',
                'email' => 'khaled.dos@gmail.com',
                'address' => 'الرياض - حي النرجس',
                'city' => 'الرياض',
                'status' => 'active',
            ],
            [
                'customer_type' => 'individual',
                'name' => 'سارة بنت علي الشمري',
                'national_id' => '1033221100',
                'commercial_registration_number' => null,
                'tax_number' => null,
                'phone' => '0509871234',
                'email' => 'sara.ali.shm@gmail.com',
                'address' => 'الدمام - حي الفيصلية',
                'city' => 'الدمام',
                'status' => 'active',
            ],
        ];

        foreach ($customersData as $c) {
            Customer::updateOrCreate(
                ['name' => $c['name']],
                $c
            );
        }

        $servicesData = [
            ['name' => 'إصدار عقد عمل وتوثيق منصة قوى', 'price' => 350.00, 'notes' => 'شامل إعداد العقد وإرساله عبر منصة قوى للاعتماد'],
            ['name' => 'تجديد إقامة ورخصة عمل (سنة كاملة)', 'price' => 850.00, 'notes' => 'شامل السداد والتنفيذ الإلكتروني الفوري عبر مقيم'],
            ['name' => 'استخراج تأشيرة عمل جديدة وتفويض', 'price' => 1200.00, 'notes' => 'خدمة تقديم الطلب ومتابعة الاعتماد مع وزارة الموارد البشرية'],
            ['name' => 'استشارة إدارية وهيكلة موارد بشرية', 'price' => 2500.00, 'notes' => 'جلسة استشارية وتصميم اللائحة الداخلية للمنشأة'],
            ['name' => 'تأسيس فرع منشأة وإصدار الرخص والتراخيص', 'price' => 5000.00, 'notes' => 'إصدار السجل الفرعي، رخصة البلدية، وتصريح الدفاع المدني'],
            ['name' => 'خدمات المطابقة الزكوية والضريبية السنوية', 'price' => 3500.00, 'notes' => 'إعداد ورفع الإقرار الزكوي والضريبي لدى الهيئة'],
        ];

        foreach ($servicesData as $s) {
            Service::updateOrCreate(
                ['company_id' => $mainCompany->id, 'name' => $s['name']],
                [
                    'price' => $s['price'],
                    'notes' => $s['notes'],
                    'status' => 'active',
                ]
            );
        }
    }
}
