<?php

return [
    'single' => 'دين عام',
    'plural' => 'سجل الديون العامة',

    'sections' => [
        'debtor_source' => 'مصدر وبيانات المدين',
        'debtor_source_desc' => 'تحديد ما إذا كان الدين مسجلاً على منشأة وموظف أو على طرف خارجي حر',
        'internal_debtor' => 'بيانات المنشأة والمدين التابع لها',
        'internal_debtor_desc' => 'تحديد المؤسسة والموظف المترتب عليه الدين',
        'external_legal_info' => 'السجل القانوني للمدين (خارج المنشآت)',
        'external_legal_info_desc' => 'بيانات الإثبات الرسمية للمدين لتوثيق السجل القانوني والمطالبة القضائية',
        'guarantor_info' => 'بيانات الضامن أو الكفيل (اختياري)',
        'guarantor_info_desc' => 'إضافة ضامن معتمد من سجل الكفلاء للالتزام بسداد الدين',
        'financial' => 'البيانات المالية وأصل الدين',
        'financial_desc' => 'المبلغ الأصلي، سبب نشوء الدين، المحصل، والمتبقي',
        'notification' => 'تنبيه الاستحقاق والمتابعة',
        'notification_desc' => 'مسؤول المتابعة وتاريخ الاستحقاق ونص التنبيه',
    ],

    'fields' => [
        'debt_number' => 'رقم الدين (تلقائي)',
        'debt_date' => 'تاريخ نشوء الدين',
        'due_date' => 'تاريخ الاستحقاق',
        'debtor_type' => 'مصدر المدين',
        'company' => 'المؤسسة',
        'employee' => 'الموظف المدين',
        'debtor' => 'المدين (صاحب الدين)',
        'external_name' => 'اسم المدين / الجهة الرباعي',
        'external_national_id' => 'رقم الهوية / السجل التجاري',
        'external_phone' => 'رقم الجوال للتواصل',
        'external_email' => 'البريد الإلكتروني',
        'external_address' => 'العنوان الوطني ومقر الإقامة',
        'guarantor' => 'الضامن أو الكفيل',
        'total_amount' => 'أصل مبلغ الدين (ر.س)',
        'paid_amount' => 'المبلغ المحصل (ر.س)',
        'remaining_amount' => 'المتبقي من الدين (ر.س)',
        'debt_reason' => 'سبب ونوع الدين / البند',
        'description' => 'تفاصيل وشروط الدين',
        'status' => 'حالة الدين',
        'notify_employee' => 'يصل التنبيه إلى',
        'notification_notes' => 'نص التنبيه والمتابعة',
        'executed_by' => 'المسؤول المسجل',
    ],

    'toggle_options' => [
        'internal' => 'خدمات داخلية (منشآت وموظفين)',
        'external' => 'خارج المنشآت (تسجيل حر)',
    ],

    'badges' => [
        'internal' => 'منشآت',
        'external' => 'خارجي',
        'pending' => 'مستحق',
        'partial' => 'تحصيل جزئي',
        'paid' => 'مسدد بالكامل',
        'overdue' => 'متعثر / متأخر',
    ],

    'statuses' => [
        'pending' => 'مستحق (غير محصل)',
        'partial' => 'محصل جزئياً',
        'paid' => 'مسدد بالكامل',
        'overdue' => 'متأخر عن الاستحقاق',
    ],

    'placeholders' => [
        'external_name' => 'اسم المدين أو المنشأة بالكامل',
        'external_national_id' => 'رقم الهوية الوطنية أو الإقامة أو 700',
        'external_phone' => '05xxxxxxxx',
        'external_address' => 'المدينة، الحي، العنوان الوطني',
        'debt_reason' => 'مثال: مشتريات بضاعة آجلة / مستحقات عقد تشغيل',
        'notification_notes' => 'تنبيه: حل موعد استحقاق الدين — يرجى التواصل للتحصيل',
    ],

    'table_heading' => 'سجل الديون العامة — :count دين',

    // Collections
    'collections' => [
        'single' => 'سند تحصيل دين',
        'plural' => 'تحصيل الديون العامة',
        'sections' => [
            'collection_info' => 'بيانات سند التحصيل',
            'collection_info_desc' => 'رقم السند، الدين المرتبط، تاريخ التحصيل، ومبلغ الدفعة',
        ],
        'fields' => [
            'status' => 'الحالة',
            'voucher_number' => 'رقم سند التحصيل (تلقائي)',
            'collection_date' => 'تاريخ التحصيل',
            'debt' => 'الدين المرتبط',
            'amount' => 'المبلغ المحصل (ر.س)',
            'payment_method' => 'طريقة التحصيل',
            'treasury_transaction' => 'سند الخزينة الوارد المرتبط',
            'description' => 'البيان وملاحظات التحصيل',
            'executed_by' => 'المحصل المسجل',
        ],
        'actions' => [
            'approve' => 'اعتماد السند',
            'unapprove' => 'إلغاء الاعتماد',
            'approved_success' => 'تم اعتماد سند تحصيل الدين بنجاح',
            'unapproved_success' => 'تم إلغاء اعتماد سند التحصيل بنجاح',
        ],
        'payment_methods' => [
            'cash' => 'نقدي (خزينة)',
            'bank_transfer' => 'تحويل بنكي',
            'cheque' => 'شيك مصرفي',
        ],
        'table_heading' => 'سجل سندات تحصيل الديون — :count سند',
    ],
];
