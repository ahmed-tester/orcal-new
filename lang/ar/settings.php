<?php

return [
    'single' => 'إعداد',
    'plural' => 'إعدادات النظام',
    'settings' => 'الإعدادات العامة',

    'sections' => [
        'general' => 'بيانات الإعداد الأساسية',
        'general_desc' => 'المفتاح، التصنيف، والتسمية الظاهرة للمستخدم',
        'value_and_type' => 'نوع الحقل والقيمة المحفوظة',
        'value_and_type_desc' => 'تحديد نوع الإدخال والقيمة المسندة وخيارات الحقل',
    ],

    'groups' => [
        'general' => 'إعدادات عامة',
        'appearance' => 'المظهر والهوية',
        'alerts' => 'التنبيهات والإشعارات',
        'uploads' => 'رفع الملفات والمستندات',
        'ai' => 'الذكاء الاصطناعي والتكاملات',
        'hr' => 'الموارد البشرية والرواتب',
        'company' => 'المؤسسات والفروع',
        'contracts' => 'المقاولات والعقود',
    ],

    'input_types' => [
        'text' => 'نص قصير (Text)',
        'textarea' => 'نص متعدد الأسطر (Textarea)',
        'number' => 'رقم (Number)',
        'toggle' => 'مفتاح تبديل (نعم / لا)',
        'select' => 'قائمة اختيار (Select)',
        'color' => 'منتقي ألوان (Color)',
        'file' => 'ملف / صورة (File)',
        'date' => 'تاريخ (Date)',
    ],

    'fields' => [
        'key' => 'المفتاح البرمجي (Key)',
        'label' => 'اسم الإعداد (العنوان الظاهر)',
        'group' => 'مجموعة الإعداد (Group)',
        'input_type' => 'نوع حقل الإدخال',
        'value' => 'قيمة الإعداد',
        'options' => 'خيارات القائمة (JSON أو مفتاح:قيمة)',
        'description' => 'وصف وتوضيح الإعداد',
        'is_active' => 'حالة التفعيل (نشط / معطل)',
        'created_at' => 'تاريخ الإنشاء',
        'updated_at' => 'آخر تعديل',
    ],

    'helpers' => [
        'key' => 'معرف فريد باللغة الإنجليزية مثل site_name أو iqama_alert_days',
        'options' => 'في حال كان الحقل من نوع قائمة (Select)، أدخل الخيارات بصيغة JSON أو مفتاح:قيمة',
        'is_active' => 'عند إيقاف هذا الخيار، سيتم تعطيل الإعداد مؤقتاً في النظام دون حذفه',
    ],

    'manage' => [
        'title' => 'إعدادات النظام',
        'save_changes' => 'حفظ التغييرات',
        'save_success' => 'تم حفظ الإعدادات بنجاح',

        'general' => [
            'section_desc' => 'البيانات الأساسية للنظام والمؤسسة والمراسلات',
            'site_name' => 'اسم النظام / المؤسسة',
            'site_name_helper' => 'الاسم الرسمي الذي يظهر في أعلى لوحة التحكم وفي الترويسات الرسمية للتقارير',
            'company_phone' => 'رقم هاتف التواصل العام',
            'company_phone_helper' => 'رقم الهاتف الرئيسي للشركة لطباعته في نماذج العقود وعروض الأسعار',
            'company_email' => 'البريد الإلكتروني الرسمي',
            'company_email_helper' => 'البريد الإلكتروني المعتمد للمراسلات الرسمية',
        ],

        'alerts' => [
            'section_desc' => 'تحديد فترات التنبيه التلقائي لمواعيد انتهاء الإقامات والمستندات الرسمية',
            'iqama_alert_first_days' => 'تنبيه انتهاء الإقامة الأول (بالأيام)',
            'iqama_alert_first_days_helper' => 'عدد الأيام المتبقية قبل إرسال التنبيه الأول وتغيير لون الحالة',
            'iqama_alert_second_days' => 'تنبيه انتهاء الإقامة الثاني (بالأيام)',
            'iqama_alert_second_days_helper' => 'عدد الأيام المتبقية للتنبيه الثاني ولون التحذير المتوسط',
            'iqama_alert_third_days' => 'تنبيه انتهاء الإقامة الحرج (بالأيام)',
            'iqama_alert_third_days_helper' => 'فترة الخطر (الإنذار الأخير) مع إرسال تنبيهات حرجة',
        ],

        'uploads' => [
            'section_desc' => 'قيود وضوابط رفع الملفات والوثائق في النظام',
            'allowed_upload_extensions' => 'صيغ الملفات المسموح برفعها',
            'allowed_upload_extensions_helper' => 'أدخل الامتدادات مفصولة بفاصلة مثل: pdf,jpg,jpeg,png,docx,xlsx',
            'max_upload_size_kb' => 'الحد الأقصى لحجم الملف (كيلوبايت)',
            'max_upload_size_kb_helper' => 'أقصى حجم مسموح به للملف الواحد (5120 كيلوبايت = 5 ميجابايت)',
        ],

        'ai' => [
            'section_desc' => 'إعدادات الاتصال بنماذج Google Gemini AI لقراءة وتحليل المستندات الذكية',
            'gemini_api_key' => 'مفتاح Google Gemini API Key',
            'gemini_api_key_helper' => 'المفتاح السري لخدمة Google Gemini AI لقراءة وتحليل واستيراد الملفات',
            'gemini_model' => 'نموذج الذكاء الاصطناعي (Gemini Model)',
            'gemini_model_helper' => 'اختر نموذج الذكاء الاصطناعي المعتمد لمعالجة واستيراد الملفات',
            'models' => [
                'gemini_3_5_flash' => 'Gemini 3.5 Flash (النموذج القياسي ⚡)',
                'gemini_3_6_flash' => 'Gemini 3.6 Flash',
                'gemini_3_7_flash' => 'Gemini 3.7 Flash',
                'gemini_3_8_flash' => 'Gemini 3.8 Flash',
                'gemini_3_5_flash_lite' => 'Gemini 3.5 Flash Lite (خفيف وفائق السرعة 🚀)',
            ],
            'test_btn' => '⚡ فحص واختبار الاتصال بالمفتاح والنموذج المختار',
            'test_header_btn' => 'اختبار وفحص الاتصال ⚡',
            'no_api_key_title' => 'تنبيه: لا يوجد مفتاح API',
            'no_api_key_body' => 'يرجى إدخال مفتاح Google Gemini API Key أولاً لإجراء الفحص.',
            'test_success_title' => 'الاتصال ناجح وسليم! 🚀',
            'test_success_body' => 'تم التحقق بنجاح من المفتاح، والنموذج (:model) مستعد للعمل ومعالجة البيانات بأعلى كفاءة.',
            'test_failed_title' => 'فشل الاتصال بـ Google Gemini ❌',
            'test_failed_body' => 'السبب: :error',
            'test_error_title' => 'خطأ أثناء محاولة الاتصال ❌',

            'errors' => [
                'invalid_api_key' => 'مفتاح الـ API غير صالح. يرجى التأكد من نسخ المفتاح بشكل صحيح من Google AI Studio.',
                'quota_exceeded' => 'تم تجاوز حد الاستخدام المسموح به (Quota Exceeded) في حساب Google الخاص بك.',
                'model_not_found' => 'النموذج المختار غير متاح حالياً أو غير مدعوم في هذا الإصدار من Google.',
                'permission_denied' => 'تم رفض الصلاحية. يرجى التحقق من تفعيل خدمة Generative Language API.',
            ],
        ],

        'database_reset' => [
            'button_label' => 'تصفير قاعدة البيانات',
            'modal_heading' => 'تحذير شديد الخطورة: تصفير قاعدة البيانات بالكامل',
            'modal_description' => 'سيتم حذف وتصفير جميع البيانات التشغيلية (الشركات، الفروع، الموظفين، العمليات المالية، الفواتير، الإجازات، سجلات البصمة والمهام) نهائياً ولا يمكن التراجع عن هذه الخطوة. سيتم الحفاظ فقط على حسابات المشرفين والمدراء (Admins & Super Admins) وإعدادات النظام الأساسية.',
            'submit_label' => 'تأكيد وحذف البيانات نهائياً',
            'checkbox_label' => 'أقر وأوافق على حذف وتصفير جميع البيانات التشغيلية نهائياً مع الإبقاء على المدراء',
            'success_title' => 'تم تصفير قاعدة البيانات بنجاح',
            'success_body' => 'تم تفريغ :tables جدولاً تشغيلياً بنجاح مع الحفاظ على :admins حساب إداري.',
            'failed_title' => 'فشل تصفير قاعدة البيانات',
        ],
    ],
];
