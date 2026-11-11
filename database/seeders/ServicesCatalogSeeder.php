<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServicesCatalogSeeder extends Seeder
{
    /**
     * Seed diversified services catalog (General, Companies, and Individuals/Employees).
     *
     * @return array<string, int>
     */
    public function run(?int $companyId = null): array
    {
        $targetCompanyId = $companyId ?? Company::first()?->id;
        if (! $targetCompanyId) {
            return ['created_count' => 0];
        }

        $services = [
            // ── 1. General Services (خدمات عامة) ──────────────────────────
            [
                'name' => 'إصدار وطباعة شهادة سعودة وتأمينات',
                'target_type' => 'general',
                'category' => 'government',
                'price' => 150.00,
                'default_gov_fee' => 0.00,
                'notes' => 'خدمة عامة لإصدار شهادة نسبة التوطين وسعودة فورياً عبر قوى والتأمينات',
            ],
            [
                'name' => 'توثيق وتعديل عقد عمل منصة قوى',
                'target_type' => 'general',
                'category' => 'government',
                'price' => 350.00,
                'default_gov_fee' => 150.00,
                'notes' => 'صياغة وتوثيق العقود الرسمية وتأكيد موافقة الطرفين عبر قوى',
            ],
            [
                'name' => 'تحديث بيانات وتفويض إلكتروني أبشر أعمال',
                'target_type' => 'general',
                'category' => 'government',
                'price' => 200.00,
                'default_gov_fee' => 0.00,
                'notes' => 'إضافة مفوضين وتحديث الصلاحيات للمستخدمين عبر بوابة أبشر أعمال',
            ],
            [
                'name' => 'استشارة إدارية وتنظيم لوائح العمل الداخلية',
                'target_type' => 'general',
                'category' => 'consulting',
                'price' => 800.00,
                'default_gov_fee' => 0.00,
                'notes' => 'مراجعة وتنظيم اللائحة التنظيمية للمنشأة واعتمادها رسمياً',
            ],

            // ── 2. Establishment / Corporate Services (خدمات المنشآت والمؤسسات) ─
            [
                'name' => 'تأسيس سجل تجاري وتراخيص بلدية ودفاع مدني',
                'target_type' => 'company',
                'category' => 'government',
                'price' => 3500.00,
                'default_gov_fee' => 1200.00,
                'notes' => 'حزمة تأسيس متكاملة: السجل، رخصة بلدي، وتصريح سلامة للدفاع المدني',
            ],
            [
                'name' => 'إصدار وتجديد رخصة بلدية فورية (منصة بلدي)',
                'target_type' => 'company',
                'category' => 'government',
                'price' => 1800.00,
                'default_gov_fee' => 1200.00,
                'notes' => 'رفع المخطط الهندسي وسداد رسوم الأمانة وإصدار الرخصة فورياً',
            ],
            [
                'name' => 'تأهيل واعتماد ملف المنشأة لدى هيئة الزكاة والضريبة',
                'target_type' => 'company',
                'category' => 'finance',
                'price' => 2500.00,
                'default_gov_fee' => 500.00,
                'notes' => 'التسجيل في ضريبة القيمة المضافة وإصدار الشهادة الزكوية وتفعيل الفاتورة الإلكترونية',
            ],
            [
                'name' => 'إصدار شهادة الدفاع المدني (سلامة)',
                'target_type' => 'company',
                'category' => 'government',
                'price' => 1200.00,
                'default_gov_fee' => 600.00,
                'notes' => 'الربط مع شركة السلامة ورفع تقرير الكشف وإصدار الترخيص',
            ],
            [
                'name' => 'فتح ملف منشأة وتفعيل التأمينات والغرفة التجارية',
                'target_type' => 'company',
                'category' => 'government',
                'price' => 900.00,
                'default_gov_fee' => 300.00,
                'notes' => 'فتح ملف مكتب العمل والتأمينات الاجتماعية وتصديق اشتراك الغرفة',
            ],
            [
                'name' => 'طلب تأشيرات تأسيسية وتوسع (وزارة الموارد البشرية)',
                'target_type' => 'company',
                'category' => 'government',
                'price' => 2200.00,
                'default_gov_fee' => 2000.00,
                'notes' => 'رفع دراسة التوسع وإصدار رصيد التأشيرات المهنية عبر قوى',
            ],

            // ── 3. Individual / Employee Services (خدمات الأفراد والمنسوبين) ───
            [
                'name' => 'تجديد إقامة ورخصة عمل (سنة كاملة - مقيم)',
                'target_type' => 'employee',
                'category' => 'government',
                'price' => 1150.00,
                'default_gov_fee' => 950.00,
                'notes' => 'سداد المقابل المالي ورخصة العمل والتجديد الفوري عبر منصة مقيم',
            ],
            [
                'name' => 'نقل كفالة وتعديل مهنة عبر منصة قوى',
                'target_type' => 'employee',
                'category' => 'government',
                'price' => 1500.00,
                'default_gov_fee' => 1000.00,
                'notes' => 'إرسال طلب النقل الوظيفي، وتعديل المسمى المهني وسداد الرسوم',
            ],
            [
                'name' => 'استخراج تأشيرة وتفويض مساند (عمالة منزلية / أفراد)',
                'target_type' => 'employee',
                'category' => 'government',
                'price' => 2400.00,
                'default_gov_fee' => 2000.00,
                'notes' => 'إصدار التأشيرة عبر منصة مساند والتعاقد مع المكتب الخارجي',
            ],
            [
                'name' => 'إصدار تأشيرة خروج وعودة متعددة',
                'target_type' => 'employee',
                'category' => 'government',
                'price' => 500.00,
                'default_gov_fee' => 500.00,
                'notes' => 'سداد الرسوم وإصدار التأشيرة الإلكترونية فورياً عبر مقيم',
            ],
            [
                'name' => 'تحديث بيانات جواز سفر وتمديد تأشيرة زيارة',
                'target_type' => 'employee',
                'category' => 'government',
                'price' => 400.00,
                'default_gov_fee' => 100.00,
                'notes' => 'نقل معلومات الجواز الجديد وتأمين الزيارة وتمديد الصلاحية',
            ],
            [
                'name' => 'إسقاط وتأشيرة خروج نهائي وإخلاء طرف',
                'target_type' => 'employee',
                'category' => 'government',
                'price' => 300.00,
                'default_gov_fee' => 0.00,
                'notes' => 'تسوية المستحقات وإصدار تأشيرة الخروج النهائي وإسقاط الاسم من التأمينات',
            ],
        ];

        $count = 0;
        foreach ($services as $svc) {
            Service::updateOrCreate(
                [
                    'company_id' => $targetCompanyId,
                    'name' => $svc['name'],
                ],
                [
                    'target_type' => $svc['target_type'],
                    'category' => $svc['category'],
                    'price' => $svc['price'],
                    'default_gov_fee' => $svc['default_gov_fee'],
                    'notes' => $svc['notes'],
                    'status' => 'active',
                ]
            );
            $count++;
        }

        return ['created_count' => $count];
    }
}

