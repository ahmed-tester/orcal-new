<?php

return [
    'model_label' => 'تصعيد أداء',
    'model_plural_label' => 'تصعيدات الأداء',
    'navigation' => 'تصعيدات الأداء',
    'subject_missing' => 'الكيان غير متوفر',

    'subject_types' => [
        'department' => 'قسم',
        'employee' => 'موظف',
        'branch' => 'فرع',
        'supervisor' => 'مشرف',
    ],

    'rules' => [
        'department_operational' => 'الدرجة التشغيلية للقسم',
    ],

    'statuses' => [
        'open' => 'مفتوحة',
        'closed' => 'مغلقة',
    ],

    'closure_reasons' => [
        'recovered' => 'تعافٍ تلقائي',
        'manual' => 'إغلاق يدوي',
    ],

    'fields' => [
        'company' => 'المنشأة',
        'subject_type' => 'نوع الكيان',
        'subject' => 'الكيان',
        'status' => 'الحالة',
        'score_at_open' => 'درجة الفتح',
        'threshold' => 'العتبة',
        'rule_key' => 'قاعدة التصعيد',
        'rules_version' => 'نسخة قواعد الحساب',
        'opened_at' => 'وقت الفتح',
        'management_action' => 'إجراء الإدارة',
        'management_action_by_user_id' => 'متخذ الإجراء',
        'management_action_at' => 'وقت الإجراء',
        'closure_reason' => 'سبب الإغلاق',
        'close_reason' => 'ملاحظات الإغلاق',
        'close_score' => 'درجة الإغلاق',
        'closed_at' => 'وقت الإغلاق',
        'closed_by_user_id' => 'أغلقها',
        'notified_at' => 'وقت الإشعار',
    ],

    'sections' => [
        'case' => 'تفاصيل الحالة',
        'closure' => 'بيانات الإغلاق',
    ],

    'actions' => [
        'record_action' => 'تسجيل إجراء الإدارة',
        'record_action_heading' => 'تسجيل إجراء الإدارة',
        'record_action_description' => 'يسجل الإجراء المتخذ مع بقاء الحالة مفتوحة لمتابعة التحسن.',
    ],

    'notifications' => [
        'opened_title' => 'تصعيد أداء جديد',
        'opened_body' => 'تم فتح حالة تصعيد لـ:subject في :company بدرجة :score أقل من العتبة :threshold. يلزم إجراء إداري.',
        'action_recorded' => 'تم تسجيل إجراء الإدارة على حالة التصعيد.',
    ],

    'commands' => [
        'opened' => 'الحالات المفتوحة: :count',
        'closed' => 'الحالات المغلقة بالتعافي: :count',
        'notified' => 'الإشعارات المرسلة: :count',
        'failed_notifications' => 'فشل إرسال :count إشعارًا وسيعاد المحاولة في التشغيل القادم.',
        'summary' => 'تم فحص :departments قسمًا داخل :companies منشأة.',
    ],
];
