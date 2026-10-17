<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // الإعدادات العامة
            [
                'key' => 'site_name',
                'label' => 'اسم النظام / المؤسسة',
                'group' => 'general',
                'input_type' => 'text',
                'value' => 'مجموعة فواز القابضة - إدارة الموارد البشرية والعمليات',
                'description' => 'الاسم الرسمي الذي يظهر في أعلى لوحة التحكم وفي الترويسات الرسمية للتقارير',
            ],
            [
                'key' => 'company_phone',
                'label' => 'رقم هاتف التواصل العام',
                'group' => 'general',
                'input_type' => 'text',
                'value' => '0500688150',
                'description' => 'رقم الهاتف الرئيسي للشركة لطباعته في نماذج العقود وعروض الأسعار',
            ],
            [
                'key' => 'company_email',
                'label' => 'البريد الإلكتروني الرسمي',
                'group' => 'general',
                'input_type' => 'text',
                'value' => 'info@fawazholding.com',
                'description' => 'البريد الإلكتروني المعتمد للمراسلات الرسمية',
            ],

            // إعدادات التنبيهات (وفقاً للملف ص 53 و 54)
            [
                'key' => 'iqama_alert_first_days',
                'label' => 'تنبيه انتهاء الإقامة الأول (بالأيام)',
                'group' => 'alerts',
                'input_type' => 'number',
                'value' => '90',
                'description' => 'عدد الأيام المتبقية على انتهاء إقامة الموظف قبل إرسال التنبيه الأول وتغيير لون الحالة',
            ],
            [
                'key' => 'iqama_alert_second_days',
                'label' => 'تنبيه انتهاء الإقامة الثاني (بالأيام)',
                'group' => 'alerts',
                'input_type' => 'number',
                'value' => '60',
                'description' => 'عدد الأيام المتبقية على انتهاء الإقامة للتنبيه الثاني ولون التحذير المتوسط',
            ],
            [
                'key' => 'iqama_alert_third_days',
                'label' => 'تنبيه انتهاء الإقامة الحرج (بالأيام)',
                'group' => 'alerts',
                'input_type' => 'number',
                'value' => '30',
                'description' => 'فترة الخطر (الإنذار الأخير) مع إرسال تنبيهات يومية أو أسبوعية',
            ],

            // إعدادات رفع الملفات (وفقاً للملف ص 54)
            [
                'key' => 'allowed_upload_extensions',
                'label' => 'صيغ الملفات المسموح برفعها',
                'group' => 'uploads',
                'input_type' => 'text',
                'value' => 'pdf,jpg,jpeg,png',
                'description' => 'الصيغ المعتمدة لرفع الوثائق وعقود العمل والإقامات والسجلات التجارية',
            ],
            [
                'key' => 'max_upload_size_kb',
                'label' => 'الحد الأقصى لحجم الملف (كيلوبايت)',
                'group' => 'uploads',
                'input_type' => 'number',
                'value' => '5120',
                'description' => 'أقصى حجم مسموح به للملف الواحد (5120 كيلوبايت = 5 ميجابايت)',
            ],


            // إعدادات الذكاء الاصطناعي والاستيراد الذكي
            [
                'key' => 'gemini_api_key',
                'label' => 'مفتاح Google Gemini API Key',
                'group' => 'ai',
                'input_type' => 'text',
                'value' => env('GEMINI_API_KEY', ''),
                'description' => 'المفتاح السري لخدمة Google Gemini AI لقراءة وتحليل واستيراد الملفات والمستندات الذكية',
            ],
            [
                'key' => 'gemini_model',
                'label' => 'نموذج الذكاء الاصطناعي (Gemini Model)',
                'group' => 'ai',
                'input_type' => 'text',
                'value' => env('GEMINI_MODEL', 'gemini-3.5-flash'),
                'description' => 'اسم نموذج الذكاء الاصطناعي الأساسي المستخدم (افتراضياً: gemini-3.5-flash)',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
