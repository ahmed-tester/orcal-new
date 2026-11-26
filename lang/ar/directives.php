<?php

return [
    'models' => [
        'directive' => [
            'singular' => 'توجيه إداري',
            'plural' => 'التوجيهات والتنبيهات',
        ],
        'category' => [
            'singular' => 'تصنيف توجيهات',
            'plural' => 'تصنيفات التوجيهات',
        ],
        'recipient' => [
            'singular' => 'مستلم',
            'plural' => 'حالة موافقة الموظفين',
        ],
    ],

    'sections' => [
        'directive_details' => 'بيانات التوجيه والتنبيه',
        'directive_details_desc' => 'اكتب التوجيه واختر التصنيف المناسب وحدد الموظفين المستهدفين لإلزامهم بالاطلاع والموافقة.',
        'target_audience' => 'الجهة المستهدفة والموظفون',
        'category_details' => 'تفاصيل تصنيف التوجيهات',
        'category_details_desc' => 'إدارة أسماء وألوان تصنيفات التوجيهات الإدارية بشكل مستقل عن تصنيفات البريد.',
    ],

    'fields' => [
        'title' => 'عنوان التوجيه',
        'category' => 'التصنيف',
        'description' => 'نص التوجيه / الوصف',
        'target_type' => 'المستهدفون',
        'employees' => 'اختر الموظفين',
        'is_active' => 'تفعيل التوجيه',
        'created_by' => 'المرسل',
        'created_at' => 'تاريخ الإرسال',
        'acknowledgment_status' => 'حالة الموافقة',
        'acknowledgment_progress' => 'نسبة الاطلاع',
        'employee_name' => 'اسم الموظف',
        'company' => 'المنشأة',
        'branch' => 'الفرع',
        'recipient_status' => 'الحالة',
        'acknowledged_at' => 'تاريخ ووقت الموافقة',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'color' => 'لون الشارة',
        'sort_order' => 'الترتيب',
        'directives_count' => 'عدد التوجيهات',
    ],

    'targets' => [
        'all' => 'جميع موظفي المؤسسة',
        'selected' => 'مجموعة موظفين محددين',
    ],

    'statuses' => [
        'completed' => 'تمت الموافقة بالكامل',
        'partial' => 'موافقة جزئية',
        'unread' => 'لم يُشاهد بعد',
        'no_recipients' => 'لا يوجد مستلمون',
        'acknowledged' => 'تمت الموافقة',
        'pending' => 'لم يوافق بعد',
    ],

    'colors' => [
        'gray' => 'رمادي',
        'primary' => 'كحلي (رئيسي)',
        'info' => 'أزرق (معلومات)',
        'success' => 'أخضر (نجاح)',
        'warning' => 'أصفر (تنبيه)',
        'danger' => 'أحمر (عاجل)',
    ],

    'helpers' => [
        'target_all' => 'سيظهر هذا التوجيه تلقائياً كنافذة منبثقة إلزامية لجميع الموظفين النشطين ضمن نطاق مؤسستك المصرح بها.',
        'target_selected' => 'اختر موظفاً واحداً أو أكثر من موظفي المؤسسة؛ سيظهر لهم التوجيه ولن يُغلق إلا بالضغط على موافق.',
    ],

    'actions' => [
        'view_recipients' => 'متابعة الموافقات',
        'acknowledge' => 'موافق',
    ],

    'popup' => [
        'header_title' => 'توجيه وتنبيه إلزامي',
        'mandatory_notice' => 'يرجى قراءة التوجيه جيداً والضغط على زر "موافق" للمتابعة.',
        'counter' => 'توجيه :current من :total',
        'sender' => 'المرسل',
        'sent_at' => 'تاريخ الإرسال',
        'acknowledged_toast' => 'تم تسجيل موافقتك على التوجيه بنجاح',
    ],

    'labels' => [
        'employee_fallback' => 'موظف #:id',
    ],

    'validation' => [
        'category_has_directives' => 'لا يمكن حذف هذا التصنيف لوجود توجيهات مرتبطة به.',
    ],
];
